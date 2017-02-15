<?php
/**
 * ownCloud - search_lucene
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @copyright Thomas Müller 2015
 */

namespace OCA\Search_Lucene\Jobs;

use OCA\Search_Lucene\AppInfo\Application;
use OCA\Search_Lucene\Core\Logger;
use OC\BackgroundJob\TimedJob;
use OCA\Search_Lucene\Db\Status;
use OCA\Search_Lucene\Db\StatusMapper;
use OCA\Search_Lucene\Lucene\Index;
use OCP\App;

class DeleteJob extends TimedJob {

	public function __construct() {
		//execute once a minute
		$this->setInterval(60);
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

		/** @var Index $index */
		$index = $container->query('Index');

		/** @var StatusMapper $mapper */
		$mapper = $container->query('StatusMapper');

		$deletedIds = $mapper->getDeleted();
		$count = 0;
		foreach ($deletedIds as $fileId) {
			$logger->debug( 'deleting status for ('.$fileId.') ' );
			//delete status
			$status = new Status($fileId);
			$mapper->delete($status);
			//delete from lucene
			$count += $index->deleteFile($fileId);
		}

		$logger->debug( 'removed '.$count.' files from index' );
 	}
}
