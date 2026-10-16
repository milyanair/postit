<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Settings;

use OCA\PostIt\Config;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;

class Personal implements ISettings {
	public function __construct(private Config $config, private IUserSession $session) {}
	public function getForm(): TemplateResponse {
		Util::addScript('postit', 'postit-settings');
		$u = $this->session->getUser();
		return new TemplateResponse('postit', 'personal', ['prefs' => $this->config->prefs($u ? $u->getUID() : ''), 'allowed' => $u && $this->config->allowed($u)], '');
	}
	public function getSection(): string { return 'postit'; }
	public function getPriority(): int { return 10; }
}
