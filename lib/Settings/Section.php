<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {
	public function __construct(private IL10N $l, private IURLGenerator $url) {}
	public function getID(): string { return 'postit'; }
	public function getName(): string { return $this->l->t('Post It'); }
	public function getPriority(): int { return 81; }
	public function getIcon(): string { return $this->url->imagePath('postit', 'app-dark.svg'); }
}
