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

class Files {

	private $userId;

	public function __construct($userId){
		$this->userId = $userId;
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
		$root = \OC::$server->getRootFolder();
		$folder = null;

		if(!$root->nodeExists($dir)) {
			$folder = $root->newFolder($dir);
		} else {
			$folder = $root->get($dir);
		}

		return $folder;

	}
}
