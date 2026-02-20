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

namespace OCA\Ownpad\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
	/**
	 * @param string $appName
	 * @param IConfig $config
	 */
	public function __construct(
		private string $appName,
		private IConfig $config,
		private IInitialState $initialState,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$parameters = [
			'etherpadEnable' => $this->getHumanBooleanConfig('ownpad', 'ownpad_etherpad_enable', false),
			'etherpadHost' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''),
			'etherpadUseApi' => $this->getHumanBooleanConfig('ownpad', 'ownpad_etherpad_useapi', false),
			'etherpadPublicEnable' => $this->getHumanBooleanConfig('ownpad', 'ownpad_etherpad_public_enable', ),
			'etherpadVersion' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_public_enable', '1'),
			'etherpadEnableOauth' => $this->getHumanBooleanConfig('ownpad', 'ownpad_etherpad_enable_oauth', false),
			'etherpadApiKey' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_apikey', ''),
			'etherpadClientId' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_client_id', ''),
			'etherpadClientSecret' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_client_secret', ''),
			'etherpadCookieDomain' => $this->config->getAppValue('ownpad', 'ownpad_etherpad_cookie_domain', ''),
			'legacyTokenMode' => $this->getLegacyTokenMode(),
			'ethercalcEnable' => $this->getHumanBooleanConfig('ownpad', 'ownpad_ethercalc_enable', false),
			'ethercalcHost' => $this->config->getAppValue('ownpad', 'ownpad_ethercalc_host', ''),
			'mimetypeEpConfigured' => \OC::$server->getMimeTypeDetector()->detectPath("test.pad") === 'application/x-ownpad',
			'mimetypeEcConfigured' => \OC::$server->getMimeTypeDetector()->detectPath("test.calc") === 'application/x-ownpad-calc',
		];
		$this->initialState->provideInitialState('settings', $parameters);

		\OCP\Util::addScript($this->appName, 'ownpad-settings');
		return new TemplateResponse($this->appName, 'settings', [], '');
	}


	/**
	 * Helper function to retrive boolean values from human readable strings ('yes' / 'no')
	 */
	private function getHumanBooleanConfig(string $app, string $key, bool $default = false): bool {
		return $this->config->getAppValue($app, $key, $default ? 'yes' : 'no') === 'yes';
	}

	/**
	 * Helper function to retrieve the legacy token policy.
	 */
	private function getLegacyTokenMode(): string {
		$mode = strtolower($this->config->getAppValue('ownpad', 'ownpad_legacy_token_mode', 'all'));
		if (!in_array($mode, ['none', 'unprotected', 'all'], true)) {
			return 'all';
		}

		return $mode;
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
