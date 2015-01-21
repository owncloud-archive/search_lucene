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

namespace OCA\Search_Lucene\Hooks;

use OCA\Search_Lucene\AppInfo\Application;
use OCA\Search_Lucene\Db\StatusMapper;
use OCA\Search_Lucene\Jobs\IndexJob;
use OCP\BackgroundJob;
use OCP\Files\File;
use OCP\Files\Folder;

class Files {

	/**
	 * handle for indexing file
	 */
	const handle_post_write = 'indexFile';

	/**
	 * handle for renaming file
	 */
	const handle_post_rename = 'renameFile';

	/**
	 * handle file writes (triggers reindexing)
	 * 
	 * the file indexing is queued as a background job
	 * 
	 * @param $param array from postWriteFile-Hook
	 */
	public static function indexFile(array $param) {

		$app = new Application();
		$container = $app->getContainer();
		$userId = $container->query('UserId');

		if (!empty($userId)) {

			// mark written file as new
			/** @var Folder $userFolder */
			$userFolder = $container->query('ServerContainer')->getUserFolder();
			$node = $userFolder->get($param['path']);
			/** @var StatusMapper $mapper */
			$mapper = $container->query('StatusMapper');
			$status = $mapper->getOrCreateFromFileId($node->getId());

			// only index files
			if ($node instanceof File) {
				$mapper->markNew($status);

				//Add Background Job:
				\OC::$server->getJobList()->add(new IndexJob(), array('user' => $userId));
			} else {
				$mapper->markSkipped($status);
			}
		} else {
			$container->query('Logger')->debug(
				'Hook indexFile could not determine user when called with param '.json_encode($param)
			);
		}
	}

	/**
	 * handle file renames (triggers indexing and deletion)
	 * 
	 * @param $param array from postRenameFile-Hook
	 */
	public static function renameFile(array $param) {
		$app = new Application();
		$container = $app->getContainer();

		if (!empty($param['oldpath'])) {
			//delete from lucene index
			$container->query('Index')->deleteFile($param['oldpath']);
		}

		if (!empty($param['newpath'])) {
			/** @var Folder $userFolder */
			$userFolder = $container->query('ServerContainer')->getUserFolder();
			$node = $userFolder->get($param['newpath']);

			// only index files
			if ($node instanceof File) {
				$mapper = $container->query('StatusMapper');
				$mapper->getOrCreateFromFileId($node->getId());
				self::indexFile(array('path'=>$param['newpath']));
			}

		}
	}

}
