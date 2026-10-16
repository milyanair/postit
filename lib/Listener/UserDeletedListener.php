<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Listener;

use OCA\PostIt\Service\NoteService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

/** @template-implements IEventListener<UserDeletedEvent> */
class UserDeletedListener implements IEventListener {
	public function __construct(private NoteService $svc) {}
	public function handle(Event $event): void {
		if ($event instanceof UserDeletedEvent) $this->svc->deleteUser($event->getUser()->getUID());
	}
}
