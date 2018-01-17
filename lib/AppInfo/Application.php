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

use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

    public function __construct(array $urlParams = array()) {
        parent::__construct('ownpad', $urlParams);
    }

    public function registerHooks() {
        $dispatcher = $this->getContainer()->getServer()->getEventDispatcher();

        $dispatcher->addListener(
            'OCA\Files::loadAdditionalScripts',
            function() {
                Util::addStyle('ownpad', 'ownpad');
                Util::addScript('ownpad', 'ownpad');
            });
    }
}
