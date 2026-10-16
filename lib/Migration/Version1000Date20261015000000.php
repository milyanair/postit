<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20261015000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('postit_notes')) return null;
		$t = $schema->createTable('postit_notes');
		$t->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
		$t->addColumn('user_id', Types::STRING, ['notnull' => true, 'length' => 64]);
		$t->addColumn('content', Types::TEXT, ['notnull' => false]);
		$t->addColumn('color', Types::STRING, ['notnull' => true, 'length' => 16, 'default' => '']);
		$t->addColumn('x', Types::INTEGER, ['notnull' => true, 'default' => 0]);
		$t->addColumn('y', Types::INTEGER, ['notnull' => true, 'default' => 0]);
		$t->addColumn('w', Types::INTEGER, ['notnull' => true, 'default' => 280]);
		$t->addColumn('h', Types::INTEGER, ['notnull' => true, 'default' => 260]);
		$t->addColumn('pinned', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
		$t->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'default' => 0]);
		$t->addColumn('updated_at', Types::BIGINT, ['notnull' => true, 'default' => 0]);
		$t->setPrimaryKey(['id']);
		$t->addIndex(['user_id'], 'postit_user_idx');
		return $schema;
	}
}
