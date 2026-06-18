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
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

class PublicDisplayController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IConfig */
	private $config;

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
			return new DataResponse(['message' => $this->l->t('Share not found'), Http::STATUS_NOT_FOUND]);
		}

		try {
			$node = $share->getNode();
		} catch (NotFoundException $e) {
			return new DataResponse(['message' => $this->l->t('Share not found'), Http::STATUS_NOT_FOUND]);
		}

		if ($node instanceof Folder) {
			return;
		}

		$content = $node->getContent();
		$file = $node->getName();
		$fileId = $node->getId();

		$params = [
			'urlGenerator' => $this->urlGenerator,
			'ownpad_version' => $this->appManager->getAppVersion('ownpad'),
			'title' => $file,
		];

		try {
			$params['url'] = $this->ownpadService->parseOwnpadContent(
				$file,
				$content,
				true,
				$fileId,
				function (string $newContent) use ($node): bool {
					$node->setContent($newContent);
					return true;
				},
			);
			return new TemplateResponse($this->appName, 'viewer', $params, 'blank');
		} catch(OwnpadException $e) {
			$params["error"] = $e->getMessage();
			return new TemplateResponse($this->appName, 'noviewer', $params, 'blank');
		}

	}
}
