<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2017
 */

namespace OCA\Ownpad\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Ownpad\Listeners\CSPListener;
use OCA\Ownpad\Listeners\LoadPublicViewerListener;

use OCA\Ownpad\Listeners\LoadViewerListener;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Util;

class Application extends App implements IBootstrap {
	public const APP_ID = 'ownpad';

	public function __construct() {
		parent::__construct(self::APP_ID);

		$dispatcher = $this->getContainer()->query(IEventDispatcher::class);
		$dispatcher->addListener(
			LoadAdditionalScriptsEvent::class,
			function () {
				Util::addStyle('ownpad', 'ownpad');
				Util::addInitScript('ownpad', 'ownpad-main');
			}
		);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(LoadViewer::class, LoadViewerListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, LoadPublicViewerListener::class);
		$context->registerEventListener(AddContentSecurityPolicyEvent::class, CSPListener::class);

		if (class_exists('OCA\\Files_Trashbin\\Events\\MoveToTrashEvent')) {
			$context->registerEventListener('OCA\\Files_Trashbin\\Events\\MoveToTrashEvent', \OCA\Ownpad\Listeners\MoveToTrashListener::class);
		}
	}

	public function boot(IBootContext $context): void {
	}
}
