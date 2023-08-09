<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * This migration script is heavily inspired by `files_mindmap`[1] and
 * `drawio-nextcloud`[2] Nextcloud applications. Thanks to them!
 *
 * 1. https://github.com/ACTom/files_mindmap/blob/6bbb130374cc7f0619904b1c57b7fa81d73a634b/lib/Migration/InstallStep.php
 * 2. https://github.com/jgraph/drawio-nextcloud/blob/8b0bfbccc176e8144e827955aa82b0b1718f6601/lib/Migration/RegisterMimeType.php
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2023
 */

namespace OCA\Ownpad\Migration;

use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;
use OC\Core\Command\Maintenance\Mimetype\UpdateJS;
use OCP\ILogger;
use OCP\Files\IMimeTypeLoader;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class RegisterMimeType implements IRepairStep {

    private $mimeTypeLoader;
    private $updateJS;

    public function __construct(UpdateJS $updateJS, IMimeTypeLoader $mimeTypeLoader) {
        $this->updateJS = $updateJS;
        $this->mimeTypeLoader = $mimeTypeLoader;
    }

    public function getName() {
        return 'Register MIME types for Ownpad.';
    }

    public function run(IOutput $output) {
        $output->info('Registering the MIME types…');
        $this->registerMimeTypes();

        $output->info('Copy MIME types icons to core/img directory.');
        $this->copyIcons();

        $this->updateJS->run(new StringInput(''), new ConsoleOutput());
    }

    private function registerMimeTypes() {
        $mimetypeToExtMapping = [
            'application/x-ownpad' => 'pad',
            'application/x-ownpad-calc' => 'calc',
        ];
        $extToMimetypeMapping = [
            'pad' => ['application/x-ownpad'],
            'calc' => ['application/x-ownpad-calc'],
        ];

        // Update cache for any existing files.
        foreach ($mimetypeToExtMapping as $mimeType => $ext) {
            $mimeTypeId = $this->mimeTypeLoader->getId($mimeType);
            $this->mimeTypeLoader->updateFilecache($ext, $mimeTypeId);
        }

        // Updating the alias/mapping configuration file.
        $configDir = \OC::$configDir;
        $mimetypealiasesFile = $configDir . 'mimetypealiases.json';
        $mimetypemappingFile = $configDir . 'mimetypemapping.json';

        $this->appendToFile($mimetypealiasesFile, $mimetypeToExtMapping);
        $this->appendToFile($mimetypemappingFile, $extToMimetypeMapping);
    }

    private function appendToFile(string $filename, array $data) {
        $obj = [];
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $obj = json_decode($content, true);
        }
        foreach ($data as $key => $value) {
            $obj[$key] = $value;
        }
        file_put_contents($filename, json_encode($obj,  JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }

    private function copyIcons() {
        $icons = ['pad', 'calc'];
        foreach ($icons as $icon) {
            $source = __DIR__ . '/../../img/' . $icon . '.svg';
            $target = \OC::$SERVERROOT . '/core/img/filetypes/' . $icon . '.svg';
            if (!file_exists($target) || md5_file($target) !== md5_file($source)) {
                copy($source, $target);
            }
        }
    }
}
