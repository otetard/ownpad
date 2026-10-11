<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2023
 */

namespace OCA\Ownpad\Controller;

use OCA\Ownpad\Service\OwnpadException;
use OCA\Ownpad\Service\OwnpadService;

use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

class PublicDisplayController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IAppManager */
	private $appManager;

	/** @var IManager */
	private $shareManager;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		IAppManager $appManager,
		IManager $shareManager,
		OwnpadService $ownpadService
	) {
		parent::__construct($AppName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->appManager = $appManager;
		$this->shareManager = $shareManager;
		$this->ownpadService = $ownpadService;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function showPad($token) {
		try {
			$share = $this->shareManager->getShareByToken($token);
		} catch (ShareNotFound $e) {
			return new DataResponse(['message' => 'Share not found'], Http::STATUS_NOT_FOUND);
		}

		try {
			$node = $share->getNode();
		} catch (NotFoundException $e) {
			return new DataResponse(['message' => 'Share not found'], Http::STATUS_NOT_FOUND);
		}

		if ($node instanceof Folder) {
			$fileParam = $this->request->getParam('file', '');
			$fileParam = is_string($fileParam) ? $this->normalizeFileParam($fileParam, (string)$token) : '';

			if ($fileParam === '') {
				return new TemplateResponse($this->appName, 'noviewer', [
					'urlGenerator' => $this->urlGenerator,
					'ownpad_version' => $this->appManager->getAppVersion('ownpad'),
					'title' => '',
					'error' => 'No pad file selected.',
				], 'blank');
			}

			try {
				$node = $node->get($fileParam);
			} catch (NotFoundException $e) {
				return new DataResponse(['message' => 'Share not found'], Http::STATUS_NOT_FOUND);
			}
		}

		if (!($node instanceof File)) {
			return new DataResponse(['message' => 'Share not found'], Http::STATUS_NOT_FOUND);
		}

		$content = $node->getContent();
		$file = $node->getName();

		$params = [
			'urlGenerator' => $this->urlGenerator,
			'ownpad_version' => $this->appManager->getAppVersion('ownpad'),
			'title' => $file,
		];

		try {
			$permissions = (int)$share->getPermissions();
			$readOnly = ($permissions & Constants::PERMISSION_UPDATE) === 0;
			$params['url'] = $this->ownpadService->parseOwnpadContent($file, $content, true, (string)$token, $readOnly);
			return new TemplateResponse($this->appName, 'viewer', $params, 'blank');
		} catch(OwnpadException $e) {
			$params["error"] = $e->getMessage();
			return new TemplateResponse($this->appName, 'noviewer', $params, 'blank');
		}

	}

	private function normalizeFileParam(string $fileParam, string $token): string {
		$fileParam = trim($fileParam);
		$fileParam = urldecode($fileParam);

		if (preg_match('#^https?://#i', $fileParam)) {
			$path = parse_url($fileParam, PHP_URL_PATH);
			$path = is_string($path) ? urldecode($path) : '';

			if (preg_match('#/public\.php/dav/files/([^/]+)/(.+)$#', $path, $publicMatch)) {
				// Keep path relative to share root and only trust matching tokens.
				if ($publicMatch[1] === $token) {
					$fileParam = $publicMatch[2];
				} else {
					$fileParam = '';
				}
			} elseif (preg_match('#/remote\.php/dav/files/[^/]+/(.+)$#', $path, $remoteMatch)) {
				$fileParam = $remoteMatch[1];
			} else {
				$fileParam = ltrim($path, '/');
			}
		}

		$fileParam = str_replace('\\', '/', $fileParam);
		$fileParam = ltrim($fileParam, '/');

		if ($fileParam === '') {
			return '';
		}

		$segments = explode('/', $fileParam);
		$safeSegments = [];
		foreach ($segments as $segment) {
			if ($segment === '' || $segment === '.') {
				continue;
			}
			if ($segment === '..') {
				return '';
			}
			$safeSegments[] = $segment;
		}

		return implode('/', $safeSegments);
	}
}
