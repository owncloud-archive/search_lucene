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
use OCA\Search_Lucene\Lucene\Index;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Index\Term;
use ZendSearch\Lucene\Search\Query;
use ZendSearch\Lucene\Search\QueryHit;

class TestIndex extends TestCase {

	function testUpdate() {

		// preparation
		$app = new Application();
		$container = $app->getContainer();

		// get an index
		/** @var Index $index */
		$index = $container->query('Index');

		// add a document
		$doc = new Document();

		$doc->addField(Document\Field::Keyword('fileId', '1'));
		$doc->addField(Document\Field::Text('path', '/somewhere/deep/down/the/rabbit/hole' , 'UTF-8'));
		$doc->addField(Document\Field::Text('users', 'alice' , 'UTF-8'));

		$index->index->addDocument($doc);
		$index->commit();

		// search for it
		$idTerm  = new Term('1', 'fileId');
		$idQuery = new Query\Term($idTerm);

		$query = new Query\Boolean();
		$query->addSubquery($idQuery);
		/** @var QueryHit $hit */
		$hits = $index->find($query);
		// get the document from the query hit
		$foundDoc = $hits[0]->getDocument();
		$this->assertEquals('alice', $foundDoc->getFieldValue('users'));

		// delete the document from the index
		//$index->index->delete($hit);

		// change the 'users' key of the document
		$foundDoc->addField(Document\Field::Text('users', 'bob' , 'UTF-8'));
		$this->assertEquals('bob', $foundDoc->getFieldValue('users'));

		// add the document back to the index
		$index->updateFile($foundDoc, '1');


		$idTerm2  = new Term('1', 'fileId');
		$idQuery2 = new Query\Term($idTerm2);

		$query2 = new Query\Boolean();
		$query2->addSubquery($idQuery2);
		/** @var QueryHit $hit */
		$hits2 = $index->find($query2);
		// get the document from the query hit
		$foundDoc2 = $hits2[0]->getDocument();
		$this->assertEquals('bob', $foundDoc2->getFieldValue('users'));

	}
}
