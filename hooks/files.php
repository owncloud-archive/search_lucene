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

namespace OCA\Search_Lucene\Hooks;

use OCA\Search_Lucene\AppInfo\Application;
use OCA\Search_Lucene\Core\Logger;
use OCA\Search_Lucene\Db\Status;
use OCA\Search_Lucene\Db\StatusMapper;
use OCA\Search_Lucene\Lucene\Index;
use OCP\BackgroundJob;
use OCP\Files\File;
use OCP\Files\Folder;

/**
 * 
 * @author Jörn Dreyer <jfd@butonic.de>
 */
class Files {

	/**
	 * handle for indexing file
	 *
	 * @param string $path
	 */
	const handle_post_write = 'indexFile';

	/**
	 * handle for renaming file
	 *
	 * @param string $path
	 */
	const handle_post_rename = 'renameFile';

	/**
	 * handle for removing file
	 *
	 * @param string $path
	 */
	const handle_delete = 'deleteFile';

	/**
	 * handle file writes (triggers reindexing)
	 * 
	 * the file indexing is queued as a background job
	 * 
	 * @author Jörn Dreyer <jfd@butonic.de>
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
				BackgroundJob::registerJob( 'OCA\Search_Lucene\Jobs\IndexJob', array('user' => $userId) );
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
	 * @author Jörn Dreyer <jfd@butonic.de>
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

	/**
	 * deleteFile triggers the removal of any deleted files from the index
	 *
	 * @author Jörn Dreyer <jfd@butonic.de>
	 *
	 * @param $param array from deleteFile-Hook
	 */
	static public function deleteFile(array $param) {
		// we cannot use post_delete as $param would not contain the id
		// of the deleted file and we could not fetch it with getId
		$app = new Application();
		$container = $app->getContainer();

		/** @var Index $index */
		$index = $container->query('Index');

		/** @var StatusMapper $mapper */
		$mapper = $container->query('StatusMapper');

		/** @var Logger $logger */
		$logger = $container->query('Logger');

		$deletedIds = $mapper->getDeleted();
		$count = 0;
		foreach ($deletedIds as $fileId) {
			$logger->debug( 'deleting status for ('.$fileId.') ' );
			//delete status
			$status = new Status($fileId);
			$mapper->delete($status);
			//delete from lucene
			$count += $index->deleteFile($fileId);

		}
		$logger->debug( 'removed '.$count.' files from index' );

	}

}
