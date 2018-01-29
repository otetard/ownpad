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

namespace OCA\Ownpad\Migration;

use OCP\Migration\IRepairStep;
use OCP\IConfig;
use OCP\Migration\IOutput;

class MigrateSettings implements IRepairStep {

    /** @var IConfig */
    private $config;

    /**
     * @param IConfig $config
     */
    public function __construct(IConfig $config) {
        $this->config = $config;
    }

    public function getName() {
        return '0.5.2 introduces a new checkbox to enable/disable Etherpad/Ethercalc.';
    }

    public function run(IOutput $output) {
        $installedVersion = $this->config->getAppValue('ownpad', 'installed_version', '0.0.0');
        if(version_compare($installedVersion, '0.5.2', '<')) {
            $appConfig = \OC::$server->getConfig();

            $enabled = ($appConfig->getAppValue('ownpad', 'ownpad_etherpad_host', '') !== '') ? 'yes' : 'no';
            $appConfig->setAppValue('ownpad', 'ownpad_etherpad_enable', $enabled);

            $enabled = ($appConfig->getAppValue('ownpad', 'ownpad_ethercalc_host', '') !== '') ? 'yes' : 'no';
            $appConfig->setAppValue('ownpad', 'ownpad_ethercalc_enable', $enabled);
        }
    }
}
