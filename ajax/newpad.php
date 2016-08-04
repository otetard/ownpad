<?php
/**
 * ownCloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2015
 *
 * The logic of this file is inspired by `ajax/newfile.php` from the
 * `files` app (core).
 */

use EtherpadLite\Client;

if(!OC_User::isLoggedIn()) {
    exit;
}

$dir = isset($_REQUEST['dir']) ? '/'.trim($_REQUEST['dir'], '/\\') : '';
$padname = isset($_REQUEST['padname']) ? trim($_REQUEST['padname'], '/\\') : '';
$type = isset($_REQUEST['type']) ? trim($_REQUEST['type'], '/\\') : '';
$protected = isset($_REQUEST['protected']) && $_REQUEST['protected'] === 'true' ? true : false;

OC_JSON::callCheck();

// Generate a random pad name
$token = \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(16, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_UPPER.\OCP\Security\ISecureRandom::CHAR_DIGITS);

$l10n = \OC::$server->getL10N('ownpad');
$l10n_files = \OC::$server->getL10N('files');

$result = ['success' => false,
           'data' => NULL];

if($type === "ethercalc") {
    $ext = "calc";
    $host = \OCP\Config::getAppValue('ownpad', 'ownpad_ethercalc_host', false);
    $url = sprintf("%s/%s", rtrim($host, "/"), $token);
}
elseif($type === "etherpad") {
    $padID = $token;

    if($protected === true) {
        try {
            $config = \OC::$server->getConfig();
            if($config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no') !== 'no' AND $config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no') !== 'no') {
                $eplHost = $config->getAppValue('ownpad', 'ownpad_etherpad_host', '');
                $eplApiKey = $config->getAppValue('ownpad', 'ownpad_etherpad_apikey', '');
                $eplInstance = new Client($eplApiKey, $eplHost . "/api");
            }

            $group = $eplInstance->createGroup();
            $groupPad = $eplInstance->createGroupPad($group->groupID, $token);
            $padID = $groupPad->padID;
        }
        catch(Exception $e) {
            $result['data'] = ['message' => (string)$l10n->t('Unable to communicate with Etherpad API.')];
            OCP\JSON::error($result);
            exit();
        }
    }

    $ext = "pad";
    $host = \OCP\Config::getAppValue('ownpad', 'ownpad_etherpad_host', false);
    $url = sprintf("%s/p/%s", rtrim($host, "/"), $padID);
}

if($padname === '' || $padname === '.' || $padname === '..') {
    $result['data'] = array('message' => (string)$l10n->t('Incorrect padname.'));
    OCP\JSON::error($result);
    exit();
}

if(!OCP\Util::isValidFileName($padname)) {
    $result['data'] = array('message' => (string)$l10n_files->t("Invalid name, '\\', '/', '<', '>', ':', '\"', '|', '?' and '*' are not allowed."));
    OCP\JSON::error($result);
    exit();
}

if(!\OC\Files\Filesystem::file_exists($dir . '/')) {
    $result['data'] = array('message' => (string)$l10n_files->t('The target folder has been moved or deleted.'),
                            'code' => 'targetnotfound');
    OCP\JSON::error($result);
    exit();
}

// Add the extension only if padname doesn’t contain it
if(substr($padname, -strlen(".$ext")) != ".$ext") {
    $filename = "$padname.$ext";
}
else {
    $filename = $padname;
}

$target = $dir . "/" . $filename;

if(\OC\Files\Filesystem::file_exists($target)) {
    $result['data'] = array('message' => (string)$l10n_files->t('The name %s is already used in the folder %s. Please choose a different name.', [$filename, $dir]));
    OCP\JSON::error($result);
    exit();
}

$content = sprintf("[InternetShortcut]\nURL=%s", $url);

if(\OC\Files\Filesystem::file_put_contents($target, $content)) {
    $meta = \OC\Files\Filesystem::getFileInfo($target);
    OCP\JSON::success(array('data' => \OCA\Files\Helper::formatFileInfo($meta)));
    exit();
}

OCP\JSON::error(array('data' => array( 'message' => $l10n_files->t('Error when creating the file'))));
