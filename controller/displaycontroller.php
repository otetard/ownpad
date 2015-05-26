<?php
/**
 * ownCloud - OwnPad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2015
 */

namespace OCA\OwnPad\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

class DisplayController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct($AppName, IRequest $request, IURLGenerator $urlGenerator) {
		parent::__construct($AppName, $request);
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function showPad($file, $dir) {
		/* Retrieve file content to find pad’s URL */		
		$content = \OC\Files\Filesystem::file_get_contents($dir."/".$file);
		preg_match('/URL=(.*)$/', $content, $matches);
		$url = $matches[1];		
		$title = $file;
		
		$params = [
			'urlGenerator' => $this->urlGenerator,
			'url' => $url,
			'title' => $title,
		];
		$response = new TemplateResponse($this->appName, 'viewer', $params, 'blank');

		return $response;
	}
}
