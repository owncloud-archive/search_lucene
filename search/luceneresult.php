<?php
/**
 * ownCloud - search_lucene
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @copyright Jörn Friedrich Dreyer 2012-2014
 */

namespace OCA\Search_Lucene\Search;
use OC\Files\Filesystem;

/**
 * A found file
 */
class LuceneResult extends \OC\Search\Result\File {

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
	 * @param \Zend_Search_Lucene_Search_QueryHit $hit file data given by provider
	 */
	public function __construct(\Zend_Search_Lucene_Search_QueryHit $hit) {
		$this->id = (string)$hit->fileid;
		$this->path = Filesystem::getView()->getRelativePath($hit->path);
		$this->name = basename($this->path);
		$this->size = (int)$hit->size;
		$this->score = $hit->score;
		$this->link = \OCP\Util::linkTo(
			'files',
			'index.php',
			array('dir' => dirname($this->path), 'file' => $this->name)
		);
		$this->permissions = self::get_permissions($this->path);
		$this->modified = (int)$hit->mtime;
		$this->mime_type = $hit->mimetype;
	}

}
