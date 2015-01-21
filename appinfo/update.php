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

if (version_compare($currentVersion, '0.5.0', '<')) {
	//clear old background jobs
	$stmt = OCP\DB::prepare('DELETE FROM `*PREFIX*queuedtasks` WHERE `app`=?');
	$stmt->execute(array('search_lucene'));
}
