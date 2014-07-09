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

class OptimizeJob extends \OC\BackgroundJob\TimedJob {

	public function __construct() {
		$this->setInterval(86400); //execute at most once a day
	}

	public function run($arguments){
		$app = new Application();
		$container = $app->getContainer();

		if (!empty($arguments['user'])) {
			$userId = $arguments['user'];
			$container->query('Logger')->log('background job optimizing index for '.$userId, 'debug' );
			$folder = $container->query('FileUtility')->setUpIndexFolder($userId);
			//TODO use folder?
			$container->query('Index')->optimizeIndex();
		} else {
			$container->query('Logger')->
				log('indexer job did not receive user in arguments: '.json_encode($arguments), 'debug' );
		}
	}
}
