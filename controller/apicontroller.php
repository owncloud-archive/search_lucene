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

namespace OCA\Search_Lucene\Controller;

use OCA\Search_Lucene\Db\StatusMapper;
use OCA\Search_Lucene\Lucene\Index;
use OCA\Search_Lucene\Lucene\Indexer;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class ApiController extends Controller {

	/**
	 * @var $mapper StatusMapper
	 */
	private $mapper;

	/**
	 * @var $index Index
	 */
	private $index;

	/**
	 * @var $indexer Indexer
	 */
	private $indexer;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param StatusMapper $mapper
	 * @param Index $index
	 * @param Indexer $indexer
	 */
	public function __construct($appName, IRequest $request, StatusMapper $mapper, Index $index, Indexer $indexer) {
		parent::__construct($appName, $request);
		$this->mapper = $mapper;
		$this->index = $index;
		$this->indexer = $indexer;
	}


	/**
	 * index the given fileId or, if not given, all unindexed files
	 * @param int $fileId optional fileId to index
	 * @NoAdminRequired
	 */
	public function index($fileId = null) {
		if ( isset($fileId) ){
			$fileIds = array($fileId);
		} else {
			$fileIds = $this->mapper->getUnindexed();
		}

		//TODO use public api when available in \OCP\AppFramework\IApi
		$eventSource = new \OC_EventSource();
		$eventSource->send('count', count($fileIds));

		$this->indexer->indexFiles($fileIds, $eventSource);

		$eventSource->send('done', '');
		$eventSource->close();

		// end script execution to prevent app framework from sending headers after
		// the eventsource is closed
		exit();
	}


	/**
	 * Optimize the index
	 * @NoAdminRequired
	 */
	public function optimize() {
		$this->index->optimizeIndex();
	}

}