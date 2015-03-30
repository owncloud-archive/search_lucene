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

namespace OCA\Search_Lucene\Document;

use OCA\Search_Lucene\Lucene\NotIndexedException;
use Smalot\PdfParser\Parser;
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
	 * @throws NotIndexedException
	 */
	private function __construct($data, $storeContent) {

		//TODO check PDF >1.5 metadata extraction

		//do the content extraction
		$parser = new Parser();

		try {
			$pdf = $parser->parseContent($data);

			$body = $pdf->getText();

			// Store contents
			if ($storeContent) {
				$this->addField(Document\Field::Text('body', $body, 'UTF-8'));
			} else {
				$this->addField(Document\Field::UnStored('body', $body, 'UTF-8'));
			}

			$details = $pdf->getDetails();

			// Store meta data properties
			foreach ($details as $key => $value) {
				$key = strtolower($key);
				if ($key === 'author') {
					$key = 'creator';
				}
				$this->addField(Document\Field::Text($key, $value, 'UTF-8'));
			}
		} catch (\Exception $ex) {
			throw new NotIndexedException (null, null, $ex);
		}

	}

	/**
	 * Load PDF document from a string
	 *
	 * @param string  $data
	 * @param boolean $storeContent
	 * @return Pdf
	 * @throws \Exception
	 */
	public static function loadPdf($data, $storeContent = false)
	{
		return new Pdf($data, $storeContent);
	}

}

