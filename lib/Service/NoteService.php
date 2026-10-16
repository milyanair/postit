<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Service;

use OCA\PostIt\Db\Note;
use OCA\PostIt\Db\NoteMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;

/** Notes plus per-user state and shares. All permission checks live here. */
class NoteService {
	public const STATE = ['x', 'y', 'w', 'h', 'pinned', 'archived', 'open'];
	private array $names = [];
	public function __construct(private IDBConnection $db, private NoteMapper $mapper, private IUserManager $users) {}

	/** Owner or share recipient may access; returns the note or null. */
	public function access(int $id, string $uid): ?Note {
		try { $n = $this->mapper->findById($id); } catch (DoesNotExistException) { return null; }
		if ($n->getUserId() === $uid || $this->isShared($id, $uid)) return $n;
		return null;
	}
	public function isShared(int $id, string $uid): bool {
		$q = $this->db->getQueryBuilder();
		$q->select('id')->from('postit_shares')->where($q->expr()->eq('note_id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($q->expr()->eq('user_id', $q->createNamedParameter($uid)))->setMaxResults(1);
		$r = $q->executeQuery(); $ok = (bool)$r->fetch(); $r->closeCursor();
		return $ok;
	}
	/** @return string[] recipients of a note */
	public function recipients(int $id): array {
		$q = $this->db->getQueryBuilder();
		$q->select('user_id')->from('postit_shares')->where($q->expr()->eq('note_id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))->orderBy('id');
		$r = $q->executeQuery(); $out = [];
		while ($row = $r->fetch()) $out[] = (string)$row['user_id'];
		$r->closeCursor();
		return $out;
	}
	public function name(string $uid): string {
		if (!isset($this->names[$uid])) { $u = $this->users->get($uid); $this->names[$uid] = $u ? ($u->getDisplayName() ?: $uid) : $uid; }
		return $this->names[$uid];
	}
	public function state(int $id, string $uid): ?array {
		$q = $this->db->getQueryBuilder();
		$q->select('*')->from('postit_state')->where($q->expr()->eq('note_id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($q->expr()->eq('user_id', $q->createNamedParameter($uid)));
		$r = $q->executeQuery(); $row = $r->fetch(); $r->closeCursor();
		return $row ?: null;
	}
	/** Insert or update the caller's own state for a note. */
	public function setState(int $id, string $uid, array $s): void {
		$cols = ['x' => 'x', 'y' => 'y', 'w' => 'w', 'h' => 'h', 'pinned' => 'pinned', 'archived' => 'archived', 'open' => 'is_open'];
		$vals = [];
		foreach ($cols as $k => $c) if (array_key_exists($k, $s) && $s[$k] !== null) $vals[$c] = is_bool($s[$k]) ? (int)$s[$k] : (int)$s[$k];
		if ($this->state($id, $uid) === null) {
			$q = $this->db->getQueryBuilder();
			$v = ['note_id' => $q->createNamedParameter($id, IQueryBuilder::PARAM_INT), 'user_id' => $q->createNamedParameter($uid)];
			foreach ($vals as $c => $x) $v[$c] = $q->createNamedParameter($x, IQueryBuilder::PARAM_INT);
			$q->insert('postit_state')->values($v)->executeStatement();
			return;
		}
		if (!$vals) return;
		$q = $this->db->getQueryBuilder();
		$q->update('postit_state');
		foreach ($vals as $c => $x) $q->set($c, $q->createNamedParameter($x, IQueryBuilder::PARAM_INT));
		$q->where($q->expr()->eq('note_id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($q->expr()->eq('user_id', $q->createNamedParameter($uid)))->executeStatement();
	}
	/** The note as the caller sees it: shared content plus the caller's own state. */
	public function view(Note $n, string $uid, ?array $st = null, ?array $rcpt = null): array {
		$st = $st ?? $this->state($n->getId(), $uid) ?? [];
		$rcpt = $rcpt ?? $this->recipients($n->getId());
		$owner = $n->getUserId();
		return ['id' => $n->getId(), 'content' => $n->getContent() ?? '', 'color' => $n->getColor(),
			'x' => (int)($st['x'] ?? 0), 'y' => (int)($st['y'] ?? 0), 'w' => (int)($st['w'] ?? 0), 'h' => (int)($st['h'] ?? 0),
			'pinned' => !empty($st['pinned']), 'archived' => !empty($st['archived']), 'open' => !empty($st['is_open']),
			'createdAt' => $n->getCreatedAt(), 'updatedAt' => $n->getUpdatedAt(),
			'owner' => $owner, 'ownerName' => $this->name($owner), 'isOwner' => $owner === $uid,
			'shared' => $owner !== $uid || count($rcpt) > 0, 'shareCount' => count($rcpt)];
	}
	/** Every note the user owns or that is shared with them. */
	public function all(string $uid): array {
		$ids = [];
		$q = $this->db->getQueryBuilder();
		$q->select('note_id')->from('postit_shares')->where($q->expr()->eq('user_id', $q->createNamedParameter($uid)));
		$r = $q->executeQuery(); while ($row = $r->fetch()) $ids[] = (int)$row['note_id']; $r->closeCursor();
		$notes = $this->mapper->findAll($uid);
		foreach (array_chunk($ids, 500) as $chunk) foreach ($this->mapper->findByIds($chunk) as $n) $notes[] = $n;
		$states = [];
		$q = $this->db->getQueryBuilder();
		$q->select('*')->from('postit_state')->where($q->expr()->eq('user_id', $q->createNamedParameter($uid)));
		$r = $q->executeQuery(); while ($row = $r->fetch()) $states[(int)$row['note_id']] = $row; $r->closeCursor();
		$counts = [];
		$own = array_map(fn ($n) => $n->getId(), array_filter($notes, fn ($n) => $n->getUserId() === $uid));
		foreach (array_chunk($own, 500) as $chunk) {
			$q = $this->db->getQueryBuilder();
			$q->select('note_id')->from('postit_shares')->where($q->expr()->in('note_id', $q->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)));
			$r = $q->executeQuery(); while ($row = $r->fetch()) $counts[(int)$row['note_id']][] = 1; $r->closeCursor();
		}
		$out = [];
		foreach ($notes as $n) {
			$rc = $n->getUserId() === $uid ? ($counts[$n->getId()] ?? []) : [1];
			$out[] = $this->view($n, $uid, $states[$n->getId()] ?? [], $rc);
		}
		usort($out, fn ($a, $b) => $b['updatedAt'] <=> $a['updatedAt']);
		return $out;
	}
	public function addShare(int $id, string $with): void {
		if ($this->isShared($id, $with)) return;
		$q = $this->db->getQueryBuilder();
		$q->insert('postit_shares')->values(['note_id' => $q->createNamedParameter($id, IQueryBuilder::PARAM_INT),
			'user_id' => $q->createNamedParameter($with), 'created_at' => $q->createNamedParameter(time(), IQueryBuilder::PARAM_INT)])->executeStatement();
	}
	/** Remove a recipient: their share and their personal state. */
	public function removeShare(int $id, string $with): void {
		foreach (['postit_shares', 'postit_state'] as $t) {
			$q = $this->db->getQueryBuilder();
			$q->delete($t)->where($q->expr()->eq('note_id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
				->andWhere($q->expr()->eq('user_id', $q->createNamedParameter($with)))->executeStatement();
		}
	}
	/** Owner delete: the note, every share and every user's state. */
	public function deleteNote(Note $n): void {
		foreach (['postit_shares', 'postit_state'] as $t) {
			$q = $this->db->getQueryBuilder();
			$q->delete($t)->where($q->expr()->eq('note_id', $q->createNamedParameter($n->getId(), IQueryBuilder::PARAM_INT)))->executeStatement();
		}
		$this->mapper->delete($n);
	}
	/** Account removal: delete owned notes, drop the user's shares and state. */
	public function deleteUser(string $uid): void {
		foreach ($this->mapper->findAll($uid, 100000) as $n) $this->deleteNote($n);
		foreach (['postit_shares', 'postit_state'] as $t) {
			$q = $this->db->getQueryBuilder();
			$q->delete($t)->where($q->expr()->eq('user_id', $q->createNamedParameter($uid)))->executeStatement();
		}
	}
	/** Users whose image folders may hold an image referenced by a note the caller can access. */
	public function imageOwners(string $uid, string $name): array {
		$cands = [$uid];
		foreach ($this->all($uid) as $v) {
			if (strpos($v['content'], $name) === false) continue;
			$cands[] = $v['owner'];
			foreach ($this->recipients($v['id']) as $r) $cands[] = $r;
		}
		return array_values(array_unique($cands));
	}
}
