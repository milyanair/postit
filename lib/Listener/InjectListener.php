<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Listener;

use OCA\PostIt\Config;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use OCP\Util;

/** @template-implements IEventListener<Event> */
class InjectListener implements IEventListener {
	public function __construct(private IUserSession $session, private Config $config, private IInitialState $state) {}
	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent) || !$event->isLoggedIn()) return;
		$u = $this->session->getUser();
		if ($u === null || !$this->config->allowed($u)) return;
		$this->state->provideInitialState('prefs', $this->config->prefs($u->getUID()));
		Util::addStyle('postit', 'postit');
		Util::addTranslations('postit');
		Util::addScript('postit', 'postit');
	}
}
