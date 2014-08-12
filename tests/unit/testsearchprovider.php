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

use OCA\Search_Lucene\Tests\Unit\Util\DummyIndex;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Search\QueryHit;

class TestSearchProvider extends TestCase {

	/**
	 * @dataProvider searchResultDataProvider
	 */
	function testSearchLuceneResultContent($fileId, $name, $path, $size, $score, $mimeType, $modified, $container) {

		require_once __DIR__ . '/util/dummyindex.php';

		$index = new DummyIndex();

		$doc = new Document();
		$doc->addField(Document\Field::Keyword('fileId', $fileId));
		$doc->addField(Document\Field::Text('path', '/test/files'.$path, 'UTF-8'));
		$doc->addField(Document\Field::unIndexed('mtime', $modified));
		$doc->addField(Document\Field::unIndexed('size', $size));
		$doc->addField(Document\Field::unIndexed('mimetype', $mimeType));
		$index->addDocument($doc);

		$hit = new QueryHit($index);
		$hit->score = $score;
		$hit->id = 0;
		$hit->document_id = 0;

		$searchResult = new \OCA\Search_Lucene\Search\LuceneResult($hit);

		$this->assertInstanceOf('OCA\Search_Lucene\Search\LuceneResult', $searchResult);
		$this->assertEquals($fileId, $searchResult->id);
		$this->assertEquals('lucene', $searchResult->type);
		$this->assertEquals($path, $searchResult->path);
		$this->assertEquals($name, $searchResult->name);
		$this->assertEquals($mimeType, $searchResult->mime_type);
		$this->assertEquals($size, $searchResult->size);
		$this->assertEquals($score, $searchResult->score);
		$this->assertEquals($modified, $searchResult->modified);
	}

	public function searchResultDataProvider() {

		return array(
			// hit, name, size, score, mime_type, container
			array('10', 'document.txt', '/documents/document.txt', 123, 0.4, 'text/plain', 1234567, 'documents'),
			array('20', 'document.pdf', '/documents/document.pdf', 1234, 0.31, 'application/pdf', 1234567, 'documents'),
			array('30', 'document.mp3', '/documents/document.mp3', 12341234, 0.299, 'audio/mp3', 1234567, 'documents'),
			array('40', 'document.jpg', '/documents/document.jpg', 1234123, 0.001, 'image/jpg', 1234567, 'documents'),
		);
	}
}
