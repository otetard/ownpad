<?php
/**
 * ownCloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2016
 */

OCP\JSON::callCheck();
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('ownpad');

$config = [];

$appConfig = \OC::$server->getConfig();
$config['ownpad_etherpad_enable'] = $appConfig->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no');
$config['ownpad_etherpad_useapi'] = $appConfig->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no');
$config['ownpad_ethercalc_enable'] = $appConfig->getAppValue('ownpad', 'ownpad_ethercalc_enable', 'no');

OC_JSON::success(array('data' => $config));
