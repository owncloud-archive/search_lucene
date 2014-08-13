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

/*
 * when testing documents we want to test if
 * - the body is indexed
 * - the metadata is indexed
 *
 * the body of a document can contain text in various forms
 * - font style
 * - inside tables
 * - headlines
 * - left, right, justified, block alignments
 * - numbered, bullet lists
 *
 * how do we test all of these properties?
 * since we are testing for text inside a document we should start with noise. eg. lorem ipsum and insert unique
 * search terms in an intersting place:
 * - we can add the same serch term to all documents (in an interesting place). searching it should always return all indexed files
 * - we can add a second, gobally unique term that will only be present in a single file. searching the term should only return a single file
 *
 * how do we name these terms? if all is lorem ipsum we can use 'term1', 'term2', ... 'term...'
 */

namespace OCA\Search_Lucene\Tests\Unit;

use OCA\Search_Lucene\Document\Pdf;

class TestDocumentPdf extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider dataProviderA1
	 */
	function testParseA1Whole($term, $field, $descriptiveLocation, $skipped) {

		if ($skipped) {
			$this->markTestSkipped('TODO search ' .$descriptiveLocation. ' in ' . $field);
		}

		$data = file_get_contents(__DIR__.'/data/libreoffice/document whole - a1.pdf');

		$doc = Pdf::loadPdf($data, true);

		$value = $doc->getFieldValue($field);

		$containsTestTerm = is_string(stristr($value, $term));
		$this->assertTrue($containsTestTerm, $field.'/'.$descriptiveLocation.' does not contain "'.$term.'" in '.$value);

	}
	/**
	 * @dataProvider dataProviderA1
	 */
	function testParseA1Split($term, $field, $descriptiveLocation, $skipped) {

		$this->markTestSkipped('FIXME pdfparser introduces too many blanks between PDF objects');

		if ($skipped) {
			$this->markTestSkipped('TODO search ' .$descriptiveLocation. ' in ' . $field);
		}

		$data = file_get_contents(__DIR__.'/data/libreoffice/document split - a1.pdf');

		$doc = Pdf::loadPdf($data, true);

		$value = $doc->getFieldValue($field);

		$containsTestTerm = is_string(stristr($value, $term));
		$this->assertTrue($containsTestTerm, $field.'/'.$descriptiveLocation.' does not contain "'.$term.'" in '.$value);

	}

	public function dataProviderA1() {
		return array(
			array('term0', 'body', 'title', false),
			array('term1', 'body', 'subtitle', false),
			array('term2', 'body', 'text', false),
			array('term3', 'body', 'font1', false),
			array('term4', 'body', 'font2', false),
			array('term5', 'body', 'font3', false),
			array('term6', 'body', 'link', false),
			array('term7', 'body', 'link name', true),
			array('term8', 'body', 'strikethrough', false),
			array('term9', 'body', 'subscript', false),
			array('term10', 'body', 'superscript', false),
			array('term11', 'body', 'bulletlist', false),
			array('term12', 'body', 'enumeration', false),
			array('term13', 'body', 'text frame', false),
			array('term14', 'body', 'heading 1', false),
			array('term15', 'body', 'heading 2', false),
			array('term16', 'body', 'heading 3', false),
			array('term17', 'body', 'heading 4', false),
			array('term18', 'body', 'centered', false),
			array('term19', 'body', 'right', false),
			array('term20', 'body', 'justified', false),
			array('term21', 'body', 'bold', false),
			array('term22', 'body', 'italic', false),
			array('term23', 'body', 'underlined', false),
			array('term24', 'body', 'bold italic', false),
			array('term25', 'body', 'bold underlined', false),
			array('term26', 'body', 'italic underlined', false),
			array('term27', 'body', 'bold italic underlined', false),
			array('term28', 'body', 'footer', false),
			array('term29', 'body', 'header', false),
			array('term30', 'body', 'color', false),
			array('term31', 'body', 'table header', false),
			array('term32', 'body', 'table row', false),
			array('term33', 'body', '6pt', false),
			array('term34', 'body', '8pt', false),
			array('term35', 'body', 'footnote', false),
			array('term36', 'body', 'comment', true),
			array('term37', 'title', 'meta title', false),
			array('term38', 'subject', 'meta subject', false),
			array('term39', 'keywords', 'meta keywords', false),
			array('term40', 'comment', 'meta comment', true),
			array('term41', 'custom', 'meta custom property', true),
		);
	}

	/**
	 * @dataProvider dataProviderV14
	 */
	function testParseV14Whole($term, $field, $descriptiveLocation, $skipped) {

		if ($skipped) {
			$this->markTestSkipped('TODO search ' .$descriptiveLocation. ' in ' . $field);
		}

		$data = file_get_contents(__DIR__.'/data/libreoffice/document whole - 1.4.pdf');

		$doc = Pdf::loadPdf($data, true);

		$value = $doc->getFieldValue($field);

		$containsTestTerm = is_string(stristr($value, $term));
		$this->assertTrue($containsTestTerm, $field.'/'.$descriptiveLocation.' does not contain "'.$term.'" in '.$value);

	}

	/**
	 * @dataProvider dataProviderV14
	 */
	function testParseV14Split($term, $field, $descriptiveLocation, $skipped) {

		$this->markTestSkipped('FIXME pdfparser introduces too many blanks between PDF objects');

		if ($skipped) {
			$this->markTestSkipped('TODO search ' .$descriptiveLocation. ' in ' . $field);
		}

		$data = file_get_contents(__DIR__.'/data/libreoffice/document split - 1.4.pdf');

		$doc = Pdf::loadPdf($data, true);

		$value = $doc->getFieldValue($field);

		$containsTestTerm = is_string(stristr($value, $term));
		$this->assertTrue($containsTestTerm, $field.'/'.$descriptiveLocation.' does not contain "'.$term.'" in '.$value);

	}

	public function dataProviderV14() {
		return array(
			array('term0', 'body', 'title', false),
			array('term1', 'body', 'subtitle', false),
			array('term2', 'body', 'text', false),
			array('term3', 'body', 'font1', false),
			array('term4', 'body', 'font2', false),
			array('term5', 'body', 'font3', false),
			array('term6', 'body', 'link', false),
			array('term7', 'body', 'link name', true),
			array('term8', 'body', 'strikethrough', false),
			array('term9', 'body', 'subscript', false),
			array('term10', 'body', 'superscript', false),
			array('term11', 'body', 'bulletlist', false),
			array('term12', 'body', 'enumeration', false),
			array('term13', 'body', 'text frame', false),
			array('term14', 'body', 'heading 1', false),
			array('term15', 'body', 'heading 2', false),
			array('term16', 'body', 'heading 3', false),
			array('term17', 'body', 'heading 4', false),
			array('term18', 'body', 'centered', false),
			array('term19', 'body', 'right', false),
			array('term20', 'body', 'justified', false),
			array('term21', 'body', 'bold', false),
			array('term22', 'body', 'italic', false),
			array('term23', 'body', 'underlined', false),
			array('term24', 'body', 'bold italic', false),
			array('term25', 'body', 'bold underlined', false),
			array('term26', 'body', 'italic underlined', false),
			array('term27', 'body', 'bold italic underlined', false),
			array('term28', 'body', 'footer', false),
			array('term29', 'body', 'header', false),
			array('term30', 'body', 'color', false),
			array('term31', 'body', 'table header', false),
			array('term32', 'body', 'table row', false),
			array('term33', 'body', '6pt', false),
			array('term34', 'body', '8pt', false),
			array('term35', 'body', 'footnote', false),
			array('term36', 'body', 'comment', true),
			array('term37', 'title', 'meta title', false),
			array('term38', 'subject', 'meta subject', false),
			array('term39', 'keywords', 'meta keywords', false),
			array('term40', 'comment', 'meta comment', true),
			array('term41', 'custom', 'meta custom property', true),
		);
	}

	/**
	 * @dataProvider dataProviderV15
	 */
	function testParseV15($term, $field, $descriptiveLocation, $skipped) {

		$this->markTestSkipped('FIXME pdfparser does not correctly extract text from pdf v1.5');

		if ($skipped) {
			$this->markTestSkipped('TODO search ' .$descriptiveLocation. ' in ' . $field);
		}

		$data = file_get_contents(__DIR__.'/data/cairo/document.pdf');

		$doc = Pdf::loadPdf($data, true);

		$value = $doc->getFieldValue($field);

		$containsTestTerm = is_string(stristr($value, $term));
		$this->assertTrue($containsTestTerm, $field.'/'.$descriptiveLocation.' does not contain "'.$term.'" in '.$value);

	}

	public function dataProviderV15() {
		return array(
			array('term0', 'body', 'title', false),
			array('term1', 'body', 'subtitle', false),
			array('term2', 'body', 'text', false),
			array('term3', 'body', 'font1', false),
			array('term4', 'body', 'font2', false),
			array('term5', 'body', 'font3', false),
			array('term6', 'body', 'link', false),
			array('term7', 'body', 'link name', false),
			array('term8', 'body', 'strikethrough', false),
			array('term9', 'body', 'subscript', false),
			array('term10', 'body', 'superscript', false),
			array('term11', 'body', 'bulletlist', false),
			array('term12', 'body', 'enumeration', false),
			array('term13', 'body', 'text frame', false),
			array('term14', 'body', 'heading 1', false),
			array('term15', 'body', 'heading 2', false),
			array('term16', 'body', 'heading 3', false),
			array('term17', 'body', 'heading 4', false),
			array('term18', 'body', 'centered', false),
			array('term19', 'body', 'right', false),
			array('term20', 'body', 'justified', false),
			array('term21', 'body', 'bold', false),
			array('term22', 'body', 'italic', false),
			array('term23', 'body', 'underlined', false),
			array('term24', 'body', 'bold italic', false),
			array('term25', 'body', 'bold underlined', false),
			array('term26', 'body', 'italic underlined', false),
			array('term27', 'body', 'bold italic underlined', false),
			array('term28', 'body', 'footer', false),
			array('term29', 'body', 'header', false),
			array('term30', 'body', 'color', false),
			array('term31', 'body', 'table header', false),
			array('term32', 'body', 'table row', false),
			array('term33', 'body', '6pt', false),
			array('term34', 'body', '8pt', false),
			array('term35', 'body', 'footnote', false),
			array('term36', 'body', 'comment', false),
			array('term37', 'title', 'meta title', false),
			array('term38', 'subject', 'meta subject', false),
			array('term39', 'keywords', 'meta keywords', false),
			array('term40', 'comment', 'meta comment', false),
			array('term41', 'custom', 'meta custom property', false),
		);
	}
}
