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

$appConfig = \OC::$server->getAppConfig();
$template->assign('ownpad_etherpad_host', $appConfig->getValue('ownpad', 'ownpad_etherpad_host', ''));
$template->assign('ownpad_ethercalc_host', $appConfig->getValue('ownpad', 'ownpad_ethercalc_host', ''));

return $template->fetchPage();