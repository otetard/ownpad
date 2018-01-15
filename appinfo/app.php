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

/*
 * Disabled since it breaks Mimetype detection for other types…
 * More information here: https://github.com/otetard/ownpad/issues/3
 *
 * To make everything work, you need to create
 * `/config/mimetypemapping.json` from
 * `/resources/config/mimetypemapping.dist.json` and then append the
 * two following lines just after the “_comment” lines.
 *
 *     "pad": ["application/x-ownpad"],
 *     "calc": ["application/x-ownpad"],
 */
// \OC::$server->getMimeTypeDetector()->registerType("pad", "application/x-ownpad");
// \OC::$server->getMimeTypeDetector()->registerType("calc", "application/x-ownpad");
