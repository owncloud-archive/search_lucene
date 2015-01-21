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

use OCA\Search_Lucene\Jobs\DeleteJob;
use OCA\Search_Lucene\Jobs\OptimizeJob;

// --- always add js & css -----------------------------------------------

OCP\Util::addScript('search_lucene', 'checker');
OCP\Util::addStyle('search_lucene', 'lucene');

// --- replace default file search provider -----------------------------------------------

//add search provider
\OC::$server->getSearch()->registerProvider('OCA\Search_Lucene\Search\LuceneProvider', array('apps' => array('files')));

// add background job for index optimization when we know for which user:
if (\OC::$server->getUserSession()->getUser()) {
	$arguments = array('user' => \OC::$server->getUserSession()->getUser()->getUID());
	\OC::$server->getJobList()->add(new OptimizeJob(), $arguments);
	\OC::$server->getJobList()->add(new DeleteJob(), $arguments);
}

// --- add hooks -----------------------------------------------

//post_create is ignored, as write will be triggered afterwards anyway

//connect to the filesystem for auto updating
OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_post_write,
		'OCA\Search_Lucene\Hooks\Files',
		OCA\Search_Lucene\Hooks\Files::handle_post_write);

//connect to the filesystem for renaming
OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		OC\Files\Filesystem::signal_post_rename,
		'OCA\Search_Lucene\Hooks\Files',
		OCA\Search_Lucene\Hooks\Files::handle_post_rename);
