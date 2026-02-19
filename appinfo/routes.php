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

namespace OCA\Ownpad\Appinfo;

/** @var $this \OC\Route\Router */

$this->create('ownpad_newpad', 'ajax/newpad.php')->actionInclude('ownpad/ajax/newpad.php');

return ['routes' => [
	['name' => 'display#showPad', 'url' => '/', 'verb' => 'GET'],
	['name' => 'publicDisplay#showPad', 'url' => '/public/{token}', 'verb' => 'GET'],
	['name' => 'ajax#getconfig', 'url' => '/ajax/v1.0/getconfig', 'verb' => 'GET'],
	['name' => 'ajax#newpad', 'url' => '/ajax/v1.0/newpad', 'verb' => 'POST'],
	['name' => 'ajax#testetherpadtoken', 'url' => '/ajax/v1.0/testetherpadtoken', 'verb' => 'GET'],
	['name' => 'ajax#backfillbindings', 'url' => '/ajax/v1.0/backfillbindings', 'verb' => 'POST'],
	['name' => 'ajax#backfillmarkvalid', 'url' => '/ajax/v1.0/backfillmarkvalid', 'verb' => 'POST'],
	['name' => 'ajax#backfillcreatealias', 'url' => '/ajax/v1.0/backfillcreatealias', 'verb' => 'POST'],
]];
