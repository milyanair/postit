<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Controller;

use OCA\PostIt\Service\NoteService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IUserSession;

/** Serves note images to the uploader and to users who can access a note that references them. */
class ImageController extends Controller {
	public function __construct(IRequest $request, private IAppData $appData, private IUserSession $session, private NoteService $svc) { parent::__construct('postit', $request); }
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function show(string $name): Http\Response {
		$u = $this->session->getUser();
		if (!$u || !preg_match('/^[a-f0-9]{32}\.(png|jpg|gif|webp)$/', $name)) return new DataResponse([], Http::STATUS_NOT_FOUND);
		$file = null;
		// Own images, or images in notes shared with this user (uploaded by the owner or another recipient).
		foreach ($this->svc->imageOwners($u->getUID(), $name) as $owner) {
			try { $file = $this->appData->getFolder('u-' . md5($owner))->getFile($name); break; } catch (NotFoundException) {}
		}
		if (!$file) return new DataResponse([], Http::STATUS_NOT_FOUND);
		$mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'][pathinfo($name, PATHINFO_EXTENSION)];
		$r = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Content-Disposition' => 'inline']);
		$r->cacheFor(86400, false, true);
		return $r;
	}
}
