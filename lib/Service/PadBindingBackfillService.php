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
	 * @return array<string, mixed>
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
		$conflictDetails = [];

		$seenFileIds = [];
		$reservedByFileId = [];
		$reservedByPad = [];
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
				$result = $this->processFile($node, $dryRun, $reservedByFileId, $reservedByPad);
				$status = $result['status'];
				if (isset($summary[$status])) {
					$summary[$status]++;
				} else {
					$summary['errors']++;
				}
				if ($status === 'conflicts' && isset($result['detail']) && count($conflictDetails) < 200) {
					$conflictDetails[] = $result['detail'];
				}

				$processed++;
				if ($limit !== null && $processed >= $limit) {
					break 2;
				}
			}
		}
		$result->closeCursor();

		$summary['conflict_details'] = $conflictDetails;
		return $summary;
	}

	/**
	 * @return array{status: string, detail?: array<string, mixed>}
	 */
	private function processFile(File $file, bool $dryRun, array &$reservedByFileId, array &$reservedByPad): array {
		$path = (string)$file->getPath();
		if (str_contains($path, '/files_trashbin/')) {
			return ['status' => 'skipped'];
		}
		$fileId = $file->getId();

		try {
			$content = $file->getContent();
		} catch (\Throwable $e) {
			$this->logger->warning('Unable to read pad file for mapping backfill', [
				'app' => 'ownpad',
				'fileId' => $file->getId(),
				'exception' => $e,
			]);
			return ['status' => 'errors'];
		}

		$url = $this->extractShortcutValue((string)$content, 'URL');
		if ($url === null || $url === '') {
			return ['status' => 'skipped'];
		}

		$baseUrl = rtrim((string)$this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''), '/');
		if (!$this->isAllowedOwnpadUrl($url, $baseUrl, 'pad')) {
			return ['status' => 'skipped'];
		}

		$padId = $this->extractPadIdFromUrl($url, $baseUrl);
		if ($padId === null || $padId === '') {
			return ['status' => 'skipped'];
		}

		$padKey = $baseUrl . "\n" . $padId;

		$existingReservedByFile = $reservedByFileId[$fileId] ?? null;
		if ($existingReservedByFile !== null) {
			if ($existingReservedByFile === $padKey) {
				return ['status' => 'already_bound'];
			}
			return $this->conflictResult('file_already_reserved_to_another_pad', $fileId, $path, $baseUrl, $padId);
		}

		$binding = $this->padBindingService->findActiveByFileId($fileId);
		if ($binding !== null) {
			if ((string)$binding['pad_id'] === $padId && (string)$binding['base_url'] === $baseUrl) {
				$this->reserveBinding($fileId, $padKey, $reservedByFileId, $reservedByPad);
				return ['status' => 'already_bound'];
			}
			return $this->conflictResult(
				'file_already_bound_to_another_pad',
				$fileId,
				$path,
				$baseUrl,
				$padId,
				isset($binding['file_id']) ? (int)$binding['file_id'] : null
			);
		}

		$existingPadBinding = $this->padBindingService->findActiveByPad($baseUrl, $padId);
		if ($existingPadBinding !== null && (int)$existingPadBinding['file_id'] !== $fileId) {
			return $this->conflictResult(
				'pad_already_bound_to_other_file',
				$fileId,
				$path,
				$baseUrl,
				$padId,
				(int)$existingPadBinding['file_id']
			);
		}
		$existingReservedByPad = $reservedByPad[$padKey] ?? null;
		if ($existingReservedByPad !== null && $existingReservedByPad !== $fileId) {
			return $this->conflictResult(
				'duplicate_in_current_run',
				$fileId,
				$path,
				$baseUrl,
				$padId,
				(int)$existingReservedByPad
			);
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
					return ['status' => 'errors'];
				}
			}
		}

		if ($dryRun) {
			$this->reserveBinding($fileId, $padKey, $reservedByFileId, $reservedByPad);
			return ['status' => 'created'];
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
				$this->reserveBinding($fileId, $padKey, $reservedByFileId, $reservedByPad);
				return ['status' => 'already_bound'];
			}

			return ['status' => 'errors'];
		}

		$this->reserveBinding($fileId, $padKey, $reservedByFileId, $reservedByPad);
		return ['status' => 'created'];
	}

	private function reserveBinding(int $fileId, string $padKey, array &$reservedByFileId, array &$reservedByPad): void {
		$reservedByFileId[$fileId] = $padKey;
		$reservedByPad[$padKey] = $fileId;
	}

	/**
	 * @return array{status: string, detail: array<string, mixed>}
	 */
	private function conflictResult(string $reason, int $fileId, string $path, string $baseUrl, string $padId, ?int $conflictFileId = null): array {
		return [
			'status' => 'conflicts',
			'detail' => [
				'reason' => $reason,
				'file_id' => $fileId,
				'path' => $path,
				'base_url' => $baseUrl,
				'pad_id' => $padId,
				'conflict_file_id' => $conflictFileId,
			],
		];
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
