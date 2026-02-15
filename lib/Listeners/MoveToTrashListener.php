<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @copyright
 */

namespace OCA\Ownpad\Listeners;

use OCA\Ownpad\Service\OwnpadService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;

class MoveToTrashListener implements IEventListener {
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

		$name = $node->getName();
		if (!str_ends_with(strtolower($name), '.pad')) {
			return;
		}

		$fileId = (int)$node->getId();
		$url = $this->ownpadService->getPadUrlForFileId($fileId);
		if ($url === null) {
			return;
		}

		if ($this->ownpadService->isDeleteOnTrashEnabled()) {
			$this->ownpadService->deletePadFromUrl($url);
		}
		$this->ownpadService->deletePadUrlForFileId($fileId);
	}
}
