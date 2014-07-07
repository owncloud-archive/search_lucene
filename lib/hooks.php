<?php

namespace OCA\Search_Lucene;

use \OCP\BackgroundJob;
use \OCP\Util;

/**
 * 
 * @author Jörn Dreyer <jfd@butonic.de>
 */
class Hooks {

	/**
	 * classname which used for hooks handling
	 * used as signalclass in OC_Hooks::emit()
	 */
	const CLASSNAME = 'Hooks';

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
		$user = \OCP\User::getUser();
		if (!empty($user)) {

			// mark written file as new
			$userFolder = \OC::$server->getUserFolder();
			$node = $userFolder->get($param['path']);
			$status = Status::fromFileId($node->getId());

			// only index files
			if ($node instanceof \OCP\Files\File) {
				/** @var \OCP\Files\File $node */
				$status->markNew();
			} else {
				$status->markSkipped();
			}

			//Add Background Job:
			BackgroundJob::registerJob( '\OCA\Search_Lucene\IndexJob', array('user' => $user) );
		} else {
			\OCP\Util::writeLog(
				'search_lucene',
				'Hook indexFile could not determine user when called with param '.json_encode($param),
				\OCP\Util::DEBUG
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
		if (!empty($param['oldpath'])) {
			//delete from lucene index
			$lucene = new Lucene();
			$lucene->deleteFile($param['oldpath']);
		}
		if (!empty($param['newpath'])) {
			$userFolder = \OC::$server->getUserFolder();
			$folder = $userFolder->get($param['newpath']);
			Status::fromFileId($folder->getId())->markNew();
			self::indexFile(array('path'=>$param['newpath']));
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
		$lucene = new Lucene();
		$deletedIds = Status::getDeleted();
		$count = 0;
		foreach ($deletedIds as $fileId) {
			Util::writeLog(
				'search_lucene',
				'deleting status for ('.$fileId.') ',
				Util::DEBUG
			);
			//delete status
			Status::delete($fileId);
			//delete from lucene
			$count += $lucene->deleteFile($fileId);

		}
		Util::writeLog(
			'search_lucene',
			'removed '.$count.' files from index',
			Util::DEBUG
		);

	}

	/**
	 * was used by backgroundjobs to index individual files
	 * 
	 * @deprecated since version 0.6.0
	 * 
	 * @author Jörn Dreyer <jfd@butonic.de>
	 *
	 * @param $param array from deleteFile-Hook
	 */
	static public function doIndexFile(array $param) {/* ignore */}

}
