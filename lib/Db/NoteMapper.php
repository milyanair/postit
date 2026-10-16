<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<Note> */
class NoteMapper extends QBMapper {
	public function __construct(IDBConnection $db) { parent::__construct($db, 'postit_notes', Note::class); }
	/** @return Note[] */
	public function findAll(string $uid, int $limit = 500): array {
		$q = $this->db->getQueryBuilder();
		$q->select('*')->from($this->getTableName())->where($q->expr()->eq('user_id', $q->createNamedParameter($uid)))->orderBy('updated_at', 'DESC')->setMaxResults($limit);
		return $this->findEntities($q);
	}
	public function findById(int $id): Note {
		$q = $this->db->getQueryBuilder();
		$q->select('*')->from($this->getTableName())->where($q->expr()->eq('id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($q);
	}
	/** @param int[] $ids @return Note[] */
	public function findByIds(array $ids): array {
		if (!$ids) return [];
		$q = $this->db->getQueryBuilder();
		$q->select('*')->from($this->getTableName())->where($q->expr()->in('id', $q->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));
		return $this->findEntities($q);
	}
	public function find(int $id, string $uid): Note {
		$q = $this->db->getQueryBuilder();
		$q->select('*')->from($this->getTableName())
			->where($q->expr()->eq('id', $q->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($q->expr()->eq('user_id', $q->createNamedParameter($uid)));
		return $this->findEntity($q);
	}
}
