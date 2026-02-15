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

use OCP\IConfig;
use OCP\IUserSession;

class OwnpadService {
	private $eplHost;
	private $eplHostApi;

	private $eplApiKey = "";
	private $eplEnableOIDC = false;
	private $eplClientId = "";
	private $eplClientSecret = "";

	public const EPL_API_VERSION = '1.2.11';
	public const EPL_CODE_OK = 0;
	public const EPL_CODE_INVALID_PARAMETERS = 1;
	public const EPL_CODE_INTERNAL_ERROR = 2;
	public const EPL_CODE_INVALID_FUNCTION = 3;
	public const EPL_CODE_INVALID_API_KEY = 4;

	public function __construct(
		private IConfig $config,
		private IUserSession $userSession
	) {
		$this->config = $config;
		$this->userSession = $userSession;

		if($this->config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no') !== 'no' and
		   $this->config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no') !== 'no') {
			$this->eplHost = $this->config->getAppValue('ownpad', 'ownpad_etherpad_host', '');
			$this->eplHostApi = $this->eplHost . "/api";

			if ($this->config->getAppValue('ownpad', 'ownpad_etherpad_enable_oauth', 'no') === 'no') {
				$this->eplApiKey = $this->config->getAppValue('ownpad', 'ownpad_etherpad_apikey', '');
			} else {
				$this->eplEnableOIDC = true;
				$this->eplClientId = $this->config->getAppValue('ownpad', 'ownpad_etherpad_client_id', '');
				$this->eplClientSecret = $this->config->getAppValue('ownpad', 'ownpad_etherpad_client_secret', '');
			}
		}
	}

	public function create($dir, $padname, $type, $protected) {
		// Generate a random pad name
		$token = \OC::$server->getSecureRandom()->generate(rand(32, 50), \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);

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
		} elseif($type === "etherpad") {
			$padID = $token;

			$config = \OC::$server->getConfig();
			if($config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no') !== 'no' and $config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no') !== 'no') {
				try {
					if($protected === true) {
						// Create a protected (group) pad via API
						$group = $this->etherpadCallApi('createGroup');
						$groupPad = $this->etherpadCallApi('createGroupPad', ["groupID" => $group->groupID, "padName" => $token]);
						$padID = $groupPad->padID;
					} else {
						// Create a public pad via API
						$this->etherpadCallApi("createPad", ["padID" => $token]);
					}
				} catch(Exception $e) {
					throw new OwnpadException($l10n->t('Unable to communicate with Etherpad API due to the following error: “%s”.', [$e->getMessage()]));
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
		} catch(\OCP\Files\InvalidPathException $ex) {
			throw new OwnpadException($l10n_files->t("Invalid name, '\\', '/', '<', '>', ':', '\"', '|', '?' and '*' are not allowed."));
		}

		if(!\OC\Files\Filesystem::file_exists($dir . '/')) {
			throw new OwnpadException($l10n_files->t('The target folder has been moved or deleted.'));
		}

		// Add the extension only if padname doesn’t contain it
		if(substr($padname, -strlen(".$ext")) !== ".$ext") {
			$filename = "$padname.$ext";
		} else {
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

	public function parseOwnpadContent($file, $content, bool $publicMode = false, string $publicShareToken = '', bool $readOnly = false) {
		$l10n = \OC::$server->getL10N('ownpad');

		if (preg_match('/URL=(.*)$/', (string)$content, $matches) !== 1 || !isset($matches[1])) {
			throw new OwnpadException($l10n->t('Cannot parse URL from the selected pad file.'));
		}
		$url = trim($matches[1]);
		$decodedUrl = urldecode($url);

		$eplHostApi = $this->config->getAppValue('ownpad', 'ownpad_etherpad_host', '');
		$eplHostApi = rtrim($eplHostApi, '/');
		$protectedPadRegex = sprintf('/%s\/p\/(g\.\w{16})\\$([^\/]+)$/', preg_quote($eplHostApi, '/'));
		$match = preg_match($protectedPadRegex, $decodedUrl, $matches);

		/*
		 * We are facing a “protected” pad. Call for Etherpad API to
		 * create the session and then properly configure the cookie.
		 */
		if($match) {
			$groupID = $matches[1];
			$padID = $matches[1] . '$' . $matches[2];

			if($publicMode === true) {
				if ($this->config->getAppValue('ownpad', 'ownpad_etherpad_public_enable', 'no') === 'no') {
					throw new OwnpadException($l10n->t('You are not allowed to open this pad.'));
				}

				if ($publicShareToken === '') {
					throw new OwnpadException($l10n->t('You are not allowed to open this pad.'));
				}

				$authorMapper = 'public:' . hash('sha256', $publicShareToken . '|' . $padID);
				$authorName = $l10n->t('Public share guest');
				$sessionValidUntil = time() + 900;
			} else {
				$username = $this->userSession->getUser()->getUID();
				$displayName = $this->userSession->getUser()->getDisplayName();
				$authorMapper = $username;
				$authorName = $displayName;
				$sessionValidUntil = time() + 3600;
			}

			$author = $this->etherpadCallApi("createAuthorIfNotExistsFor", ["authorMapper" => $authorMapper, "name" => $authorName]);
			$session = $this->etherpadCallApi('createSession', ["groupID" => $groupID, "authorID" => $author->authorID, "validUntil" => $sessionValidUntil]);

			$cookieDomain = $this->config->getAppValue('ownpad', 'ownpad_etherpad_cookie_domain', '');
			setcookie('sessionID', $session->sessionID, 0, '/', $cookieDomain, true, false);

			if ($readOnly) {
				$url = $this->getReadOnlyPadUrl($padID);
			}
		} elseif ($readOnly) {
			$padRegex = sprintf('/^%s\/p\/([^\/]+)$/', preg_quote($eplHostApi, '/'));
			if (preg_match($padRegex, $decodedUrl, $matches) === 1 && isset($matches[1])) {
				$url = $this->getReadOnlyPadUrl($matches[1]);
			}
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
		$fileending = $split[count($split) - 1];

		// Get Host-URL
		if($fileending === "calc") {
			$host = \OC::$server->getConfig()->getAppValue('ownpad', 'ownpad_ethercalc_host', false);
		} elseif($fileending === "pad") {
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
		} elseif($fileending === "pad") {
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

	private function getReadOnlyPadUrl(string $padID): string {
		$l10n = \OC::$server->getL10N('ownpad');
		$host = rtrim($this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''), '/');

		// Already a read-only pad ID: no API conversion needed.
		if (strpos($padID, 'r.') === 0) {
			return sprintf('%s/p/%s', $host, $padID);
		}

		if ($this->config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no') === 'no') {
			throw new OwnpadException($l10n->t('Read-only share mode requires Etherpad API support.'));
		}

		try {
			$readOnly = $this->etherpadCallApi('getReadOnlyID', ['padID' => $padID]);
		} catch (Exception $e) {
			throw new OwnpadException($l10n->t('Unable to switch to read-only mode due to the following error: “%s”.', [$e->getMessage()]));
		}

		if (!isset($readOnly->readOnlyID) || !is_string($readOnly->readOnlyID) || $readOnly->readOnlyID === '') {
			throw new OwnpadException($l10n->t('Unable to switch to read-only mode because Etherpad did not return a read-only ID.'));
		}

		return sprintf('%s/p/%s', $host, $readOnly->readOnlyID);
	}

	public function testEtherpadToken() {
		try {
			return $this->etherpadCallApi('checkToken');
		} catch(Exception) {
			$l10n = \OC::$server->getL10N('ownpad');
			throw new OwnpadException($l10n->t('Invalid authentication credentials'));
		}
	}

	/**
	 * Main entrypoint to call Etherpad API.
	 *
	 * This code is heavily inspired from tomnomnom’s PHP Etherpad
	 * client. Original source code is available here:
	 * https://github.com/tomnomnom/etherpad-lite-client
	 */
	private function etherpadCallApi($function, array $arguments = array(), $method = 'GET') {
		$params = array("http" => array("method" => $method, "ignore_errors" => true, "header" => "Content-Type:application/x-www-form-urlencoded"));

		if ($this->eplEnableOIDC) {
			$token = $this->getBearerToken();
			$params["http"]["header"] .= "\r\nAuthorization: Bearer {$token}";
		} else {
			$arguments["apikey"] = $this->eplApiKey;
		}

		$arguments = array_map(array($this, "etherpadConvertBools"), $arguments);
		$arguments = http_build_query($arguments, "", "&");
		$url = $this->eplHostApi."/".self::EPL_API_VERSION."/".$function;

		if ($method !== "POST") {
			$url .= "?".$arguments;
		} elseif ($method === "POST") {
			$params["http"]["content"] = $arguments;
		}

		$context = stream_context_create($params);
		$fp = fopen($url, "rb", false, $context);
		$result = $fp ? stream_get_contents($fp) : null;

		if(!$result) {
			throw new \UnexpectedValueException("Empty or No Response from the server");
		}

		$result = json_decode($result);

		if ($result === null) {
			throw new \UnexpectedValueException("JSON response could not be decoded");
		}

		if (!isset($result->code)) {
			throw new \RuntimeException("API response has no code");
		}
		if (!isset($result->message)) {
			throw new \RuntimeException("API response has no message");
		}
		if (!isset($result->data)) {
			$result->data = null;
		}

		switch ($result->code) {
			case self::EPL_CODE_OK:
				return $result->data;
			case self::EPL_CODE_INVALID_PARAMETERS:
			case self::EPL_CODE_INVALID_API_KEY:
				throw new \InvalidArgumentException($result->message);
			case self::EPL_CODE_INTERNAL_ERROR:
				throw new \RuntimeException($result->message);
			case self::EPL_CODE_INVALID_FUNCTION:
				throw new \BadFunctionCallException($result->message);
			default:
				throw new \RuntimeException("An unexpected error occurred whilst handling the response");
		}
	}

	protected function etherpadConvertBools($candidate) {
		if (is_bool($candidate)) {
			return $candidate? "true" : "false";
		}
		return $candidate;
	}

	private function getBearerToken() {
		$oidcUrl = $this->eplHost . "/oidc/token";
		$data = [
			"resource" => $this->eplHost . "/oidc/resource",
			"grant_type" => "client_credentials",
			"client_id" => $this->eplClientId,
			"client_secret" => $this->eplClientSecret,
		];
		$options = ["http" => ["method" => "POST",
			"ignore_errors" => true,
			"header" => "Content-Type:application/x-www-form-urlencoded",
			"content" => http_build_query($data)
		]];
		$context = stream_context_create($options);
		$result = file_get_contents($oidcUrl, false, $context);

		if ($result === false) {
			$l10n = \OC::$server->getL10N('ownpad');
			throw new OwnpadException($l10n->t('Unable to authenticate to Etherpad API'));
		}

		$result = json_decode($result);

		return $result->access_token;
	}
}
