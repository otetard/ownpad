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

OCP\App::registerAdmin('ownpad','settings');

OCP\Util::addscript('ownpad', 'ownpad');
OCP\Util::addStyle('ownpad', 'ownpad');

\OC::$server->getMimeTypeDetector()->registerType("pad", "application/x-ownpad");
\OC::$server->getMimeTypeDetector()->registerType("calc", "application/x-ownpad");
