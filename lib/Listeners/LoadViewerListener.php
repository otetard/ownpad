<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2023
 */

namespace OCA\Ownpad\Listeners;

use OCA\Ownpad\AppInfo\Application;
use OCA\Viewer\Event\LoadViewer;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadViewerListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof LoadViewer) {
			return;
		}
		Util::addScript(Application::APP_ID, 'ownpad-viewer', 'viewer');
	}
}
