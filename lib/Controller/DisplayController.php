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

use OCA\Ownpad\Service\OwnpadService;
use OCA\Ownpad\Service\OwnpadException;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\App\IAppManager;

class DisplayController extends Controller {

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var IAppManager */
    private $appManager;

    /** @var OwnpadService */
    private $ownpadService;

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
        OwnpadService $ownpadService
    ) {
        parent::__construct($AppName, $request);
        $this->urlGenerator = $urlGenerator;
        $this->appManager = $appManager;
        $this->ownpadService = $ownpadService;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     *
     * @return TemplateResponse
     */
    public function showPad($file): TemplateResponse {
        \OC_Util::setupFS();

        /* Retrieve file content to find pad’s URL */
        $content = \OC\Files\Filesystem::file_get_contents($file);

        $params = [
            'urlGenerator' => $this->urlGenerator,
            'ownpad_version' => $this->appManager->getAppVersion('ownpad'),
            'title' => $file,
        ];

        try {
            $params['url'] = $this->ownpadService->parseOwnpadContent($file, $content);
            return new TemplateResponse($this->appName, 'viewer', $params, 'blank');
        }
        catch(OwnpadException $e) {
            return new TemplateResponse($this->appName, 'noviewer', $params, 'blank');
        }
    }
}
