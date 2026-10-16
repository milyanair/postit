<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Controller;

use OCA\PostIt\Config;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class SettingsController extends Controller {
	public function __construct(IRequest $request, private Config $config) { parent::__construct('postit', $request); }
	/** Admin only (default). */
	public function setGroups(array $groups = []): JSONResponse {
		$this->config->setGroups($groups);
		return new JSONResponse(['groups' => $this->config->groups()]);
	}
}
