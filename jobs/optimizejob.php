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
use OC\BackgroundJob\TimedJob;
use OCP\App;

class OptimizeJob extends TimedJob {

	public function __construct() {
		$this->setInterval(86400); //execute at most once a day
	}

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

    $logger->debug('background job optimizing index');
    $container->query('FileUtility')->setUpIndexFolder();
    $container->query('Index')->optimizeIndex();
	}
}
