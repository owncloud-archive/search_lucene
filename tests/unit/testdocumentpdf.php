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

use OCA\Search_Lucene\Document\Pdf;

class TestDocumentPdf extends \PHPUnit_Framework_TestCase {

	function testUpdate() {

		$data = file_get_contents(__DIR__ . '/data/document.pdf');

		$doc = Pdf::loadPdf($data, true);

		$this->assertGreaterThan(0,count($doc->getFieldValue('body')));
	}
}
