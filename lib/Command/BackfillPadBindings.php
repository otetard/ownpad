<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 */

namespace OCA\Ownpad\Command;

use OCA\Ownpad\Service\PadBindingBackfillService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BackfillPadBindings extends Command {
	public function __construct(
		private PadBindingBackfillService $backfillService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ownpad:bindings:backfill')
			->setDescription('Backfill Ownpad .pad mappings into ownpad_pad_binding')
			->addOption(
				'dry-run',
				null,
				InputOption::VALUE_NONE,
				'Show what would be inserted without writing DB/file changes'
			)
			->addOption(
				'limit',
				null,
				InputOption::VALUE_REQUIRED,
				'Process at most N pad files'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		$dryRun = (bool)$input->getOption('dry-run');
		$limit = $input->getOption('limit');
		$limit = is_numeric($limit) ? max(1, (int)$limit) : null;

		$io->title('Ownpad pad binding backfill');
		$io->writeln('Mode: ' . ($dryRun ? 'dry-run' : 'write'));
		if ($limit !== null) {
			$io->writeln('Limit: ' . $limit);
		}

		$summary = $this->backfillService->run($dryRun, $limit);

		$io->newLine();
		$io->table(['Metric', 'Count'], [
			['scanned', (string)$summary['scanned']],
			['created', (string)$summary['created']],
			['already_bound', (string)$summary['already_bound']],
			['skipped', (string)$summary['skipped']],
			['conflicts', (string)$summary['conflicts']],
			['errors', (string)$summary['errors']],
		]);

		if ($summary['errors'] > 0) {
			$io->warning('Backfill finished with errors. Check Nextcloud logs for details.');
		} else {
			$io->success('Backfill finished.');
		}

		$conflicts = is_array($summary['conflict_details'] ?? null) ? $summary['conflict_details'] : [];
		if ($conflicts !== []) {
			$io->newLine();
			$io->section('Conflicts');

			$rows = [];
			foreach ($conflicts as $conflict) {
				$rows[] = [
					(string)($conflict['file_id'] ?? ''),
					(string)($conflict['conflict_file_id'] ?? ''),
					(string)($conflict['reason'] ?? ''),
					(string)($conflict['pad_id'] ?? ''),
					(string)($conflict['path'] ?? ''),
				];
			}
			$io->table(['file_id', 'conflict_file_id', 'reason', 'pad_id', 'path'], $rows);
		}

		return self::SUCCESS;
	}
}
