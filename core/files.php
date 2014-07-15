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

namespace OCA\Search_Lucene\Core;

use OC\Files\Node\Folder;

class Files {

	/**
	 * @var string
	 */
	private $userId;

	/**
	 * @var \OC\Files\Node\Folder
	 */
	private $rootFolder;

	public function __construct($userId, Folder $rootFolder){
		$this->userId = $userId;
		$this->rootFolder = $rootFolder;
	}
	/**
	 * Returns a folder for the users 'files' folder
	 * Warning, this will tear down the current filesystem
	 *
	 * @param string $userId
	 * @return \OCP\Files\Folder
	 */
	public function setUpUserFolder($userId = null) {
		$userHome = $this->setUpUserHome($userId);

		$dir = 'files';
		$folder = null;
		if(!$userHome->nodeExists($dir)) {
			$folder = $userHome->newFolder($dir);
		} else {
			$folder = $userHome->get($dir);
		}

		return $folder;

	}

	/**
	 * @param string $userId
	 * @return null|\OCP\Files\Folder
	 */
	public function setUpIndexFolder($userId = null) {
		$userHome = $this->setUpUserHome($userId);
		// TODO profile: encrypt the index on logout, decrypt on login
		//return OCP\Files::getStorage('search_lucene');
		// FIXME \OC::$server->getAppFolder() returns '/search'
		//$indexFolder = \OC::$server->getAppFolder();

		$dir = 'lucene_index';
		$folder = null;
		if(!$userHome->nodeExists($dir)) {
			$folder = $userHome->newFolder($dir);
		} else {
			$folder = $userHome->get($dir);
		}

		return $folder;
	}

	/**
	 * @param string $userId
	 * @return null|\OCP\Files\Folder
	 */
	public function setUpUserHome($userId = null) {
		if (is_null($userId)) {
			$userId = $this->userId;
		}
		if (!\OCP\User::userExists($userId)) {
			return null;
		}
		if ($userId !== $this->userId) {
			\OC_Util::tearDownFS();
			\OC_User::setUserId($userId);
			$this->userId = $userId;
		}
		\OC_Util::setupFS($userId);

		$dir = '/' . $userId;
		$folder = null;

		if(!$this->rootFolder->nodeExists($dir)) {
			$folder = $this->rootFolder->newFolder($dir);
		} else {
			$folder = $this->rootFolder->get($dir);
		}

		return $folder;

	}
}
