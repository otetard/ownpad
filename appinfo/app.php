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

require_once __DIR__ . '/../3rdparty/autoload.php';

OCP\App::registerAdmin('ownpad','settings');

OCP\Util::addscript('ownpad', 'ownpad');
OCP\Util::addStyle('ownpad', 'ownpad');

