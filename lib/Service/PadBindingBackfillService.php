<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 */

namespace OCA\Ownpad\Service;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class PadBindingBackfillService {
	public function __construct(
		private IDBConnection $db,
		private IRootFolder $rootFolder,
		private IConfig $config,
		private ISecureRandom $secureRandom,
		private PadBindingService $padBindingService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @return array<string, int>
	 */
	public function run(bool $dryRun = true, ?int $limit = null): array {
		$summary = [
			'scanned' => 0,
			'created' => 0,
			'already_bound' => 0,
			'skipped' => 0,
			'conflicts' => 0,
			'errors' => 0,
		];

		$seenFileIds = [];
		$processed = 0;

		$qb = $this->db->getQueryBuilder();
		$qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->iLike('name', $qb->createNamedParameter('%.pad')))
			->orderBy('fileid', 'ASC');

		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			$fileId = (int)($row['fileid'] ?? 0);
			if ($fileId <= 0 || isset($seenFileIds[$fileId])) {
				continue;
			}
			$seenFileIds[$fileId] = true;

			$nodes = $this->rootFolder->getById($fileId);
			if ($nodes === []) {
				$summary['skipped']++;
				continue;
			}

			foreach ($nodes as $node) {
				if (!($node instanceof File)) {
					continue;
				}

				$summary['scanned']++;
				$status = $this->processFile($node, $dryRun);
				if (isset($summary[$status])) {
					$summary[$status]++;
				} else {
					$summary['errors']++;
				}

				$processed++;
				if ($limit !== null && $processed >= $limit) {
					break 2;
				}
			}
		}
		$result->closeCursor();

		return $summary;
	}

	private function processFile(File $file, bool $dryRun): string {
		$path = (string)$file->getPath();
		if (str_contains($path, '/files_trashbin/')) {
			return 'skipped';
		}

		try {
			$content = $file->getContent();
		} catch (\Throwable $e) {
			$this->logger->warning('Unable to read pad file for mapping backfill', [
				'app' => 'ownpad',
				'fileId' => $file->getId(),
				'exception' => $e,
			]);
			return 'errors';
		}

		$url = $this->extractShortcutValue((string)$content, 'URL');
		if ($url === null || $url === '') {
			return 'skipped';
		}

		$baseUrl = rtrim((string)$this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''), '/');
		if (!$this->isAllowedOwnpadUrl($url, $baseUrl, 'pad')) {
			return 'skipped';
		}

		$padId = $this->extractPadIdFromUrl($url, $baseUrl);
		if ($padId === null || $padId === '') {
			return 'skipped';
		}

		$fileId = $file->getId();
		$binding = $this->padBindingService->findActiveByFileId($fileId);
		if ($binding !== null) {
			if ((string)$binding['pad_id'] === $padId && (string)$binding['base_url'] === $baseUrl) {
				return 'already_bound';
			}
			return 'conflicts';
		}

		$existingPadBinding = $this->padBindingService->findActiveByPad($baseUrl, $padId);
		if ($existingPadBinding !== null && (int)$existingPadBinding['file_id'] !== $fileId) {
			return 'conflicts';
		}

		$originToken = $this->extractShortcutValue((string)$content, 'OwnpadToken');
		if ($originToken === null || $originToken === '') {
			$originToken = $this->secureRandom->generate(64, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
			$content = $this->appendShortcutField((string)$content, 'OwnpadToken', $originToken);

			if (!$dryRun) {
				try {
					$file->putContent($content);
				} catch (\Throwable $e) {
					$this->logger->warning('Unable to persist OwnpadToken while backfilling mapping', [
						'app' => 'ownpad',
						'fileId' => $fileId,
						'exception' => $e,
					]);
					return 'errors';
				}
			}
		}

		if ($dryRun) {
			return 'created';
		}

		$ownerUid = $file->getOwner()?->getUID();
		$isProtectedPad = preg_match('/^g\.[A-Za-z0-9]{16}\$.+$/', $padId) === 1;

		try {
			$this->padBindingService->create(
				$fileId,
				$padId,
				$baseUrl,
				$originToken,
				$ownerUid,
				$ownerUid,
				$isProtectedPad,
			);
		} catch (\Throwable $e) {
			$this->logger->warning('Unable to store mapping while backfilling pad binding', [
				'app' => 'ownpad',
				'fileId' => $fileId,
				'exception' => $e,
			]);

			$raceBinding = $this->padBindingService->findActiveByFileId($fileId);
			if ($raceBinding !== null
				&& (string)$raceBinding['pad_id'] === $padId
				&& (string)$raceBinding['base_url'] === $baseUrl) {
				return 'already_bound';
			}

			return 'errors';
		}

		return 'created';
	}

	private function extractShortcutValue(string $content, string $key): ?string {
		$regex = sprintf('/^%s=(.*)$/mi', preg_quote($key, '/'));
		if (preg_match($regex, $content, $matches) !== 1) {
			return null;
		}

		return trim((string)$matches[1]);
	}

	private function appendShortcutField(string $content, string $key, string $value): string {
		$lines = preg_split('/\r\n|\r|\n/', trim($content));
		$updated = [];
		$replaced = false;

		foreach ($lines as $line) {
			if (str_starts_with((string)$line, $key . '=')) {
				$updated[] = $key . '=' . $value;
				$replaced = true;
				continue;
			}
			$updated[] = $line;
		}

		if (!$replaced) {
			$updated[] = $key . '=' . $value;
		}

		return implode("\n", $updated);
	}

	private function extractPadIdFromUrl(string $url, string $baseUrl): ?string {
		if (!$this->isAllowedOwnpadUrl($url, $baseUrl, 'pad')) {
			return null;
		}

		$urlPath = parse_url($url, PHP_URL_PATH);
		if (!is_string($urlPath) || $urlPath === '') {
			return null;
		}

		$configuredPath = parse_url($baseUrl, PHP_URL_PATH);
		$configuredPath = is_string($configuredPath) ? rtrim($configuredPath, '/') : '';

		$prefixPath = $configuredPath . '/p/';
		if (!str_starts_with($urlPath, $prefixPath)) {
			return null;
		}

		$padId = substr($urlPath, strlen($prefixPath));
		if ($padId === false || $padId === '') {
			return null;
		}

		return rawurldecode($padId);
	}

	private function isAllowedOwnpadUrl(string $url, string $configuredHost, string $fileEnding): bool {
		$configured = parse_url(rtrim($configuredHost, '/'));
		$actual = parse_url($url);
		if ($configured === false || $actual === false) {
			return false;
		}

		$configuredScheme = strtolower((string)($configured['scheme'] ?? ''));
		$actualScheme = strtolower((string)($actual['scheme'] ?? ''));
		$configuredHostName = strtolower((string)($configured['host'] ?? ''));
		$actualHostName = strtolower((string)($actual['host'] ?? ''));

		if ($configuredScheme === '' || $configuredHostName === '') {
			return false;
		}
		if ($configuredScheme !== $actualScheme || $configuredHostName !== $actualHostName) {
			return false;
		}
		if ($this->normalizeUrlPort($configuredScheme, $configured['port'] ?? null) !== $this->normalizeUrlPort($actualScheme, $actual['port'] ?? null)) {
			return false;
		}

		$configuredPath = isset($configured['path']) ? rtrim((string)$configured['path'], '/') : '';
		$actualPath = (string)($actual['path'] ?? '');
		if ($fileEnding === 'pad') {
			return preg_match('/^' . preg_quote($configuredPath . '/p/', '/') . '[^\/]+$/', $actualPath) === 1;
		}

		return false;
	}

	private function normalizeUrlPort(string $scheme, mixed $port): int {
		if (is_int($port)) {
			return $port;
		}

		return $scheme === 'https' ? 443 : 80;
	}
}
