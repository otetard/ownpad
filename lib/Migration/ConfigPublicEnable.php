<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Ole Reglitzki <frissdiegurke@protonmail.com>
 * @copyright Ole Reglitzki <frissdiegurke@protonmail.com>, 2018
 */

namespace OCA\Ownpad\Migration;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ConfigPublicEnable implements IRepairStep {

	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getName() {
		return '0.6.6 introduces a new checkbox to enable/disable public pads if protected pads are enabled.';
	}

	public function run(IOutput $output) {
		$installedVersion = $this->config->getAppValue('ownpad', 'installed_version', '0.0.0');
		if(version_compare($installedVersion, '0.6.6', '<') and $installedVersion !== '0.0.0') {
			$appConfig = \OC::$server->getConfig();

			$enabled = ($appConfig->getAppValue('ownpad', 'ownpad_etherpad_public_enable', '') === '') ? 'yes' : 'no';
			$appConfig->setAppValue('ownpad', 'ownpad_etherpad_public_enable', $enabled);
		}
	}

}
