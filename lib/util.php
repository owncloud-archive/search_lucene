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

		$dir = '/files';
		if(!$folder->nodeExists($dir)) {
			$folder = $folder->newFolder($dir);
		} else {
			$folder = $folder->get($dir);
		}

		return $folder;

	}

}
