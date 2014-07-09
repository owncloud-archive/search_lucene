<?php
/**
 * ownCloud - cloudmaster
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @copyright Jörn Friedrich Dreyer 2014
 */

namespace OCA\Search_Lucene\AppInfo;

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
$application = new Application();

$application->registerRoutes($this, array('routes' => array(
	array('name' => 'api#index', 'url' => '/indexer/index', 'verb' => 'GET'),
	array('name' => 'api#optimize', 'url' => '/indexer/optimize', 'verb' => 'POST'),
)));
