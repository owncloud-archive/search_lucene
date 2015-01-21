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

namespace OCA\Search_Lucene\Search;
use OC\Files\Filesystem;
use ZendSearch\Lucene\Search\QueryHit;
use OC\Search\Result\File;

/**
 * A found file
 */
class LuceneResult extends File {

	/**
	 * Type name; translated in templates
	 * @var string
	 */
	public $type = 'lucene';

	/**
	 * @var float
	 */
	public $score;

	/**
	 * Create a new content search result
	 * @param QueryHit $hit file data given by provider
	 */
	public function __construct(QueryHit $hit) {
		$this->id = (string)$hit->fileId;
		$this->path = $this->getRelativePath($hit->path);
		$this->name = basename($this->path);
		$this->size = (int)$hit->size;
		$this->score = $hit->score;
		$this->link = \OCP\Util::linkTo(
			'files',
			'index.php',
			array('dir' => dirname($this->path), 'file' => $this->name)
		);
		$this->permissions = $this->getPermissions($this->path);
		$this->modified = (int)$hit->mtime;
		$this->mime_type = $hit->mimetype;
	}

	protected function getRelativePath ($path) {
		$root = \OC::$server->getUserFolder();
		return $root->getRelativePath($path);
  	}

	/**
	 * Determine permissions for a given file path
	 * @param string $path
	 * @return int
	 */
	function getPermissions($path) {
		// add read permissions
		$permissions = \OCP\PERMISSION_READ;
		// get directory
		$fileInfo = pathinfo($path);
		$dir = $fileInfo['dirname'] . '/';
		// add update permissions
		if (Filesystem::isUpdatable($dir)) {
			$permissions |= \OCP\PERMISSION_UPDATE;
		}
		// add delete permissions
		if (Filesystem::isDeletable($dir)) {
			$permissions |= \OCP\PERMISSION_DELETE;
		}
		// add share permissions
		if (Filesystem::isSharable($dir)) {
			$permissions |= \OCP\PERMISSION_SHARE;
		}
		// return
		return $permissions;
	}

}
