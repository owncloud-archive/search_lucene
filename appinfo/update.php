<?php

$currentVersion=OCP\Config::getAppValue('search_lucene', 'installed_version');

if (version_compare($currentVersion, '0.5.0', '<')) {
	//clear old background jobs
	$stmt = OCP\DB::prepare('DELETE FROM `*PREFIX*queuedtasks` WHERE `app`=?');
	$stmt->execute(array('search_lucene'));
}
