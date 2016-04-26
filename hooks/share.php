<?php
/**
 * ownCloud - search_lucene
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Devin M. Ceartas <devin@nacredata.com>
 * @copyright Devin M. Ceartas 2016
 */

namespace OCA\Search_Lucene\Hooks;

use OCA\Search_Lucene\AppInfo\Application;

class Share {

	private static function reIndex($fileIds) {
		$app = new Application();
		$container = $app->getContainer();
		$container->query('Indexer')->indexFiles($fileIds);
	}

	public static function postShareHook(array $param) {
		if ('file' == $param['itemType']) {
			self::reIndex(array($param['itemSource']));
		} else {
			$app = new Application();
			$container = $app->getContainer();
			$files = $container->query('FilesInFolder')->files($param['itemSource']);
			self::reIndex($files);
		}
	}

	public static function postUnshareHook(array $param) {
		if ('file' == $param['itemType']) {
			self::reIndex(array($param['itemSource']));
		} else {
			$app = new Application();
			$container = $app->getContainer();
			$files = $container->query('FilesInFolder')->files($param['itemSource']);
			self::reIndex($files);
		}
	}
}
