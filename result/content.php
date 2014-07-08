<?php
/**
 * ownCloud
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Search_Lucene\Result;
use OC\Files\Filesystem;

/**
 * A found file
 */
class Content extends \OC\Search\Result\File {

	/**
	 * Type name; translated in templates
	 * @var string
	 */
	public $type = 'content';

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
			array('dir' => dirname($this->path), 'file' => basename($this->path))
		);
		$this->permissions = self::get_permissions($this->path);
		$this->modified = (int)$hit->mtime;
		$this->mime_type = $hit->mimetype;
	}

}
