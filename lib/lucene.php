<?php

namespace OCA\Search_Lucene;

use \OCP\Util;

/**
 * @author Jörn Dreyer <jfd@butonic.de>
 */
class Lucene {

	/**
	 * classname which used for hooks handling
	 * used as signalclass in OC_Hooks::emit()
	 */
	const CLASSNAME = 'Lucene';

	public $index;

	/**
	 * The default location of '<user>/lucene_index' can be overridden by passing in a different folder
	 *
	 * @param \OCP\Files\Folder $indexFolder location of the lucene_index
	 */
	public function __construct(\OCP\Files\Folder $indexFolder = null) {
		if (is_null($indexFolder)) {
			$indexFolder = $this->getIndexFolder();
		}
		$this->index = $this->openOrCreate($indexFolder);
	}

	/**
	 * @return null|\OCP\Files\Folder
	 */
	private function getIndexFolder() {

		// TODO profile: encrypt the index on logout, decrypt on login
		//return OCP\Files::getStorage('search_lucene');
		// FIXME \OC::$server->getAppFolder() returns '/search'
		//$indexFolder = \OC::$server->getAppFolder();

		$root = \OC::$server->getRootFolder();
		$dir = '/'.\OCP\User::getUser();
		$userFolder = null;
		if(!$root->nodeExists($dir)) {
			$userFolder = $root->newFolder($dir);
		} else {
			$userFolder = $root->get($dir);
		}
		$dir = 'lucene_index';
		$indexFolder = null;
		if(!$userFolder->nodeExists($dir)) {
			$indexFolder = $userFolder->newFolder($dir);
		} else {
			$indexFolder = $userFolder->get($dir);
		}

		return $indexFolder;
	}

	/**
	 * opens or creates the given lucene index
	 * 
	 * stores the index in $indexFolder
	 * 
	 * @author Jörn Dreyer <jfd@butonic.de>
	 *
	 * @param \OCP\Files\Folder $indexFolder
	 * @return \Zend_Search_Lucene_Interface
	 * @throws \Exception
	 */
	private function openOrCreate(\OCP\Files\Folder $indexFolder) {

		if (is_null($indexFolder)) {
			throw new \Exception('No Index folder given');
		}

		try {

			$storage = $indexFolder->getStorage();
			$localPath = $storage->getLocalFile($indexFolder->getInternalPath());

			//let lucene search for numbers as well as words
			\Zend_Search_Lucene_Analysis_Analyzer::setDefault(
				new \Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive()
			);

			// can we use the index?
			if ($indexFolder->nodeExists('/v0.6.0')) {
				// correct index present
				$index = \Zend_Search_Lucene::open($localPath);
			} else if (file_exists($indexFolder)) {
				Util::writeLog(
					'search_lucene',
					'recreating outdated lucene index',
					Util::INFO
				);
				$indexFolder->delete();
				$index = \Zend_Search_Lucene::create($localPath);
				touch($indexFolder.'/v0.6.0');
			} else {
				$index = \Zend_Search_Lucene::create($localPath);
				touch($indexFolder.'/v0.6.0');
			}
			return $index;
		} catch ( \Exception $e ) {
			Util::writeLog(
				'search_lucene',
				$e->getMessage().' Trace:\n'.$e->getTraceAsString(),
				Util::ERROR
			);
		}

		return null;

	}

	/**
	 * optimizes the lucene index
	 *
	 * @author Jörn Dreyer <jfd@butonic.de>
	 * 
	 * @return void
	 */
	public function optimizeIndex() {

		Util::writeLog(
			'search_lucene',
			'optimizing index',
			Util::DEBUG
		);

		$this->index->optimize();

	}

	/**
	 * upates a file in the lucene index
	 * 
	 * 1. the file is deleted from the index
	 * 2. the file is readded to the index
	 * 3. the file is marked as index in the status table
	 * 
	 * @author Jörn Dreyer <jfd@butonic.de>
	 * 
	 * @param \Zend_Search_Lucene_Document $doc  the document to store for the path
	 * @param int $fileid fileid to update
	 * 
	 * @return void
	 */
	public function updateFile(
		\Zend_Search_Lucene_Document $doc,
		$fileid
	) {

		// TODO profile perfomance for searching before adding to index
		$this->deleteFile($fileid);

		Util::writeLog(
			'search_lucene',
			'adding ' . $fileid .' '.json_encode($doc),
			Util::DEBUG
		);
		
		// Add document to the index
		$this->index->addDocument($doc);

		$this->index->commit();

	}

	/**
	 * removes a file frome the lucene index
	 * 
	 * @author Jörn Dreyer <jfd@butonic.de>
	 * 
	 * @param int $fileid fileid to remove from the index
	 * 
	 * @return int count of deleted documents in the index
	 */
	public function deleteFile($fileid) {

		$hits = $this->index->find( 'fileid:' . $fileid );

		Util::writeLog(
			'search_lucene',
			'found ' . count($hits) . ' hits for fileid ' . $fileid,
			Util::DEBUG
		);

		foreach ($hits as $hit) {
			Util::writeLog(
				'search_lucene',
				'removing ' . $hit->id . ':' . $hit->path . ' from index',
				Util::DEBUG
			);
			$this->index->delete($hit);
		}
		
		return count($hits);
	}

	public function find ($query) {
		return $this->index->find($query);
	}

}
