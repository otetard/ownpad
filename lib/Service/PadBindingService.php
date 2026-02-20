<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 */

namespace OCA\Ownpad\Service;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class PadBindingService {
	public const TABLE = 'ownpad_pad_binding';

	public function __construct(
		private IDBConnection $db,
	) {
	}

	public function create(
		int $fileId,
		string $padId,
		string $baseUrl,
		string $originToken,
		?string $createdByUid,
		?string $ownerUid,
		bool $isProtected
	): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert(self::TABLE)
			->values([
				'file_id' => $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
				'pad_id' => $qb->createNamedParameter($padId),
				'base_url' => $qb->createNamedParameter($baseUrl),
				'origin_token' => $qb->createNamedParameter($originToken),
				'created_by_uid' => $qb->createNamedParameter($createdByUid),
				'owner_uid' => $qb->createNamedParameter($ownerUid),
				'is_protected' => $qb->createNamedParameter($isProtected ? 1 : 0, IQueryBuilder::PARAM_INT),
				'created_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
				'updated_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
			]);
		$qb->executeStatement();
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findActiveByFileId(int $fileId): ?array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE)
			->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)))
			->setMaxResults(1);

		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row === false) {
			return null;
		}

		return $row;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findActiveByPad(string $baseUrl, string $padId): ?array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE)
			->where($qb->expr()->eq('base_url', $qb->createNamedParameter($baseUrl)))
			->andWhere($qb->expr()->eq('pad_id', $qb->createNamedParameter($padId)))
			->setMaxResults(1);

		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row === false) {
			return null;
		}

		return $row;
	}
}
