<?php

/**
 * ownCloud search lucene
 *
 * @author Jörn Dreyer
 * @copyright 2014 Jörn Friedrich Dreyer jfd@butonic.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Search_Lucene\Tests\Unit;

use OCA\Search_Lucene\AppInfo\Application;
use OCA\Search_Lucene\Jobs\IndexJob;
use OCA\Search_Lucene\Jobs\OptimizeJob;
use OCA\Search_Lucene\Lucene\Index;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Search\Query;
use ZendSearch\Lucene\Search\QueryHit;

class TestOptimizeJob extends TestCase {

	public function setUp() {
		parent::setUp();

		$this->userSession->setUser(null);
		\OC_Util::tearDownFS();

		$job = new IndexJob();
		$job->run(array('user' => 'test'));

		$this->userSession->setUser(null);
		\OC_Util::tearDownFS();
	}

	function testOptimizeJob() {

		// preparation
		$app = new Application();
		$container = $app->getContainer();

		$job = new OptimizeJob();
		$job->run(array('user' => 'test'));

		// make sure we can still find documents

		// get an index
		/** @var Index $index */
		$index = $container->query('Index');

		// search for it
		/** @var QueryHit[] $hits */
		$hits = $index->find('foo');

		// get the document from the query hit
		$foundDoc = $hits[0]->getDocument();
		$this->assertSame('document.txt', basename($foundDoc->getFieldValue('path')));

	}

}
