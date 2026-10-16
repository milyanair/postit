<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\AppInfo;

use OCA\PostIt\Listener\InjectListener;
use OCA\PostIt\Listener\UserDeletedListener;
use OCP\User\Events\UserDeletedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'postit';
	public function __construct() { parent::__construct(self::APP_ID); }
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, InjectListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
	}
	public function boot(IBootContext $context): void {}
}
