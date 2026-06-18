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

use OCP\Files\FileInfo;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
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

	private const LEGACY_MODE_NONE = 'none';
	private const LEGACY_MODE_UNPROTECTED = 'unprotected';
	private const LEGACY_MODE_ALL = 'all';

	public function __construct(
		private IConfig $config,
		private IUserSession $userSession,
		private ISecureRandom $secureRandom,
		private PadBindingService $padBindingService,
		private LoggerInterface $logger,
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
		$token = $this->secureRandom->generate(rand(32, 50), ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);

		$l10n = \OC::$server->getL10N('ownpad');
		$l10n_files = \OC::$server->getL10N('files');

		$originToken = null;

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
			$originToken = $this->generateOriginToken();
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
		if ($originToken !== null) {
			$content .= sprintf("\nOwnpadToken=%s", $originToken);
		}

		if(\OC\Files\Filesystem::file_put_contents($target, $content)) {
			$meta = \OC\Files\Filesystem::getFileInfo($target);

			if ($originToken !== null) {
				if (!($meta instanceof FileInfo)) {
					\OC\Files\Filesystem::unlink($target);
					throw new OwnpadException($l10n_files->t('Error when creating the file'));
				}

				try {
					$createdByUid = $this->userSession->getUser()?->getUID();
					$ownerUid = $meta->getOwner()?->getUID() ?? $createdByUid;
					$baseUrl = rtrim((string)$host, '/');
					$padId = $this->extractPadIdFromUrl((string)$url, $baseUrl);
					if ($padId === null || $padId === '') {
						throw new OwnpadException($l10n->t('Invalid Etherpad URL in pad file.'));
					}

					$this->padBindingService->create(
						$meta->getId(),
						$padId,
						$baseUrl,
						$originToken,
						$createdByUid,
						$ownerUid,
						$protected === true,
					);
				} catch (\Throwable $e) {
					\OC\Files\Filesystem::unlink($target);
					$this->logger->error('Failed to create pad binding for newly created file', [
						'app' => 'ownpad',
						'exception' => $e,
					]);
					throw new OwnpadException($l10n->t('Unable to secure pad mapping. Please try again.'));
				}
			}

			return \OCA\Files\Helper::formatFileInfo($meta);
		}

		throw new OwnpadException($l10n_files->t('Error when creating the file'));
	}

	public function parseOwnpadContent($file, $content, bool $publicMode = false, ?int $fileId = null, ?callable $contentUpdater = null) {
		$l10n = \OC::$server->getL10N('ownpad');

		$url = $this->extractShortcutValue($content, 'URL');
		if ($url === null || $url === '') {
			throw new OwnpadException($l10n->t('Invalid pad file: missing URL field.'));
		}
		$originToken = $this->extractShortcutValue($content, 'OwnpadToken');

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
		$fileending = pathinfo((string)$file, PATHINFO_EXTENSION);

		// Get Host-URL
		if($fileending === "calc") {
			$host = \OC::$server->getConfig()->getAppValue('ownpad', 'ownpad_ethercalc_host', false);
		} elseif($fileending === "pad") {
			$host = \OC::$server->getConfig()->getAppValue('ownpad', 'ownpad_etherpad_host', false);
		}

		if (!$this->isAllowedOwnpadUrl((string)$url, (string)$host, (string)$fileending)) {
			throw new OwnpadException($l10n->t('URL in your Etherpad/Ethercalc document does not match the allowed server'));
		}

		if ($fileending === 'pad') {
			$this->validatePadBinding(
				(string)$file,
				(string)$content,
				(string)$url,
				$originToken,
				$fileId,
				$publicMode,
				$contentUpdater,
			);
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

	private function validatePadBinding(
		string $file,
		string $content,
		string $url,
		?string $originToken,
		?int $fileId,
		bool $publicMode,
		?callable $contentUpdater,
	): void {
		$l10n = \OC::$server->getL10N('ownpad');

		if ($fileId === null) {
			throw new OwnpadException($l10n->t('Unable to validate pad ownership.'));
		}

		$baseUrl = rtrim((string)$this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''), '/');
		$padId = $this->extractPadIdFromUrl($url, $baseUrl);
		if ($padId === null || $padId === '') {
			throw new OwnpadException($l10n->t('Invalid Etherpad URL in pad file.'));
		}

		$isProtectedPad = preg_match('/^g\.[A-Za-z0-9]{16}\$.+$/', $padId) === 1;

		$binding = $this->padBindingService->findActiveByFileId($fileId);
		if ($binding !== null) {
			if ($originToken === null || $originToken === '') {
				throw new OwnpadException($l10n->t('This pad is missing a required ownership token.'));
			}

			if (!hash_equals((string)$binding['origin_token'], (string)$originToken)) {
				throw new OwnpadException($l10n->t('Pad ownership verification failed.'));
			}

			if ((string)$binding['pad_id'] !== $padId || (string)$binding['base_url'] !== $baseUrl) {
				throw new OwnpadException($l10n->t('Pad binding does not match this file.'));
			}

			return;
		}

		$existingPadBinding = $this->padBindingService->findActiveByPad($baseUrl, $padId);
		if ($existingPadBinding !== null) {
			throw new OwnpadException($l10n->t('This pad is already bound to another file and cannot be opened here.'));
		}

		if (($originToken === null || $originToken === '') && !$this->canOpenLegacyWithoutToken($isProtectedPad)) {
			throw new OwnpadException($l10n->t('This legacy pad format is not allowed by the current security policy.'));
		}

		if ($originToken === null || $originToken === '') {
			$originToken = $this->generateOriginToken();
			$updatedContent = $this->appendShortcutField($content, 'OwnpadToken', $originToken);
			$persisted = false;

			if ($contentUpdater !== null) {
				try {
					$persisted = (bool)$contentUpdater($updatedContent);
				} catch (\Throwable $e) {
					$this->logger->warning('Unable to persist generated OwnpadToken for legacy pad', [
						'app' => 'ownpad',
						'file' => $file,
						'fileId' => $fileId,
						'exception' => $e,
					]);
				}
			}

			if (!$persisted) {
				if ($isProtectedPad || $publicMode) {
					throw new OwnpadException($l10n->t('Unable to migrate this pad to the secure format.'));
				}

				$this->logger->warning('Opening legacy pad without token backfill because file update failed', [
					'app' => 'ownpad',
					'file' => $file,
					'fileId' => $fileId,
				]);
				return;
			}
		}

		$createdByUid = $this->userSession->getUser()?->getUID();
		try {
			$this->padBindingService->create(
				$fileId,
				$padId,
				$baseUrl,
				$originToken,
				$createdByUid,
				$createdByUid,
				$isProtectedPad,
			);
		} catch (\Throwable $e) {
			$this->logger->error('Failed to create pad binding during open', [
				'app' => 'ownpad',
				'file' => $file,
				'fileId' => $fileId,
				'exception' => $e,
			]);

			$existingPadBinding = $this->padBindingService->findActiveByPad($baseUrl, $padId);
			if ($existingPadBinding !== null && (int)$existingPadBinding['file_id'] !== $fileId) {
				throw new OwnpadException($l10n->t('This pad is already bound to another file and cannot be opened here.'));
			}

			$raceBinding = $this->padBindingService->findActiveByFileId($fileId);
			if ($raceBinding !== null
				&& (string)$raceBinding['pad_id'] === $padId
				&& (string)$raceBinding['base_url'] === $baseUrl
				&& hash_equals((string)$raceBinding['origin_token'], (string)$originToken)) {
				return;
			}

			throw new OwnpadException($l10n->t('Unable to create pad ownership mapping.'));
		}
	}

	private function canOpenLegacyWithoutToken(bool $isProtectedPad): bool {
		$mode = strtolower((string)$this->config->getAppValue('ownpad', 'ownpad_legacy_token_mode', self::LEGACY_MODE_ALL));

		return match ($mode) {
			self::LEGACY_MODE_ALL => true,
			self::LEGACY_MODE_NONE => false,
			default => !$isProtectedPad,
		};
	}

	private function generateOriginToken(): string {
		return $this->secureRandom->generate(64, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
	}

	private function extractShortcutValue(string $content, string $key): ?string {
		$regex = sprintf('/^%s=(.*)$/mi', preg_quote($key, '/'));
		if (preg_match($regex, $content, $matches) !== 1) {
			return null;
		}

		return trim((string)$matches[1]);
	}

	private function appendShortcutField(string $content, string $key, string $value): string {
		$lines = preg_split('/\r\n|\r|\n/', trim($content));
		$updated = [];
		$replaced = false;

		foreach ($lines as $line) {
			if (str_starts_with((string)$line, $key . '=')) {
				$updated[] = $key . '=' . $value;
				$replaced = true;
				continue;
			}
			$updated[] = $line;
		}

		if (!$replaced) {
			$updated[] = $key . '=' . $value;
		}

		return implode("\n", $updated);
	}

	private function extractPadIdFromUrl(string $url, string $baseUrl): ?string {
		if (!$this->isAllowedOwnpadUrl($url, $baseUrl, 'pad')) {
			return null;
		}

		$urlPath = parse_url($url, PHP_URL_PATH);
		if (!is_string($urlPath) || $urlPath === '') {
			return null;
		}

		$configuredPath = parse_url($baseUrl, PHP_URL_PATH);
		$configuredPath = is_string($configuredPath) ? rtrim($configuredPath, '/') : '';

		$prefixPath = $configuredPath . '/p/';
		if (!str_starts_with($urlPath, $prefixPath)) {
			return null;
		}

		$padId = substr($urlPath, strlen($prefixPath));
		if ($padId === false || $padId === '') {
			return null;
		}

		return rawurldecode($padId);
	}

	private function isAllowedOwnpadUrl(string $url, string $configuredHost, string $fileEnding): bool {
		$configured = parse_url(rtrim($configuredHost, '/'));
		$actual = parse_url($url);
		if ($configured === false || $actual === false) {
			return false;
		}

		$configuredScheme = strtolower((string)($configured['scheme'] ?? ''));
		$actualScheme = strtolower((string)($actual['scheme'] ?? ''));
		$configuredHostName = strtolower((string)($configured['host'] ?? ''));
		$actualHostName = strtolower((string)($actual['host'] ?? ''));

		if ($configuredScheme === '' || $configuredHostName === '') {
			return false;
		}
		if ($configuredScheme !== $actualScheme || $configuredHostName !== $actualHostName) {
			return false;
		}
		if ($this->normalizeUrlPort($configuredScheme, $configured['port'] ?? null) !== $this->normalizeUrlPort($actualScheme, $actual['port'] ?? null)) {
			return false;
		}

		$configuredPath = isset($configured['path']) ? rtrim((string)$configured['path'], '/') : '';
		$actualPath = (string)($actual['path'] ?? '');
		if ($fileEnding === 'calc') {
			return preg_match('/^' . preg_quote($configuredPath . '/', '/') . '=?[^\/]+$/', $actualPath) === 1;
		}
		if ($fileEnding === 'pad') {
			return preg_match('/^' . preg_quote($configuredPath . '/p/', '/') . '[^\/]+$/', $actualPath) === 1;
		}

		return false;
	}

	private function normalizeUrlPort(string $scheme, mixed $port): int {
		if (is_int($port)) {
			return $port;
		}

		return $scheme === 'https' ? 443 : 80;
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
