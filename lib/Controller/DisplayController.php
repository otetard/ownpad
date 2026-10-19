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

namespace OCA\Ownpad\Controller;

use OCA\Ownpad\Service\OwnpadException;
use OCA\Ownpad\Service\OwnpadService;

use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class DisplayController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IAppManager */
	private $appManager;

	/** @var OwnpadService */
	private $ownpadService;

	/** @var IUserSession */
	private $userSession;

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
		OwnpadService $ownpadService,
		IUserSession $userSession
	) {
		parent::__construct($AppName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->appManager = $appManager;
		$this->ownpadService = $ownpadService;
		$this->userSession = $userSession;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse|RedirectResponse
	 */
	public function showPad($file) {
		$normalizedFile = is_string($file) ? trim($file) : '';

		// Viewer may pass DAV URLs instead of filesystem paths.
		if (preg_match('#^https?://#i', $normalizedFile)) {
			$parsedPath = parse_url($normalizedFile, PHP_URL_PATH);
			$parsedPath = is_string($parsedPath) ? urldecode($parsedPath) : '';

			if (preg_match('#/remote\.php/dav/files/[^/]+/(.+)$#', $parsedPath, $remoteMatches)) {
				$normalizedFile = '/' . ltrim($remoteMatches[1], '/');
			} elseif (preg_match('#/public\.php/dav/files/([^/]+)/(.*)$#', $parsedPath, $publicMatches)) {
				$token = $publicMatches[1];
				$sharedFile = ltrim($publicMatches[2], '/');
				$publicUrl = $this->urlGenerator->linkToRoute('ownpad.publicDisplay.showPad', ['token' => $token]);
				if ($sharedFile !== '') {
					$publicUrl .= '?file=' . rawurlencode($sharedFile);
				}
				return new RedirectResponse($publicUrl);
			}
		}

		if ($normalizedFile !== '' && $normalizedFile[0] !== '/') {
			$normalizedFile = '/' . $normalizedFile;
		}

		$params = [
			'urlGenerator' => $this->urlGenerator,
			'ownpad_version' => $this->appManager->getAppVersion('ownpad'),
			'title' => $normalizedFile,
		];

		// Accessing /apps/ownpad without a concrete file should not trigger a 500.
		if ($normalizedFile === '' || $normalizedFile === '/') {
			if ($this->userSession->getUser() !== null) {
				return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index'));
			}
			$params["error"] = "No pad file selected.";
			return new TemplateResponse($this->appName, 'noviewer', $params, 'blank');
		}

		\OC_Util::setupFS();

		/* Retrieve file content to find pad’s URL */
		$content = \OC\Files\Filesystem::file_get_contents($normalizedFile);
		if ($content === false) {
			$params["error"] = "Cannot open selected pad file.";
			return new TemplateResponse($this->appName, 'noviewer', $params, 'blank');
		}

		try {
			$params['url'] = $this->ownpadService->parseOwnpadContent($normalizedFile, $content);
			return new TemplateResponse($this->appName, 'viewer', $params, 'blank');
		} catch(OwnpadException $e) {
			$params["error"] = $e->getMessage();
			return new TemplateResponse($this->appName, 'noviewer', $params, 'blank');
		}
	}
}
