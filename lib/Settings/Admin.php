<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Settings;

use OCA\PostIt\Config;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	public function __construct(private Config $config, private IGroupManager $groups) {}
	public function getForm(): TemplateResponse {
		Util::addScript('postit', 'postit-settings');
		$all = array_map(fn ($g) => ['id' => $g->getGID(), 'name' => $g->getDisplayName()], $this->groups->search(''));
		return new TemplateResponse('postit', 'admin', ['groups' => $all, 'selected' => $this->config->groups()], '');
	}
	public function getSection(): string { return 'postit'; }
	public function getPriority(): int { return 10; }
}
