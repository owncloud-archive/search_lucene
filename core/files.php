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
	 * @param string $user the user id
	 * @return \OCP\Files\Folder
	 */
	public function setUpUserFolder($user = null) {
		$userHome = $this->setUpUserHome($user);

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
	 * @return null|\OCP\Files\Folder
	 */
	public function setUpIndexFolder($user = null) {
		$userHome = $this->setUpUserHome($user);
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
	 * @return null|\OCP\Files\Folder
	 */
	public function setUpUserHome($user = null) {
		if (is_null($user)) {
			$user = $this->userId;
		}
		if (!\OCP\User::userExists($user)) {
			return null;
		}
		if ($user !== $this->userId) {
			\OC_Util::tearDownFS();
			\OC_User::setUserId($user);
			$this->userId = $user;
		}
		\OC_Util::setupFS($user);

		$dir = '/' . $user;
		$folder = null;

		if(!$this->rootFolder->nodeExists($dir)) {
			$folder = $this->rootFolder->newFolder($dir);
		} else {
			$folder = $this->rootFolder->get($dir);
		}

		return $folder;

	}
}
