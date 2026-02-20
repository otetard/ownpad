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
use Psr\Log\LoggerInterface;

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
		private IUserSession $userSession,
		private LoggerInterface $logger
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
			if ($meta) {
				$this->storePadUrlForFileId((int)$meta->getId(), $url);
			}
			return \OCA\Files\Helper::formatFileInfo($meta);
		}

		throw new OwnpadException($l10n_files->t('Error when creating the file'));
	}

	public function parseOwnpadContent($file, $content, bool $publicMode = false) {
		$l10n = \OC::$server->getL10N('ownpad');

		preg_match('/URL=(.*)$/', $content, $matches);
		$url = $matches[1];

		$eplHostApi = $this->config->getAppValue('ownpad', 'ownpad_etherpad_host', '');
		$eplHostApi = rtrim($eplHostApi, '/');
		$protectedPadRegex = sprintf('/%s\/p\/(g\.\w{16})\\$(.*)$/', preg_quote($eplHostApi, '/'));
		$match = preg_match($protectedPadRegex, $url, $matches);

		/*
		 * We are facing a “protected” pad. Call for Etherpad API to
		 * create the session and then properly configure the cookie.
		 */
		if($match) {
			if($publicMode === true) {
				throw new OwnpadException($l10n->t('You are not allowed to open this pad.'));
			}

			$groupID = $matches[1];

			$username = $this->userSession->getUser()->getUID();
			$displayName = $this->userSession->getUser()->getDisplayName();
			$author = $this->etherpadCallApi("createAuthorIfNotExistsFor", ["authorMapper" => $username, "name" => $displayName]);
			$session = $this->etherpadCallApi('createSession', ["groupID" => $groupID, "authorID" => $author->authorID, "validUntil" => time() + 3600]);

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

	public function testEtherpadToken() {
		try {
			return $this->etherpadCallApi('checkToken');
		} catch(Exception) {
			$l10n = \OC::$server->getL10N('ownpad');
			throw new OwnpadException($l10n->t('Invalid authentication credentials'));
		}
	}

	public function deletePadFromUrl(string $url, ?int $fileId = null): void {
		if (!$this->isDeleteOnTrashEnabled()) {
			return;
		}
		if ($this->config->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no') === 'no') {
			return;
		}
		if ($this->config->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no') === 'no') {
			return;
		}

		$host = rtrim($this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''), '/');
		if ($host === '') {
			return;
		}

		$url = trim($url);
		$pattern = '#^' . preg_quote($host, '#') . '/p/([^/]+)$#';
		if (!preg_match($pattern, $url, $matches)) {
			$this->logger->warning('Skipping Etherpad pad deletion because URL format is invalid.', [
				'app' => 'ownpad',
				'fileId' => $fileId,
				'url' => $url,
			]);
			return;
		}

		$padId = $matches[1];
		try {
			$this->etherpadCallApi('deletePad', ['padID' => $padId]);
		} catch (Exception $e) {
			$this->logger->warning('Failed to delete Etherpad pad while moving file to trash.', [
				'app' => 'ownpad',
				'fileId' => $fileId,
				'padId' => $padId,
				'exception' => $e,
			]);
		}
	}

	public function storePadUrlForFileId(int $fileId, string $url): void {
		if ($fileId <= 0 || $url === '') {
			return;
		}
		$this->config->setAppValue('ownpad', 'pad_url_' . $fileId, $url);
	}

	public function getPadUrlForFileId(int $fileId): ?string {
		if ($fileId <= 0) {
			return null;
		}
		$value = $this->config->getAppValue('ownpad', 'pad_url_' . $fileId, '');
		return $value === '' ? null : $value;
	}

	public function deletePadUrlForFileId(int $fileId): void {
		if ($fileId <= 0) {
			return;
		}
		$this->config->deleteAppValue('ownpad', 'pad_url_' . $fileId);
	}

	public function isDeleteOnTrashEnabled(): bool {
		return $this->config->getAppValue('ownpad', 'ownpad_delete_on_trash', 'no') === 'yes';
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
