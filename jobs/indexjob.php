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

namespace OCA\Search_Lucene\Jobs;

use OCA\Search_Lucene\AppInfo\Application;

class IndexJob extends \OC\BackgroundJob\QueuedJob {

	public function run($arguments){
		$app = new Application();
		$container = $app->getContainer();
		if (isset($arguments['user'])) {
			$userId = $arguments['user'];


			$folder = $container->query('FileUtility')->setUpUserFolder($userId);

			if ($folder) {

				$fileIds = $container->query('StatusMapper')->getUnindexed();

				$container->query('Logger')->
					log('background job indexing '.count($fileIds).' files for '.$userId, 'debug' );

				$container->query('Indexer')->indexFiles($fileIds);

			}
		} else {
			$container->query('Logger')->
				log('indexer job did not receive user in arguments: '.json_encode($arguments), 'debug' );
		}
 	}
}
