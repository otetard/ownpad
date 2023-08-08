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

use OCP\IConfig;
use OCP\IUserSession;
use OCP\IL10N;

use EtherpadLite\Client;
use Exception;

class OwnpadService {
    /** @var IConfig */
    private $config;

    /** @var IUserSession */
    private $userSession;

    /** @var Client */
    private $eplInstance;

    public function __construct(
        IConfig $config,
        IUserSession $userSession,
    ) {
        $this->config = $config;
        $this->userSession = $userSession;

        if($this->config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no') !== 'no' AND
           $this->config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no') !== 'no')
        {
            $eplHost = $this->config->getAppValue('ownpad', 'ownpad_etherpad_host', '');
            $eplApiKey = $this->config->getAppValue('ownpad', 'ownpad_etherpad_apikey', '');
            $this->eplInstance = new Client($eplApiKey, $eplHost . "/api");
        }
    }

    public function create($dir, $padname, $type, $protected) {
        // Generate a random pad name
        $token = \OC::$server->getSecureRandom()->generate(rand(32, 64), \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);

        $l10n = \OC::$server->getL10N('ownpad');
        $l10n_files = \OC::$server->getL10N('files');

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

            $config = \OC::$server->getConfig();
            if($config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no') !== 'no' AND $config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no') !== 'no') {
                try {
                    if($protected === true) {
                        // Create a protected (group) pad via API
                        $group = $this->eplInstance->createGroup();
                        $groupPad = $this->eplInstance->createGroupPad($group->groupID, $token);
                        $padID = $groupPad->padID;
                    }
                    else {
                        // Create a public pad via API
                        $this->eplInstance->createPad($token);
                    }
                }
                catch(Exception $e) {
                    throw new OwnpadException($l10n->t('Unable to communicate with Etherpad API.'));
                }
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

    public function parseOwnpadContent($file, $content) {
        $l10n = \OC::$server->getL10N('ownpad');

        preg_match('/URL=(.*)$/', $content, $matches);
        $url = $matches[1];

        $eplHost = $this->config->getAppValue('ownpad', 'ownpad_etherpad_host', '');
        $eplHost = rtrim($eplHost, '/');
        $protectedPadRegex = sprintf('/%s\/p\/(g\.\w{16})\\$(.*)$/', preg_quote($eplHost, '/'));
        $match = preg_match($protectedPadRegex, $url, $matches);

        /*
         * We are facing a “protected” pad. Call for Etherpad API to
         * create the session and then properly configure the cookie.
         */
        if($match) {
            $groupID = $matches[1];

            $username = $this->userSession->getUser()->getUID();
            $displayName = $this->userSession->getUser()->getDisplayName();
            $author = $this->eplInstance->createAuthorIfNotExistsFor($username, $displayName);

            $session = $this->eplInstance->createSession($groupID, $author->authorID, time() + 3600);

            $cookieDomain = $this->config->getAppValue('ownpad', 'ownpad_etherpad_cookie_domain', '');
            setcookie('sessionID', $session->sessionID, 0, '/', $cookieDomain, true, false);
        }

        /*
         * Not totally sure that this is the right way to proceed…
         *
         * First we decode the URL (to avoid double encode), then we
         * replace spaces with underscore (as they are converted as
         * such by Etherpad), then we encode the URL properly (and we
         * avoid to urlencode() the protocol scheme).
         *
         * Magic urlencode() function was stolen from this answer on
         * StackOverflow: <http://stackoverflow.com/a/7974253>.
         */
        $url = urldecode($url);
        $url = str_replace(' ', '_', $url);
        $url = preg_replace_callback('#://([^/]+)/(=)?([^?]+)#', function ($match) {
            return '://' . $match[1] . '/' . $match[2] . join('/', array_map('rawurlencode', explode('/', $match[3])));
        }, $url);

        // Check for valid URL
        // Get File-Ending
        $split = explode(".", $file);
        $fileending = $split[count($split)-1];

        // Get Host-URL
        if($fileending === "calc") {
            $host = \OC::$server->getConfig()->getAppValue('ownpad', 'ownpad_ethercalc_host', false);
        }
        elseif($fileending === "pad") {
            $host = \OC::$server->getConfig()->getAppValue('ownpad', 'ownpad_etherpad_host', false);
        }

        if(substr($host, -1, 1) !== '/') {
            $host .= '/';
        }

        // Escape all RegEx-Characters
        $hostreg = preg_quote($host);
        // Escape all Slashes in URL to use in RegEx
        $hostreg = preg_replace("/\//", "\/", $host);

        // Final Regex-String
        if($fileending === "calc") {
            /*
             * Ethercalc documents with “multisheet” support starts
             * with a `=`.
             */
            $regex = "/^".$hostreg."=?[^\/]+$/";
        }
        elseif($fileending === "pad") {
            /*
             * Etherpad documents can contain special characters, for
             * “protected pads” for example.
             */
            $regex = "/^".$hostreg."p\/[^\/]+$/";
        }

        if (preg_match($regex, $url) !== 1) {
            throw new OwnpadException($l10n->t('URL in your Etherpad/Ethercalc document does not match the allowed server'));
        }

        return $url;
    }
}
