<?php
/**
 * ownCloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2015
 */

\OC_Util::checkAdminUser();

\OCP\Util::addScript('ownpad', 'settings');

$template = new OCP\Template('ownpad', 'settings');

$appConfig = \OC::$server->getConfig();
$template->assign('ownpad_etherpad_enable', $appConfig->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no'));
$template->assign('ownpad_etherpad_host', $appConfig->getAppValue('ownpad', 'ownpad_etherpad_host', ''));
$template->assign('ownpad_etherpad_useapi', $appConfig->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no'));
$template->assign('ownpad_etherpad_apikey', $appConfig->getAppValue('ownpad', 'ownpad_etherpad_apikey', ''));
$template->assign('ownpad_etherpad_cookie_domain', $appConfig->getAppValue('ownpad', 'ownpad_etherpad_cookie_domain', ''));
$template->assign('ownpad_ethercalc_enable', $appConfig->getAppValue('ownpad', 'ownpad_ethercalc_enable', 'no'));
$template->assign('ownpad_ethercalc_host', $appConfig->getAppValue('ownpad', 'ownpad_ethercalc_host', ''));

return $template->fetchPage();
