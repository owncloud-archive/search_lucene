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

$currentVersion = \OC::$server->getConfig()->getAppValue('search_lucene', 'installed_version');

if (version_compare($currentVersion, '0.6.0', '<')) {
	//force reindexing of files
	$stmt = OCP\DB::prepare('DELETE FROM `*PREFIX*lucene_status`');
	$stmt->execute();
}