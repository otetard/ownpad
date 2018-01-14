<?php
/**
 * ownCloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2017
 */

namespace OCA\Ownpad\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {

    /** @var string */
    protected $appName;

    /** @var IConfig */
    protected $config;

    /**
     * @param string $appName
     * @param IConfig $config
     */
    public function __construct($appName, IConfig $config) {
        $this->appName = $appName;
        $this->config = $config;
    }

    /**
     * @return TemplateResponse
     */
    public function getForm() {
        return new TemplateResponse($this->appName, 'settings', [
            'ownpad_etherpad_enable' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no'),
            'ownpad_etherpad_host' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''),
            'ownpad_etherpad_useapi' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no'),
            'ownpad_etherpad_apikey' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_apikey', ''),
            'ownpad_etherpad_cookie_domain' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_cookie_domain', ''),
            'ownpad_ethercalc_enable' => $this->config->getAppValue('ownpad', 'ownpad_ethercalc_enable', 'no'),
            'ownpad_ethercalc_host' => $this->config->getAppValue('ownpad', 'ownpad_ethercalc_host', ''),
        ], 'blank');
    }


    /**
     * @return string the section ID, e.g. 'sharing'
     */
    public function getSection() {
        return 'additional';
    }

    /**
     * @return int whether the form should be rather on the top or bottom of
     * the admin section. The forms are arranged in ascending order of the
     * priority values. It is required to return a value between 0 and 100.
     *
     * E.g.: 70
     */
    public function getPriority() {
        return 30;
    }
}
