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

use ZendSearch\Lucene\Document\Field;
use ZendSearch\Lucene\Exception\ExtensionNotLoadedException;
use ZendSearch\Lucene\Exception\RuntimeException;

/**
 * Odt document.
 * @see http://en.wikipedia.org/wiki/OpenDocument_technical_specification
 */
class Odt extends OpenDocument {
	/**
	 * Object constructor
	 *
	 * @param string $fileName
	 * @param boolean $storeContent
	 * @throws ExtensionNotLoadedException
	 * @throws RuntimeException
	 */
	private function __construct($fileName, $storeContent) {
		if (!class_exists('ZipArchive', false)) {
			throw new ExtensionNotLoadedException('Open Document Text processing functionality requires Zip extension to be loaded');
		}

		// Document data holders
		$documentHeadlines = array();
		$documentParagraphs = array();

		// Open OpenXML package
		$package = new \ZipArchive();
		$package->open($fileName);

		// Read relations and search for officeDocument
		$content = $package->getFromName('content.xml');
		if ($content === false) {
			throw new RuntimeException('Invalid archive or corrupted .odt file.');
		}

		// Prevent php from loading remote resources
		$loadEntities = libxml_disable_entity_loader(true);

		$sxe = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOBLANKS | LIBXML_COMPACT);

		// Restore entity loader state
		libxml_disable_entity_loader($loadEntities);

		foreach ($sxe->xpath('//text:h') as $headline) {
			$h = strip_tags($headline->asXML());
			$documentHeadlines[] = $h;
		}

		foreach ($sxe->xpath('//text:p') as $paragraph) {
			$p = strip_tags($paragraph->asXML());
			$documentParagraphs[] = $p;
		}

		// Read core properties
		$coreProperties = $this->extractMetaData($package);

		// Close file
		$package->close();

		// Store contents
		if ($storeContent) {
			$this->addField(Field::Text('headlines', implode(' ', $documentHeadlines), 'UTF-8'));
			$this->addField(Field::Text('body', implode('', $documentParagraphs), 'UTF-8'));
		} else {
			$this->addField(Field::UnStored('headlines', implode(' ', $documentHeadlines), 'UTF-8'));
			$this->addField(Field::UnStored('body', implode('', $documentParagraphs), 'UTF-8'));
		}

		// Store meta data properties
		foreach ($coreProperties as $key => $value) {
			$this->addField(Field::Text($key, $value, 'UTF-8'));
		}

		// Store title (if not present in meta data)
		if (! isset($coreProperties['title'])) {
			$this->addField(Field::Text('title', $fileName, 'UTF-8'));
		}
	}

	/**
	 * Load Odt document from a file
	 *
	 * @param string  $fileName
	 * @param boolean $storeContent
	 * @return Odt
	 * @throws RuntimeException
	 */
	public static function loadOdtFile($fileName, $storeContent = false) {
		if (!is_readable($fileName)) {
			throw new RuntimeException('Provided file \'' . $fileName . '\' is not readable.');
		}

		return new Odt($fileName, $storeContent);
	}
}
