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

namespace OCA\Search_Lucene\Lucene;

use OCA\Search_Lucene\Core\Files;
use OCA\Search_Lucene\Core\SetUpException;
use OCP\ILogger;
use ZendSearch\Lucene\Analysis\Analyzer\Analyzer;
use ZendSearch\Lucene\Analysis\Analyzer\Common\Utf8Num\CaseInsensitive;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Lucene;
use ZendSearch\Lucene\SearchIndexInterface;

class Index {

	public $files;
	/**
	 * @var SearchIndexInterface
	 */
	public $index;
	/**
	 * @var ILogger
	 */
	public $logger;

	public function __construct(Files $files, ILogger $logger) {
		$this->files = $files;
		$this->logger = $logger;
	}

	/**
	 * opens or creates the given lucene index
	 *
	 * @throws SetUpException
	 */
	public function openOrCreate() {

		$indexFolder = $this->files->setUpIndexFolder();

		$storage = $indexFolder->getStorage();
		$localPath = $storage->getLocalFolder($indexFolder->getInternalPath());

		//let lucene search for numbers as well as words
		Analyzer::setDefault(
			new CaseInsensitive()
		);

		// can we use the index?
		if ($indexFolder->nodeExists('v0.6.0')) {
			// correct index present
			$this->index = Lucene::open($localPath);
		} else {
			$this->logger->info( 'recreating outdated lucene index' );
			$indexFolder->delete();
			$this->index = Lucene::create($localPath);
			$indexFolder->newFile('v0.6.0');
		}

	}

	/**
	 * optimizes the lucene index
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
	 * @param Document $doc the document to store for the path
	 * @param int $fileId file id to update
	 * @param bool $commit
	 * 
	 * @return void
	 */
	public function updateFile(
		Document $doc,
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
	 * @param int $fileId file id to remove from the index
	 * 
	 * @return int count of deleted documents in the index
	 */
	public function deleteFile($fileId) {

		$hits = $this->index->find( 'fileId:' . $fileId );

		$this->logger->debug( 'found ' . count($hits) . ' hits for file id ' . $fileId );

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
