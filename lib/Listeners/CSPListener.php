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

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class CSPListener implements IEventListener {
	/** @var IConfig */
	private $config;

	public function __construct(
		IConfig $config,
	) {
		$this->config = $config;
	}

	public function handle(Event $event): void {
		if (!$event instanceof AddContentSecurityPolicyEvent) {
			return;
		}

		$policy = new ContentSecurityPolicy();

		if($this->config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no') !== 'no') {
			$policy->addAllowedFrameDomain($this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''));
			$policy->addAllowedChildSrcDomain($this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''));
		}

		if($this->config->getAppValue('ownpad', 'ownpad_ethercalc_enable', 'no') !== 'no') {
			$policy->addAllowedFrameDomain($this->config->getAppValue('ownpad', 'ownpad_ethercalc_host', ''));
			$policy->addAllowedChildSrcDomain($this->config->getAppValue('ownpad', 'ownpad_ethercalc_host', ''));
		}

		$event->addPolicy($policy);
	}
}
