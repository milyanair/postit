<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Controller;

use OCA\PostIt\Config;
use OCA\PostIt\Db\Note;
use OCA\PostIt\Db\NoteMapper;
use OCA\PostIt\Service\NoteService;
use OCA\PostIt\Service\Sanitizer;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IUserManager;
use OCP\Share\IShare;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class NoteApiController extends OCSController {
	private Sanitizer $sanitizer;
	public function __construct(IRequest $request, private NoteMapper $mapper, private IUserSession $session, private Config $config,
		private IAppData $appData, private IURLGenerator $url, private NoteService $svc, private IUserManager $users, private ISearch $collab) {
		parent::__construct('postit', $request);
		$this->sanitizer = new Sanitizer();
	}
	private function uid(): ?string {
		$u = $this->session->getUser();
		return ($u && $this->config->allowed($u)) ? $u->getUID() : null;
	}
	#[NoAdminRequired]
	public function index(): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		return new DataResponse(['notes' => $this->svc->all($uid), 'prefs' => $this->config->prefs($uid)]);
	}
	#[NoAdminRequired]
	public function show(int $id): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		if (!($n = $this->svc->access($id, $uid))) return new DataResponse([], Http::STATUS_NOT_FOUND);
		return new DataResponse($this->svc->view($n, $uid));
	}
	#[NoAdminRequired]
	public function create(?string $content = '', ?string $color = '', ?int $x = null, ?int $y = null, ?int $w = null, ?int $h = null, ?bool $pinned = null): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		$p = $this->config->prefs($uid);
		$n = new Note();
		$n->setUserId($uid);
		$n->setContent($this->sanitizer->clean($content));
		$n->setColor(Config::color($color) ?: $p['color']);
		$n->setCreatedAt(time()); $n->setUpdatedAt(time());
		$n = $this->mapper->insert($n);
		$this->svc->setState($n->getId(), $uid, ['w' => $this->clamp($w ?? $p['w'], 160, 4000), 'h' => $this->clamp($h ?? $p['h'], 120, 4000),
			'x' => $this->clamp($x ?? 0, -2000, 10000), 'y' => $this->clamp($y ?? 0, 0, 10000), 'pinned' => ($pinned ?? $p['pinned']) ? 1 : 0, 'open' => 1]);
		return new DataResponse($this->svc->view($n, $uid));
	}
	/** Content and color are shared; geometry, pinned, archived and open are the caller's own. */
	#[NoAdminRequired]
	public function update(int $id, ?string $content = null, ?string $color = null, ?int $x = null, ?int $y = null, ?int $w = null, ?int $h = null,
		?bool $pinned = null, ?bool $archived = null, ?bool $open = null): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		if (!($n = $this->svc->access($id, $uid))) return new DataResponse([], Http::STATUS_NOT_FOUND);
		$touched = false;
		if ($content !== null) { $n->setContent($this->sanitizer->clean($content)); $touched = true; }
		if ($color !== null && Config::color($color)) { $n->setColor(Config::color($color)); $touched = true; }
		if ($touched) { $n->setUpdatedAt(time()); $n = $this->mapper->update($n); }
		$s = [];
		if ($x !== null) $s['x'] = $this->clamp($x, -2000, 10000);
		if ($y !== null) $s['y'] = $this->clamp($y, 0, 10000);
		if ($w !== null) $s['w'] = $this->clamp($w, 160, 4000);
		if ($h !== null) $s['h'] = $this->clamp($h, 120, 4000);
		if ($pinned !== null) $s['pinned'] = $pinned ? 1 : 0;
		if ($open !== null) $s['open'] = $open ? 1 : 0;
		if ($archived !== null) { $s['archived'] = $archived ? 1 : 0; if ($archived) { $s['pinned'] = 0; $s['open'] = 0; } }
		if ($s) $this->svc->setState($id, $uid, $s);
		return new DataResponse($this->svc->view($n, $uid));
	}
	/** Owner: deletes the note for everyone. Recipient: removes it from their own list only. */
	#[NoAdminRequired]
	public function destroy(int $id): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		if (!($n = $this->svc->access($id, $uid))) return new DataResponse([], Http::STATUS_NOT_FOUND);
		if ($n->getUserId() === $uid) { $this->svc->deleteNote($n); return new DataResponse(['deleted' => $id]); }
		$this->svc->removeShare($id, $uid);
		return new DataResponse(['left' => $id]);
	}
	#[NoAdminRequired]
	public function shares(int $id): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		if (!($n = $this->svc->access($id, $uid))) return new DataResponse([], Http::STATUS_NOT_FOUND);
		return new DataResponse($this->shareList($n));
	}
	#[NoAdminRequired]
	public function share(int $id, string $userId = ''): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		if (!($n = $this->svc->access($id, $uid))) return new DataResponse([], Http::STATUS_NOT_FOUND);
		if ($n->getUserId() !== $uid) return new DataResponse(['error' => 'owner only'], Http::STATUS_FORBIDDEN);
		$target = $this->users->get($userId);
		if (!$target || $target->getUID() === $uid || !$target->isEnabled() || !$this->config->allowed($target) || !$this->canShareWith($userId))
			return new DataResponse(['error' => 'user'], Http::STATUS_BAD_REQUEST);
		$this->svc->addShare($id, $target->getUID());
		return new DataResponse($this->shareList($n));
	}
	#[NoAdminRequired]
	public function unshare(int $id, string $userId): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		if (!($n = $this->svc->access($id, $uid))) return new DataResponse([], Http::STATUS_NOT_FOUND);
		if ($n->getUserId() !== $uid) return new DataResponse(['error' => 'owner only'], Http::STATUS_FORBIDDEN);
		$this->svc->removeShare($id, $userId);
		return new DataResponse($this->shareList($n));
	}
	/** User picker. Uses the core collaborator search, so admin sharing rules (enumeration, groups) apply. */
	#[NoAdminRequired]
	public function users(string $search = ''): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		return new DataResponse($this->searchUsers(trim($search), $uid));
	}
	private function searchUsers(string $search, string $uid): array {
		if ($search === '') return [];
		[$res] = $this->collab->search($search, [IShare::TYPE_USER], false, 10, 0);
		$out = [];
		foreach (array_merge($res['exact']['users'] ?? [], $res['users'] ?? []) as $r) {
			$id = (string)($r['value']['shareWith'] ?? '');
			if ($id === '' || $id === $uid || isset($out[$id])) continue;
			$u = $this->users->get($id);
			if (!$u || !$this->config->allowed($u)) continue;
			$out[$id] = ['id' => $id, 'name' => (string)($r['label'] ?? $id)];
		}
		return array_values($out);
	}
	private function canShareWith(string $target): bool {
		$u = $this->users->get($target);
		if (!$u) return false;
		foreach ($this->searchUsers($target, (string)$this->uid()) as $r) if ($r['id'] === $u->getUID()) return true;
		$name = $u->getDisplayName();
		if ($name && $name !== $target) foreach ($this->searchUsers($name, (string)$this->uid()) as $r) if ($r['id'] === $u->getUID()) return true;
		return false;
	}
	private function shareList(Note $n): array {
		return ['owner' => $n->getUserId(), 'ownerName' => $this->svc->name($n->getUserId()),
			'shares' => array_map(fn ($r) => ['id' => $r, 'name' => $this->svc->name($r)], $this->svc->recipients($n->getId()))];
	}
	#[NoAdminRequired]
	public function upload(): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		$f = $this->request->getUploadedFile('image');
		if (!is_array($f) || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || ($f['size'] ?? 0) > 5 * 1024 * 1024) return new DataResponse(['error' => 'upload'], Http::STATUS_BAD_REQUEST);
		$data = file_get_contents($f['tmp_name']);
		$info = $data !== false ? @getimagesizefromstring($data) : false;
		$ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
		if (!$ext) return new DataResponse(['error' => 'type'], Http::STATUS_BAD_REQUEST);
		$name = bin2hex(random_bytes(16)) . '.' . $ext;
		try { $folder = $this->appData->getFolder('u-' . md5($uid)); } catch (NotFoundException) { $folder = $this->appData->newFolder('u-' . md5($uid)); }
		$folder->newFile($name, $data);
		return new DataResponse(['url' => $this->url->linkToRoute('postit.Image.show', ['name' => $name])]);
	}
	#[NoAdminRequired]
	public function prefs(array $prefs = []): DataResponse {
		if (!($uid = $this->uid())) return new DataResponse([], Http::STATUS_FORBIDDEN);
		return new DataResponse($this->config->setPrefs($uid, $prefs));
	}
	private function clamp(int $v, int $lo, int $hi): int { return max($lo, min($hi, $v)); }
}
