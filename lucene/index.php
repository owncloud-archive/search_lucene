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

namespace OCA\Search_Lucene\Lucene;
use OCA\Search_Lucene\Core\Files;
use OCP\ILogger;

/**
 * @author Jörn Dreyer <jfd@butonic.de>
 */
class Index {

	public $files;
	/**
	 * @var \Zend_Search_Lucene
	 */
	public $index;
	/**
	 * @var \OCP\ILogger
	 */
	public $logger;

	public function __construct(Files $files, ILogger $logger) {
		$this->files = $files;
		$this->logger = $logger;
	}

	/**
	 * opens or creates the given lucene index
	 *
	 * @author Jörn Dreyer <jfd@butonic.de>
	 *
	 * @throws \Exception
	 */
	public function openOrCreate() {

		$indexFolder = $this->files->setUpIndexFolder();

		if (is_null($indexFolder)) {
			throw new \Exception('Could not set up index folder');
		}

		$storage = $indexFolder->getStorage();
		$localPath = $storage->getLocalFile($indexFolder->getInternalPath());

		//let lucene search for numbers as well as words
		\Zend_Search_Lucene_Analysis_Analyzer::setDefault(
			new \Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive()
		);

		// can we use the index?
		if ($indexFolder->nodeExists('v0.6.0')) {
			// correct index present
			$this->index = \Zend_Search_Lucene::open($localPath);
		} else {
			$this->logger->info( 'recreating outdated lucene index' );
			$indexFolder->delete();
			$this->index = \Zend_Search_Lucene::create($localPath);
			$indexFolder->newFile('v0.6.0');
		}

	}

	/**
	 * optimizes the lucene index
	 *
	 * @author Jörn Dreyer <jfd@butonic.de>
	 * 
	 * @return void
	 */
	public function optimizeIndex() {
		$this->logger->debug( 'optimizing index' );
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
	 * @param int $fileId fileid to update
	 * @param bool $commit
	 * 
	 * @return void
	 */
	public function updateFile(
		\Zend_Search_Lucene_Document $doc,
		$fileId,
		$commit = true
	) {

		// TODO profile perfomance for searching before adding to index
		$this->deleteFile($fileId);

		$this->logger->debug( 'adding ' . $fileId .' '.json_encode($doc) );
		
		// Add document to the index
		$this->index->addDocument($doc);

		if ($commit) {
			$this->index->commit();
		}

	}

	/**
	 * removes a file from the lucene index
	 * 
	 * @author Jörn Dreyer <jfd@butonic.de>
	 * 
	 * @param int $fileId fileid to remove from the index
	 * 
	 * @return int count of deleted documents in the index
	 */
	public function deleteFile($fileId) {

		$hits = $this->index->find( 'fileId:' . $fileId );

		$this->logger->debug( 'found ' . count($hits) . ' hits for fileId ' . $fileId );

		foreach ($hits as $hit) {
			$this->logger->debug( 'removing ' . $hit->id . ':' . $hit->path . ' from index' );
			$this->index->delete($hit);
		}
		
		return count($hits);
	}

	public function find ($query) {
		return $this->index->find($query);
	}

	public function commit () {
		$this->index->commit();
	}

}
