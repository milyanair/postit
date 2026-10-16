<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** 1.1.0: archived flag on notes. */
class Version1100Date20261016000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('postit_notes')) return null;
		$t = $schema->getTable('postit_notes');
		if ($t->hasColumn('archived')) return null;
		$t->addColumn('archived', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
		return $schema;
	}
}
