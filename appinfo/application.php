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

namespace OCA\Search_Lucene\AppInfo;

use OCA\Search_Lucene\Controller\ApiController;
use OCA\Search_Lucene\Core\Logger;
use OCA\Search_Lucene\Db\StatusMapper;
use OCA\Search_Lucene\Lucene\Index;
use OCA\Search_Lucene\Lucene\Indexer;
use OCA\Search_Lucene\Core\Files;
use OCP\AppFramework\App;

class Application extends App {

	public function __construct (array $urlParams=array()) {
		parent::__construct('search_lucene', $urlParams);

		$container = $this->getContainer();

		//add 3rdparty composer autoloader
		require_once __DIR__ . '/../3rdparty/autoload.php';

		/**
		 * Controller
		 */
		$container->registerService('ApiController', function($c) {
			return new ApiController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('StatusMapper'),
				$c->query('Index'),
				$c->query('Indexer')
			);
		});

		/**
		 * Lucene
		 */
		$container->registerService('Index', function($c) {
			$index = new Index(
				$c->query('FileUtility'),
				$c->query('Logger')
			);
			$index->openOrCreate();

			return $index;
		});

		$container->registerService('Indexer', function($c) {
			return new Indexer(
				$c->query('FileUtility'),
				$c->query('ServerContainer'),
				$c->query('Index'),
				$c->query('SkippedDirs'),
				$c->query('StatusMapper'),
				$c->query('Logger')
			);
		});

		$container->registerService('SkippedDirs', function($c) {
			/** @var \OCP\IConfig $config */
			$config = $c->query('ServerContainer')->getConfig();
			return explode(
				';',
				$config->getUserValue($c->query('UserId'), 'search_lucene', 'skipped_dirs', '.git;.svn;.CVS;.bzr')
			);
		});

		/**
		 * Mappers
		 */
		$container->registerService('StatusMapper', function($c) {
			return new StatusMapper(
				$c->query('Db'),
				$c->query('Logger')
			);
		});

		/**
		 * Core
		 */
		$container->registerService('UserId', function($c) {
			$user = $c->query('ServerContainer')->getUserSession()->getUser();
			if ($user) {
				return $c->query('ServerContainer')->getUserSession()->getUser()->getUID();
			}
			return false;
		});

		$container->registerService('Logger', function($c) {
			return new Logger(
				$c->query('AppName'),
				$c->query('ServerContainer')->getLogger()
			);
		});

		$container->registerService('Db', function($c) {
			return $c->query('ServerContainer')->getDb();
		});

		$container->registerService('FileUtility', function($c) {
			return new Files(
				$c->query('ServerContainer')->getUserManager(),
				$c->query('ServerContainer')->getUserSession(),
				$c->query('RootFolder')
			);
		});

		$container->registerService('RootFolder', function($c) {
			return $c->query('ServerContainer')->getRootFolder();
		});

	}


}
