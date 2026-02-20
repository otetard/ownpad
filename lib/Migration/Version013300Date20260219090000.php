<?php
/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 */

namespace OCA\Ownpad\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version013300Date20260219090000 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('ownpad_pad_binding')) {
			return $schema;
		}

		$table = $schema->createTable('ownpad_pad_binding');
		$table->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('file_id', 'integer', [
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('pad_id', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('base_url', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('origin_token', 'string', [
			'notnull' => true,
			'length' => 128,
		]);
		$table->addColumn('created_by_uid', 'string', [
			'notnull' => false,
			'length' => 64,
		]);
		$table->addColumn('owner_uid', 'string', [
			'notnull' => false,
			'length' => 64,
		]);
		$table->addColumn('is_protected', 'smallint', [
			'notnull' => true,
			'default' => 0,
			'unsigned' => true,
		]);
		$table->addColumn('created_at', 'integer', [
			'notnull' => true,
		]);
		$table->addColumn('updated_at', 'integer', [
			'notnull' => true,
		]);

		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['file_id'], 'ownpad_binding_fileid_uniq');
		$table->addUniqueIndex(['origin_token'], 'ownpad_binding_token_uniq');
		$table->addUniqueIndex(['base_url', 'pad_id'], 'ownpad_binding_pad_lookup_uniq');
		$table->addIndex(['owner_uid', 'pad_id'], 'ownpad_binding_owner_pad_idx');

		return $schema;
	}
}
