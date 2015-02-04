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

namespace OCA\Search_Lucene\Core;

use OCP\Files\Folder;
use OCP\IUserManager;
use OCP\IUserSession;

class Files {

	/**
	 * @var IUserManager
	 */
	private $userManager;
	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var Folder
	 */
	private $rootFolder;

	public function __construct(IUserManager $userManager, IUserSession $userSession, Folder $rootFolder){
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->rootFolder = $rootFolder;
	}
	/**
	 * Returns a folder for the users 'files' folder
	 * Warning, this will tear down the current filesystem
	 *
	 * @param string $userId
	 * @return \OCP\Files\Folder
	 * @throws SetUpException
	 */
	public function setUpUserFolder($userId = null) {

		$userHome = $this->setUpUserHome($userId);

		return $this->getOrCreateSubFolder($userHome, 'files');

	}

	/**
	 * @param string $userId
	 * @return \OCP\Files\Folder
	 * @throws SetUpException
	 */
	public function setUpIndexFolder($userId = null) {
		// TODO profile: encrypt the index on logout, decrypt on login
		//return OCP\Files::getStorage('search_lucene');
		// FIXME \OC::$server->getAppFolder() returns '/search'
		//$indexFolder = \OC::$server->getAppFolder();

		$userHome = $this->setUpUserHome($userId);

		return $this->getOrCreateSubFolder($userHome, 'lucene_index');
	}

	/**
	 * @param string $userId
	 * @return \OCP\Files\Folder
	 * @throws SetUpException
	 */
	public function setUpUserHome($userId = null) {

		if (is_null($userId)) {
			$user = $this->userSession->getUser();
		} else {
			$user = $this->userManager->get($userId);
		}
		if (is_null($user) || !$this->userManager->userExists($user->getUID())) {
			throw new SetUpException('could not set up user home for '.json_encode($user));
		}
		if ($user !== $this->userSession->getUser()) {
			\OC_Util::tearDownFS();
			$this->userSession->setUser($user);
		}
		\OC_Util::setupFS($user->getUID());

		return $this->getOrCreateSubFolder($this->rootFolder, '/' . $user->getUID());

	}

	/**
	 * @param \OCP\Files\Folder $parent
	 * @param string $folderName
	 * @return \OCP\Files\Folder
	 * @throws SetUpException
	 */
	private function getOrCreateSubFolder(Folder $parent, $folderName) {
		if($parent->nodeExists($folderName)) {
			return $parent->get($folderName);
		} else {
			return $parent->newFolder($folderName);
		}
	}

}
