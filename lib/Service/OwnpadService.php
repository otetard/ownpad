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

namespace OCA\Ownpad\Service;

use Exception;

class OwnpadService {

    public function create($dir, $padname, $type, $protected) {
        // Generate a random pad name
        $token = \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(16, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_UPPER.\OCP\Security\ISecureRandom::CHAR_DIGITS);

        $l10n = \OC::$server->getL10N('ownpad');
        $l10n_files = \OC::$server->getL10N('files');

        $result = ['success' => false,
                   'data' => NULL];

        if($type === "ethercalc") {
            $ext = "calc";
            $host = \OC::$server->getConfig()->getAppValue('ownpad', 'ownpad_ethercalc_host', false);

            /*
             * Prepend the calc’s name with a `=` to enable multisheet
             * support.
             *
             * More info:
             *   – https://github.com/audreyt/ethercalc/issues/138
             *   – https://github.com/otetard/ownpad/issues/26
             */
            $url = sprintf("%s/=%s", rtrim($host, "/"), $token);
        }
        elseif($type === "etherpad") {
            $padID = $token;
                try {
                    $config = \OC::$server->getConfig();
                    if($config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no') !== 'no' AND $config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no') !== 'no') {
                        $eplHost = $config->getAppValue('ownpad', 'ownpad_etherpad_host', '');
                        $eplApiKey = $config->getAppValue('ownpad', 'ownpad_etherpad_apikey', '');
                        $eplInstance = new \EtherpadLite\Client($eplApiKey, $eplHost . "/api");
                    }
                    if($protected === true) {
                        // Create a protected (group) pad via API
                        $group = $eplInstance->createGroup();
                        $groupPad = $eplInstance->createGroupPad($group->groupID, $token);
                        $padID = $groupPad->padID;
                    }
                    else {
                        // Create a public pad via API
                        $createPadResult = $eplInstance->createPad($token);
                    }               
                }
                catch(Exception $e) {
                    throw new OwnpadException($l10n->t('Unable to communicate with Etherpad API.'));
                }

            $ext = "pad";
            $host = \OC::$server->getConfig()->getAppValue('ownpad', 'ownpad_etherpad_host', false);
            $url = sprintf("%s/p/%s", rtrim($host, "/"), $padID);
        }

        if($padname === '' || $padname === '.' || $padname === '..') {
            throw new OwnpadException($l10n->t('Incorrect padname.'));
        }

        try {
            $view = new \OC\Files\View();
            $view->verifyPath($dir, $padname);
        }
        catch(\OCP\Files\InvalidPathException $ex) {
            throw new OwnpadException($l10n_files->t("Invalid name, '\\', '/', '<', '>', ':', '\"', '|', '?' and '*' are not allowed."));
        }

        if(!\OC\Files\Filesystem::file_exists($dir . '/')) {
            throw new OwnpadException($l10n_files->t('The target folder has been moved or deleted.'));
        }

        // Add the extension only if padname doesn’t contain it
        if(substr($padname, -strlen(".$ext")) !== ".$ext") {
            $filename = "$padname.$ext";
        }
        else {
            $filename = $padname;
        }

        $target = $dir . "/" . $filename;

        if(\OC\Files\Filesystem::file_exists($target)) {
            throw new OwnpadException($l10n_files->t('The name %s is already used in the folder %s. Please choose a different name.', [$filename, $dir]));
        }

        $content = sprintf("[InternetShortcut]\nURL=%s", $url);

        if(\OC\Files\Filesystem::file_put_contents($target, $content)) {
            $meta = \OC\Files\Filesystem::getFileInfo($target);
            return \OCA\Files\Helper::formatFileInfo($meta);
        }

        throw new OwnpadException($l10n_files->t('Error when creating the file'));
    }
}
