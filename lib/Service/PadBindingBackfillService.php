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
		$skippedDetails = [];

		$seenFileIds = [];
		$processed = 0;
		$inspections = [];

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
				if (count($skippedDetails) < 200) {
					$skippedDetails[] = [
						'reason' => 'file_not_found',
						'file_id' => $fileId,
						'path' => null,
						'file_link' => '/apps/files/?fileid=' . $fileId,
					];
				}
				continue;
			}

			foreach ($nodes as $node) {
				if (!($node instanceof File)) {
					continue;
				}

				$summary['scanned']++;
				$inspections[] = $this->inspectFile($node);

				$processed++;
				if ($limit !== null && $processed >= $limit) {
					break 2;
				}
			}
		}
		$result->closeCursor();

		$candidateByPadKey = [];
		foreach ($inspections as $inspection) {
			if (($inspection['status'] ?? null) !== 'create_candidate') {
				continue;
			}
			$padKey = (string)$inspection['pad_key'];
			$candidateByPadKey[$padKey] ??= [];
			$candidateByPadKey[$padKey][] = (int)$inspection['file_id'];
		}

		$duplicatePadKeys = [];
		foreach ($candidateByPadKey as $padKey => $fileIds) {
			if (count($fileIds) > 1) {
				$duplicatePadKeys[$padKey] = $fileIds;
			}
		}

		foreach ($inspections as $inspection) {
			$status = (string)$inspection['status'];
			$detail = $inspection['detail'] ?? null;

			if ($status === 'create_candidate') {
				$padKey = (string)$inspection['pad_key'];
				if (isset($duplicatePadKeys[$padKey])) {
					$status = 'conflicts';
					$otherFileId = null;
					foreach ($duplicatePadKeys[$padKey] as $candidateFileId) {
						if ($candidateFileId !== (int)$inspection['file_id']) {
							$otherFileId = $candidateFileId;
							break;
						}
					}
					$detail = $this->conflictDetail(
						'duplicate_in_current_run',
						(int)$inspection['file_id'],
						(string)$inspection['path'],
						(string)$inspection['base_url'],
						(string)$inspection['pad_id'],
						$otherFileId
					);
				} else {
					$applyResult = $this->applyCandidate($inspection, $dryRun);
					$status = (string)$applyResult['status'];
					$detail = $applyResult['detail'] ?? null;
				}
			}

			if (isset($summary[$status])) {
				$summary[$status]++;
			} else {
				$summary['errors']++;
			}
			if ($status === 'conflicts' && is_array($detail) && count($conflictDetails) < 200) {
				$conflictDetails[] = $detail;
			}
			if ($status === 'skipped' && is_array($detail) && count($skippedDetails) < 200) {
				$skippedDetails[] = $detail;
			}
		}

		$summary['conflict_details'] = $conflictDetails;
		$summary['conflict_groups'] = $this->groupConflictDetails($conflictDetails);
		$summary['skipped_details'] = $skippedDetails;
		return $summary;
	}

	/**
	 * @return array{status: string, detail?: array<string, mixed>, message?: string}
	 */
	public function markFileAsValid(int $fileId): array {
		$file = $this->findPadFileById($fileId);
		if (!($file instanceof File)) {
			return [
				'status' => 'error',
				'message' => 'Pad file not found',
			];
		}

		$inspection = $this->inspectFile($file);
		$status = (string)$inspection['status'];
		if ($status === 'create_candidate') {
			$result = $this->applyCandidate($inspection, false);
			if (($result['status'] ?? null) === 'created' || ($result['status'] ?? null) === 'already_bound') {
				return [
					'status' => 'success',
					'detail' => $result['detail'] ?? null,
				];
			}
			return $result;
		}

		if ($status === 'already_bound') {
			return ['status' => 'success'];
		}

		if ($status === 'conflicts') {
			return [
				'status' => 'conflicts',
				'detail' => $inspection['detail'] ?? null,
			];
		}

		return [
			'status' => 'error',
			'message' => 'File cannot be marked as valid in current state',
		];
	}

	/**
	 * @return array{status: string, detail?: array<string, mixed>}
	 */
	private function inspectFile(File $file): array {
		$path = (string)$file->getPath();
		if (str_contains($path, '/files_trashbin/')) {
			return $this->skippedResult('trashbin_file', $file->getId(), $path);
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
			return $this->skippedResult('missing_url', $fileId, $path);
		}

		$baseUrl = rtrim((string)$this->config->getAppValue('ownpad', 'ownpad_etherpad_host', ''), '/');
		if (!$this->isAllowedOwnpadUrl($url, $baseUrl, 'pad')) {
			return $this->skippedResult('invalid_host_or_url', $fileId, $path);
		}

		$padId = $this->extractPadIdFromUrl($url, $baseUrl);
		if ($padId === null || $padId === '') {
			return $this->skippedResult('invalid_pad_url', $fileId, $path);
		}

		$binding = $this->padBindingService->findActiveByFileId($fileId);
		if ($binding !== null) {
			if ((string)$binding['pad_id'] === $padId && (string)$binding['base_url'] === $baseUrl) {
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

		$originToken = $this->extractShortcutValue((string)$content, 'OwnpadToken');
		$missingToken = $originToken === null || $originToken === '';
		if ($missingToken) {
			$originToken = $this->secureRandom->generate(64, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
			$content = $this->appendShortcutField((string)$content, 'OwnpadToken', $originToken);
		}

		return [
			'status' => 'create_candidate',
			'file_id' => $fileId,
			'path' => $path,
			'base_url' => $baseUrl,
			'pad_id' => $padId,
			'pad_key' => $baseUrl . "\n" . $padId,
			'origin_token' => $originToken,
			'updated_content' => $content,
			'missing_token' => $missingToken,
		];
	}

	/**
	 * @return array{status: string, detail: array<string, mixed>}
	 */
	private function conflictResult(string $reason, int $fileId, string $path, string $baseUrl, string $padId, ?int $conflictFileId = null): array {
		return [
			'status' => 'conflicts',
			'detail' => $this->conflictDetail($reason, $fileId, $path, $baseUrl, $padId, $conflictFileId),
		];
	}

	/**
	 * @param array<string, mixed> $inspection
	 * @return array{status: string, detail?: array<string, mixed>}
	 */
	private function applyCandidate(array $inspection, bool $dryRun): array {
		$fileId = (int)$inspection['file_id'];
		$path = (string)$inspection['path'];
		$baseUrl = (string)$inspection['base_url'];
		$padId = (string)$inspection['pad_id'];
		$originToken = (string)$inspection['origin_token'];

		if ($dryRun) {
			return ['status' => 'created'];
		}

		$nodes = $this->rootFolder->getById($fileId);
		$file = null;
		foreach ($nodes as $node) {
			if ($node instanceof File) {
				$file = $node;
				break;
			}
		}
		if (!($file instanceof File)) {
			return ['status' => 'errors'];
		}

		if ((bool)$inspection['missing_token'] === true) {
			try {
				$file->putContent((string)$inspection['updated_content']);
			} catch (\Throwable $e) {
				$this->logger->warning('Unable to persist OwnpadToken while backfilling mapping', [
					'app' => 'ownpad',
					'fileId' => $fileId,
					'exception' => $e,
				]);
				return ['status' => 'errors'];
			}
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

			$raceBinding = $this->padBindingService->findActiveByFileId($fileId);
			if ($raceBinding !== null
				&& (string)$raceBinding['pad_id'] === $padId
				&& (string)$raceBinding['base_url'] === $baseUrl) {
				return ['status' => 'already_bound'];
			}

			return ['status' => 'errors'];
		}

		return ['status' => 'created'];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function conflictDetail(string $reason, int $fileId, string $path, string $baseUrl, string $padId, ?int $conflictFileId = null): array {
		return [
			'reason' => $reason,
			'file_id' => $fileId,
			'path' => $path,
			'base_url' => $baseUrl,
			'pad_id' => $padId,
			'conflict_file_id' => $conflictFileId,
			'file_link' => '/apps/files/?fileid=' . $fileId,
			'conflict_file_link' => $conflictFileId !== null ? '/apps/files/?fileid=' . $conflictFileId : null,
		];
	}

	/**
	 * @return array{status: string, detail: array<string, mixed>}
	 */
	private function skippedResult(string $reason, int $fileId, ?string $path): array {
		return [
			'status' => 'skipped',
			'detail' => [
				'reason' => $reason,
				'file_id' => $fileId,
				'path' => $path,
				'file_link' => '/apps/files/?fileid=' . $fileId,
			],
		];
	}

	/**
	 * @param array<int, array<string, mixed>> $conflictDetails
	 * @return array<int, array<string, mixed>>
	 */
	private function groupConflictDetails(array $conflictDetails): array {
		$groups = [];
		foreach ($conflictDetails as $detail) {
			$baseUrl = (string)($detail['base_url'] ?? '');
			$padId = (string)($detail['pad_id'] ?? '');
			$key = $baseUrl . "\n" . $padId;
			if (!isset($groups[$key])) {
				$groups[$key] = [
					'base_url' => $baseUrl,
					'pad_id' => $padId,
					'items' => [],
				];
			}
			$groups[$key]['items'][] = $detail;
		}

		return array_values($groups);
	}

	private function findPadFileById(int $fileId): ?File {
		$nodes = $this->rootFolder->getById($fileId);
		foreach ($nodes as $node) {
			if (!($node instanceof File)) {
				continue;
			}
			if (str_contains((string)$node->getPath(), '/files_trashbin/')) {
				continue;
			}
			if (strtolower((string)pathinfo((string)$node->getName(), PATHINFO_EXTENSION)) !== 'pad') {
				continue;
			}
			return $node;
		}

		return null;
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
