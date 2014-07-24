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

namespace OCA\Search_Lucene\Document;

use Smalot\PdfParser\Parser;
use OCP\Util;
use ZendPdf\PdfDocument;
use ZendSearch\Lucene\Document;

/**
 * PDF document
 */
class Pdf extends Document
{

	/**
	 * Object constructor
	 *
	 * @param string  $data
	 * @param boolean $storeContent
	 */
	private function __construct($data, $storeContent) {

		try {
			//TODO check PDF >1.5 metadata extraction

			//do the content extraction
			$parser = new Parser();
			$pdf    = $parser->parseContent($data);

			$details = $pdf->getDetails();

			// Store meta data properties
			if (isset($details['Title'])) {
				$this->addField(Document\Field::UnStored('title', $details['Title']));
			}
			if (isset($details['Author'])) {
				$this->addField(Document\Field::UnStored('author', $details['Author']));
			}
			if (isset($details['Subject'])) {
				$this->addField(Document\Field::UnStored('subject', $details['Subject']));
			}
			if (isset($details['Keywords'])) {
				$this->addField(Document\Field::UnStored('keywords', $details['Keywords']));
			}

			$body = $pdf->getText();

			if ($body != '') {
				// Store contents
				if ($storeContent) {
					$this->addField(Document\Field::Text('body', $body, 'UTF-8'));
				} else {
					$this->addField(Document\Field::UnStored('body', $body, 'UTF-8'));
				}
			}

		} catch (\Exception $e) {
			Util::writeLog('search_lucene',
				$e->getMessage() . ' Trace:\n' . $e->getTraceAsString(),
				Util::ERROR);
		}

	}

	/**
	 * Load PDF document from a string
	 *
	 * @param string  $data
	 * @param boolean $storeContent
	 * @return Pdf
	 */
	public static function loadPdf($data, $storeContent = false)
	{
		return new Pdf($data, false, $storeContent);
	}

}

