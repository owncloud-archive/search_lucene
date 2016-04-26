<?php
/**
 * ownCloud - search_lucene
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Devin M. Ceartas <devin@nacredata.com>
 * @copyright Devin M. Ceartas 2016
 */

namespace OCA\Search_Lucene\Db;

use OCP\IDb;

class FilesInFolder {

	private $db;
	private $files;

	public function __construct(IDb $db) {
		$this->db = $db;
		$this->files = array();
	}

	public function files($folder_id) {
		$sth = $this->db->prepare('SELECT fileid FROM `*PREFIX*filecache` WHERE `parent` = ?');
		$sth->bindValue(1, $folder_id, \PDO::PARAM_INT);
		$sth->execute();
		$result	= $sth->fetchAll();
		
		foreach ($result as $row) {
			if ($this->is_sub_folder($row['fileid'])) {
				return $this->files($row['fileid']);
			} else {
				$this->files[] = $row['fileid'];
			}
		}
		
		return array_unique($this->files);
	}
	
	private function is_sub_folder($folder_id) {
		$inner = $this->db->prepare('SELECT count(fileid) AS `is_sub` FROM `*PREFIX*filecache` WHERE `parent` = ?');
		$inner->bindValue(1, $folder_id, \PDO::PARAM_INT);
		$inner->execute();
		$sub_result = $inner->fetchAll();
		return ($sub_result['is_sub'] > 0);
	}

	public function logit($text) {
		\OC::$server->getLogger()->debug((string)$text, [
			'app' => 'search_lucene',
		]);
	}

}
