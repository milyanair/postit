<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;

class Config {
	public const COLORS = ['#fff59d', '#ffcc80', '#f8bbd0', '#b3e5fc', '#c8e6c9', '#e1bee7', '#ffffff', '#cfd8dc'];
	public function __construct(private IConfig $config, private IGroupManager $groups) {}
	public function groups(): array {
		$v = json_decode($this->config->getAppValue('postit', 'groups', '[]'), true);
		return is_array($v) ? array_values(array_filter($v, 'is_string')) : [];
	}
	public function setGroups(array $g): void {
		$g = array_values(array_filter($g, fn ($x) => is_string($x) && $this->groups->groupExists($x)));
		$this->config->setAppValue('postit', 'groups', json_encode($g));
	}
	public function allowed(IUser $user): bool {
		$g = $this->groups();
		if ($g === []) return true;
		foreach ($g as $gid) if ($this->groups->isInGroup($user->getUID(), $gid)) return true;
		return false;
	}
	public static function color(?string $c): string { return is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? strtolower($c) : ''; }
	public function prefs(string $uid): array {
		$g = fn ($k, $d) => $this->config->getUserValue($uid, 'postit', $k, $d);
		return [
			'w' => max(160, min(1600, (int)$g('w', '280'))),
			'h' => max(120, min(1200, (int)$g('h', '260'))),
			'color' => self::color($g('color', '')) ?: self::COLORS[0],
			'fontSize' => max(10, min(32, (int)$g('fontSize', '15'))),
			'pinned' => $g('pinned', '0') === '1',
			'launcherX' => (int)$g('launcherX', '-1'),
			'launcherY' => (int)$g('launcherY', '-1'),
			'palette' => self::COLORS,
		];
	}
	public function setPrefs(string $uid, array $p): array {
		if (isset($p['w'])) $this->config->setUserValue($uid, 'postit', 'w', (string)max(160, min(1600, (int)$p['w'])));
		if (isset($p['h'])) $this->config->setUserValue($uid, 'postit', 'h', (string)max(120, min(1200, (int)$p['h'])));
		if (isset($p['color']) && self::color((string)$p['color'])) $this->config->setUserValue($uid, 'postit', 'color', self::color((string)$p['color']));
		if (isset($p['fontSize'])) $this->config->setUserValue($uid, 'postit', 'fontSize', (string)max(10, min(32, (int)$p['fontSize'])));
		if (isset($p['pinned'])) $this->config->setUserValue($uid, 'postit', 'pinned', $p['pinned'] ? '1' : '0');
		foreach (['launcherX', 'launcherY'] as $k) if (isset($p[$k])) $this->config->setUserValue($uid, 'postit', $k, (string)max(-1, min(20000, (int)$p[$k])));
		return $this->prefs($uid);
	}
}
