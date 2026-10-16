<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * 1.2.0: sharing. Per-user note state (geometry, open, pinned, archived) moves to postit_state; shares live in postit_shares.
 * The old columns on postit_notes are left in place (unused) so a downgrade keeps working.
 */
class Version1200Date20261016010000 extends SimpleMigrationStep {
	public function __construct(private IDBConnection $db) {}
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changed = false;
		if (!$schema->hasTable('postit_state')) {
			$t = $schema->createTable('postit_state');
			$t->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
			$t->addColumn('note_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
			$t->addColumn('user_id', Types::STRING, ['notnull' => true, 'length' => 64]);
			$t->addColumn('x', Types::INTEGER, ['notnull' => true, 'default' => 0]);
			$t->addColumn('y', Types::INTEGER, ['notnull' => true, 'default' => 0]);
			$t->addColumn('w', Types::INTEGER, ['notnull' => true, 'default' => 0]);
			$t->addColumn('h', Types::INTEGER, ['notnull' => true, 'default' => 0]);
			$t->addColumn('pinned', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
			$t->addColumn('archived', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
			$t->addColumn('is_open', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
			$t->setPrimaryKey(['id']);
			$t->addUniqueIndex(['note_id', 'user_id'], 'postit_state_nu');
			$t->addIndex(['user_id'], 'postit_state_u');
			$changed = true;
		}
		if (!$schema->hasTable('postit_shares')) {
			$t = $schema->createTable('postit_shares');
			$t->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
			$t->addColumn('note_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
			$t->addColumn('user_id', Types::STRING, ['notnull' => true, 'length' => 64]);
			$t->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'default' => 0]);
			$t->setPrimaryKey(['id']);
			$t->addUniqueIndex(['note_id', 'user_id'], 'postit_share_nu');
			$t->addIndex(['user_id'], 'postit_share_u');
			$changed = true;
		}
		return $changed ? $schema : null;
	}
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// Copy each owner's existing geometry/pinned/archived into postit_state (idempotent).
		$have = [];
		$r = $this->db->getQueryBuilder()->select('note_id')->from('postit_state')->executeQuery();
		while ($row = $r->fetch()) $have[(int)$row['note_id']] = true;
		$r->closeCursor();
		$q = $this->db->getQueryBuilder();
		$r = $q->select('id', 'user_id', 'x', 'y', 'w', 'h', 'pinned', 'archived')->from('postit_notes')->executeQuery();
		$n = 0;
		while ($row = $r->fetch()) {
			if (isset($have[(int)$row['id']])) continue;
			$i = $this->db->getQueryBuilder();
			$i->insert('postit_state')->values([
				'note_id' => $i->createNamedParameter((int)$row['id'], IQueryBuilder::PARAM_INT),
				'user_id' => $i->createNamedParameter((string)$row['user_id']),
				'x' => $i->createNamedParameter((int)$row['x'], IQueryBuilder::PARAM_INT),
				'y' => $i->createNamedParameter((int)$row['y'], IQueryBuilder::PARAM_INT),
				'w' => $i->createNamedParameter((int)$row['w'], IQueryBuilder::PARAM_INT),
				'h' => $i->createNamedParameter((int)$row['h'], IQueryBuilder::PARAM_INT),
				'pinned' => $i->createNamedParameter((int)$row['pinned'], IQueryBuilder::PARAM_INT),
				'archived' => $i->createNamedParameter((int)($row['archived'] ?? 0), IQueryBuilder::PARAM_INT),
				'is_open' => $i->createNamedParameter((int)$row['pinned'], IQueryBuilder::PARAM_INT),
			])->executeStatement();
			$n++;
		}
		$r->closeCursor();
		$output->info("Post It: migrated state for $n notes");
	}
}
