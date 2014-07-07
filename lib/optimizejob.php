<?php

namespace OCA\Search_Lucene;

class OptimizeJob extends \OC\BackgroundJob\TimedJob {

	public function __construct() {
		$this->setInterval(86400); //execute at most once a day
	}

	public function run($arguments){
		if (!empty($arguments['user'])) {
			$user = $arguments['user'];
			\OCP\Util::writeLog(
				'search_lucene',
				'background job optimizing index for '.$user,
				\OCP\Util::DEBUG
			);
			$folder = Util::setUpIndexFolder($user);
			$lucene = new Lucene($folder);
			
			$lucene->optimizeIndex();
		} else {
			\OCP\Util::writeLog(
				'search_lucene',
				'optimize job did not receive user in arguments: '.json_encode($arguments),
				\OCP\Util::DEBUG
			);
		}
	}
}
