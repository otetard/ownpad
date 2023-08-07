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
use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Util;

class Application extends App {
    public const APP_ID = 'ownpad';

    public function __construct() {
        parent::__construct(self::APP_ID);

        $dispatcher = $this->getContainer()->query(IEventDispatcher::class);
        $dispatcher->addListener(
            LoadAdditionalScriptsEvent::class,
            function() {
                Util::addStyle('ownpad', 'ownpad');
                Util::addScript('ownpad', 'ownpad-main');
            }
        );
    }
}
