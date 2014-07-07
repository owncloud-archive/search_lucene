<?php

namespace OCA\Search_Lucene;

class IndexJob extends \OC\BackgroundJob\Job {


	public function run($arguments){
		if (isset($arguments['user'])) {
			$user = $arguments['user'];

			$folder = Util::setUpUserFolder($user);
			if ($folder) {

				$fileIds = Status::getUnindexed();

				\OCP\Util::writeLog(
					'search_lucene',
					'background job indexing '.count($fileIds).' files for '.$user,
					\OCP\Util::DEBUG
				);

				$lucene = new Lucene();
				$indexer = new Indexer($folder, $lucene);

				$indexer->indexFiles($fileIds);
			}
		} else {
			\OCP\Util::writeLog(
				'search_lucene',
				'indexer job did not receive user in arguments: '.json_encode($arguments),
				\OCP\Util::DEBUG
			);
		}
 	}
}
