<?php
/**
 * ownCloud - search_lucene
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @copyright Jörn Friedrich Dreyer 2012-2014
 */

// --- always add js & css -----------------------------------------------

OCP\Util::addScript('search_lucene', 'checker');
OCP\Util::addStyle('search_lucene', 'lucene');

// --- replace default file search provider -----------------------------------------------

//add search provider
\OC::$server->getSearch()->registerProvider('OCA\Search_Lucene\Search\LuceneProvider');

// add background job for index optimization:

$arguments = array('user' => \OCP\User::getUser());

//only when we know for which user:
if ($arguments['user']) {
	\OCP\BackgroundJob::registerJob( 'OCA\Search_Lucene\Jobs\OptimizeJob', $arguments );
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

//listen for file deletions to clean the database
OCP\Util::connectHook(
		OC\Files\Filesystem::CLASSNAME,
		'post_delete', //FIXME add referenceable constant in core
		'OCA\Search_Lucene\Hooks\Files',
		OCA\Search_Lucene\Hooks\Files::handle_delete);
