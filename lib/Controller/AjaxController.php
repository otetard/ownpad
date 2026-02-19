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

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\IRequest;

use OCA\Ownpad\Service\OwnpadException;
use OCA\Ownpad\Service\OwnpadService;
use OCA\Ownpad\Service\PadBindingBackfillService;

class AjaxController extends Controller {

	/** @var OwnpadService */
	private $service;

	/** @var PadBindingBackfillService */
	private $backfillService;

	public function __construct($appName, IRequest $request, OwnpadService $service, PadBindingBackfillService $backfillService) {
		parent::__construct($appName, $request);
		$this->service = $service;
		$this->backfillService = $backfillService;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getconfig() {
		$config = [];

		$appConfig = \OC::$server->getConfig();
		$config['ownpad_etherpad_enable'] = $appConfig->getAppValue('ownpad', 'ownpad_etherpad_enable', 'no');
		$config['ownpad_etherpad_public_enable'] = $appConfig->getAppValue('ownpad', 'ownpad_etherpad_public_enable', 'no');
		$config['ownpad_etherpad_useapi'] = $appConfig->getAppValue('ownpad', 'ownpad_etherpad_useapi', 'no');
		$config['ownpad_ethercalc_enable'] = $appConfig->getAppValue('ownpad', 'ownpad_ethercalc_enable', 'no');

		return new JSONResponse(["data" => $config]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function newpad($dir, $padname, $type, $protected) {
		\OC_Util::setupFS();

		$dir = isset($dir) ? '/'.trim($dir, '/\\') : '';
		$padname = isset($padname) ? trim($padname, '/\\') : '';
		$type = isset($type) ? trim($type, '/\\') : '';

		try {
			$data = $this->service->create($dir, $padname, $type, $protected);
			return new JSONResponse([
				'data' => $data,
				'status' => 'success',
			]);
		} catch(OwnpadException $e) {
			$message = [
				'data' => ['message' => $e->getMessage()],
				'status' => 'error',
			];
			return new JSONResponse($message, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function testetherpadtoken() {
		try {
			$this->service->testEtherpadToken();
			return new JSONResponse([
				'data' => null,
				'status' => 'success',
			]);
		} catch(OwnpadException $e) {
			$message = [
				'data' => ['message' => $e->getMessage()],
				'status' => 'error',
			];
			return new JSONResponse($message, Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * @AdminRequired
	 */
	public function backfillbindings($dryRun = true) {
		try {
			$normalizedDryRun = filter_var($dryRun, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			if ($normalizedDryRun === null) {
				$normalizedDryRun = true;
			}

			$summary = $this->backfillService->run($normalizedDryRun, null);
			return new JSONResponse([
				'data' => [
					'summary' => $summary,
				],
				'status' => 'success',
			]);
		} catch (\Throwable $e) {
			return new JSONResponse([
				'data' => ['message' => $e->getMessage()],
				'status' => 'error',
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
