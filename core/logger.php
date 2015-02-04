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

namespace OCA\Search_Lucene\Core;

use OCP\ILogger;
use \OC\Log;

/**
 * Class Logger
 *
 * inserts the app name when not set in context
 *
 * @package OCA\Search_Lucene\Core
 */
class Logger extends Log {

	private $appName;
	private $logger;

	public function __construct($appName, ILogger $logger) {
		$this->appName = $appName;
		$this->logger = $logger;
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function log($level, $message, array $context = array()) {
		if (empty($context['app'])) {
			$context['app'] = $this->appName;
		}
		$this->logger->log($level, $message, $context);
	}
}

