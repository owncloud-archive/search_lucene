<?php

namespace OCA\Search_Lucene;

class Util {

	/**
	 * Returns a folder for the users 'files' folder
	 * Warning, this will tear down the current filesystem
	 *
	 * @param string $user the user id
	 * @return \OCP\Files\Folder
	 */
	static function setUpUserFolder($user = null) {
		$userHome = self::setUpUserHome($user);

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
	static function setUpIndexFolder($user = null) {
		$userHome = self::setUpUserHome($user);
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
	static function setUpUserHome($user = null) {
		if (is_null($user)) {
			$user = \OCP\User::getUser();
		}
		if (!\OCP\User::userExists($user)) {
			return null;
		}
		if ($user !==  \OCP\User::getUser()) {
			\OC_Util::tearDownFS();
			\OC_User::setUserId($user);
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
