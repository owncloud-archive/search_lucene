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

namespace OCA\Search_Lucene\Jobs;

use OCA\Search_Lucene\AppInfo\Application;
use OCA\Search_Lucene\Core\Logger;
use OC\BackgroundJob\QueuedJob;
use OCP\App;

class IndexJob extends QueuedJob {

	/**
	 * @param array $arguments
	 */
	public function run($arguments){
		if (!App::isEnabled('search_lucene')) {
			return;
		}
		
		$app = new Application();
		$container = $app->getContainer();

		/** @var Logger $logger */
		$logger = $container->query('Logger');

		if (isset($arguments['user'])) {
			$userId = $arguments['user'];

			$folder = $container->query('FileUtility')->setUpUserFolder($userId);

			if ($folder) {

				$fileIds = $container->query('StatusMapper')->getUnindexed();

				$logger->debug('background job indexing '.count($fileIds).' files for '.$userId );

				$container->query('Indexer')->indexFiles($fileIds);

			}
		} else {
			$logger->debug('indexer job did not receive user in arguments: '.json_encode($arguments));
		}
 	}
}
