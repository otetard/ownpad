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

/*
 * 0.6.0 introduces a new checkbox to enable/disable
 * Etherpad/Ethercalc.
 */
if(version_compare($installedVersion, '0.5.2', '<')) {
    $appConfig = \OC::$server->getConfig();
    $enabled = ($appConfig->getAppValue('ownpad', 'ownpad_etherpad_host', '') !== '') ? 'yes' : 'no';
    $appConfig->setValue('ownpad', 'ownpad_etherpad_enable', $enabled);

    $enabled = ($appConfig->getAppValue('ownpad', 'ownpad_ethercalc_host', '') !== '') ? 'yes' : 'no';
    $appConfig->setValue('ownpad', 'ownpad_ethercalc_enable', $enabled);
}
