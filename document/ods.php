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
 * Ods document.
 * @see http://en.wikipedia.org/wiki/OpenDocument_technical_specification
 */
class Ods extends OpenDocument {

	const SCHEMA_ODTABLE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';

	/**
	 * Object constructor
	 *
	 * @param string  $fileName
	 * @param boolean $storeContent
	 * @throws ExtensionNotLoadedException
	 * @throws RuntimeException
	 */
	private function __construct($fileName, $storeContent) {
		if (!class_exists('ZipArchive', false)) {
			throw new ExtensionNotLoadedException('Open Document Spreadsheet processing functionality requires Zip extension to be loaded');
		}

		// Document data holders
		$documentTables = array();
		$documentCells = array();

		// Open OpenXML package
		$package = new \ZipArchive();
		$package->open($fileName);

		// Read relations and search for officeDocument
		$content = $package->getFromName('content.xml');
		if ($content === false) {
			throw new RuntimeException('Invalid archive or corrupted .ods file.');
		}

		// Prevent php from loading remote resources
		$loadEntities = libxml_disable_entity_loader(true);

		$sxe = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOBLANKS | LIBXML_COMPACT);

		// Restore entity loader state
		libxml_disable_entity_loader($loadEntities);

		foreach ($sxe->xpath('//table:table[@table:name]') as $table) {
			$documentTables[] = (string)$table->attributes($this::SCHEMA_ODTABLE)->name;
		}
		foreach ($sxe->xpath('//text:p') as $cell) {
			$documentCells[] = (string)$cell;
		}

		// Read core properties
		$coreProperties = $this->extractMetaData($package);

		// Close file
		$package->close();

		// Store contents
		if ($storeContent) {
			$this->addField(Field::Text('sheets', implode(' ', $documentTables), 'UTF-8'));
			$this->addField(Field::Text('body', implode(' ', $documentCells), 'UTF-8'));
		} else {
			$this->addField(Field::UnStored('sheets', implode(' ', $documentTables), 'UTF-8'));
			$this->addField(Field::UnStored('body', implode(' ', $documentCells), 'UTF-8'));
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
	 * Load Ods document from a file
	 *
	 * @param string  $fileName
	 * @param boolean $storeContent
	 * @return Ods
	 * @throws RuntimeException
	 */
	public static function loadOdsFile($fileName, $storeContent = false) {
		if (!is_readable($fileName)) {
			throw new RuntimeException('Provided file \'' . $fileName . '\' is not readable.');
		}

		return new Ods($fileName, $storeContent);
	}
}
