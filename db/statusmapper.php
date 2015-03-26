<?php
/**
 * ownCloud - search_lucene
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @copyright Jörn Friedrich Dreyer 2012-2015
 */

namespace OCA\Search_Lucene\Db;

use OC\Files\Filesystem;
use OC\Files\Mount\Mount;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\Mapper;
use OCP\IDb;
use OCP\ILogger;

class StatusMapper extends Mapper {

	private $logger;

	public function __construct(IDb $db, ILogger $logger){
		parent::__construct($db, 'lucene_status', '\OCA\Search_Lucene\Db\Status');
		$this->logger = $logger;
	}


	/**
	 * Deletes a status from the table
	 * @param Entity $status the status that should be deleted
	 */
	public function delete(Entity $status){
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE `fileid` = ?';
		$this->execute($sql, array($status->getFileId()));
	}

	/**
	 * Creates a new entry in the db from an entity
	 * @param Entity $entity the entity that should be created
	 * @return Status the saved entity with the set id
	 */
	public function insert(Entity $entity){
		// get updated fields to save, fields have to be set using a setter to
		// be saved
		$properties = $entity->getUpdatedFields();
		$values = '';
		$columns = '';
		$params = array();

		// build the fields
		$i = 0;
		foreach($properties as $property => $updated) {
			$column = $entity->propertyToColumn($property);
			$getter = 'get' . ucfirst($property);

			$columns .= '`' . $column . '`';
			$values .= '?';

			// only append colon if there are more entries
			if($i < count($properties)-1){
				$columns .= ',';
				$values .= ',';
			}

			array_push($params, $entity->$getter());
			$i++;

		}

		$sql = 'INSERT INTO `' . $this->tableName . '`(' .
			$columns . ') VALUES(' . $values . ')';

		$this->execute($sql, $params);

		$entity->setFileId((int) $this->db->getInsertId($this->tableName));
		return $entity;
	}

	/**
	 * Updates an entry in the db from a status
	 * @param Entity $entity the status that should be created
	 * @return Entity|null
	 * @throws \InvalidArgumentException if entity has no id
	 */
	public function update(Entity $entity){
		// if entity wasn't changed it makes no sense to run a db query
		$properties = $entity->getUpdatedFields();
		if(count($properties) === 0) {
			return $entity;
		}

		// entity needs an id
		$fileId = $entity->getFileId();
		if($fileId === null){
			throw new \InvalidArgumentException(
				'Entity which should be updated has no fileId');
		}

		// get updated fields to save, fields have to be set using a setter to
		// be saved
		// don't update the fileId field
		unset($properties['fileId']);

		$columns = '';
		$params = array();

		// build the fields
		$i = 0;
		foreach($properties as $property => $updated) {

			$column = $entity->propertyToColumn($property);
			$getter = 'get' . ucfirst($property);

			$columns .= '`' . $column . '` = ?';

			// only append colon if there are more entries
			if($i < count($properties)-1){
				$columns .= ',';
			}

			array_push($params, $entity->$getter());
			$i++;
		}

		$sql = 'UPDATE `' . $this->tableName . '` SET ' .
			$columns . ' WHERE `fileid` = ?';
		array_push($params, $fileId);

		$this->execute($sql, $params);
	}


	/**
	 * get the list of all unindexed files for the user
	 *
	 * @return array
	 */
	public function getUnindexed() {
		$files = array();
		//TODO use server api for mounts & root
		$absoluteRoot = Filesystem::getView()->getAbsolutePath('/');
		$mounts = Filesystem::getMountPoints($absoluteRoot);
		$mount = Filesystem::getMountPoint($absoluteRoot);
		if (!in_array($mount, $mounts)) {
			$mounts[] = $mount;
		}

		$query = $this->db->prepareQuery('
			SELECT `*PREFIX*filecache`.`fileid`
			FROM `*PREFIX*filecache`
			LEFT JOIN `' . $this->tableName . '`
			ON `*PREFIX*filecache`.`fileid` = `' . $this->tableName . '`.`fileid`
			WHERE `storage` = ?
			AND ( `status` IS NULL OR `status` = ? )
			AND `path` LIKE \'files/%\'
		');

		foreach ($mounts as $mount) {
			if (is_string($mount)) {
				$storage = Filesystem::getStorage($mount);
			} else if ($mount instanceof Mount) {
				$storage = $mount->getStorage();
			} else {
				$storage = null;
				$this->logger->
					debug( 'expected string or instance of \OC\Files\Mount\Mount got ' . json_encode($mount) );
			}
			//only index local files for now
			if ($storage->isLocal()) {
				$cache = $storage->getCache();
				$numericId = $cache->getNumericStorageId();

				$result = $query->execute(array($numericId, Status::STATUS_NEW));

				while ($row = $result->fetchRow()) {
					$files[] = $row['fileid'];
				}
			}
		}
		return $files;
	}


	/**
	 * @param $fileId
	 * @return Status
	 */
	public function getOrCreateFromFileId($fileId) {
		$this->db->insertIfNotExist(
			$this->tableName,
			[ 'fileid' => $fileId, 'status' => Status::STATUS_NEW ],
			[ 'fileid' ]
		);
		$sql = '
			SELECT `fileid`, `status`
			FROM ' . $this->tableName . '
			WHERE `fileid` = ?
		';
		return $this->findEntity($sql, array($fileId));
	}

	// always write status to db immediately
	public function markNew(Status $status) {
		$status->setStatus(Status::STATUS_NEW);
		return $this->update($status);
	}

	public function markIndexed(Status $status) {
		$status->setStatus(Status::STATUS_INDEXED);
		return $this->update($status);
	}

	public function markSkipped(Status $status) {
		$status->setStatus(Status::STATUS_SKIPPED);
		return $this->update($status);
	}

	public function markUnIndexed(Status $status) {
		$status->setStatus(Status::STATUS_UNINDEXED);
		return $this->update($status);
	}

	public function markVanished(Status $status) {
		$status->setStatus(Status::STATUS_VANISHED);
		return $this->update($status);
	}

	public function markError(Status $status) {
		$status->setStatus(Status::STATUS_ERROR);
		return $this->update($status);
	}

	/**
	 * @return int[]
	 */
	public function getDeleted() {
		$files = array();

		$query = $this->db->prepareQuery('
			SELECT `' . $this->tableName . '`.`fileid`
			FROM `' . $this->tableName . '`
			LEFT JOIN `*PREFIX*filecache`
				ON `*PREFIX*filecache`.`fileid` = `' . $this->tableName . '`.`fileid`
			WHERE `*PREFIX*filecache`.`fileid` IS NULL
		');

		$result = $query->execute();

		while ($row = $result->fetchRow()) {
			$files[] = $row['fileid'];
		}

		return $files;

	}

}
