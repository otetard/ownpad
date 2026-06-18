<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 */

namespace OCA\Ownpad\Listeners;

use OCA\Ownpad\Service\OwnpadService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;

class DeleteOwnpadMappingListener implements IEventListener {
	public function __construct(
		private OwnpadService $ownpadService
	) {
	}

	public function handle(Event $event): void {
		if (!method_exists($event, 'getNode')) {
			return;
		}

		$node = $event->getNode();
		if (!$node instanceof File) {
			return;
		}

		$name = strtolower($node->getName());
		if (!str_ends_with($name, '.pad')) {
			return;
		}

		$this->ownpadService->deletePadUrlForFileId((int)$node->getId());
	}
}
