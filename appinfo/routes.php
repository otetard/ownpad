<?php
/**
 * ownCloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2015
 */

namespace OCA\Ownpad\Appinfo;

/** @var $this \OC\Route\Router */

$this->create('ownpad_newpad', 'ajax/newpad.php')->actionInclude('ownpad/ajax/newpad.php');
$this->create('ownpad_getconfig', 'ajax/getconfig.php')->actionInclude('ownpad/ajax/getconfig.php');

return ['routes' => [
    ['name' => 'display#showPad', 'url' => '/', 'verb' => 'GET'],
]];
