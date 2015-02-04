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

use ZendSearch\Lucene\Document;

/**
 * OpenDocument document.
 */
abstract class OpenDocument extends Document
{
	const OASIS_XPATH_TITLE    = '//dc:title';
	const OASIS_XPATH_SUBJECT  = '//dc:subject';
	const OASIS_XPATH_CREATOR  = '//meta:initial-creator';
	const OASIS_XPATH_KEYWORDS = '//meta:keyword';
	const OASIS_XPATH_CREATED  = '//meta:creation-date';
	const OASIS_XPATH_MODIFIED = '//dc:date';

	/**
	 * Extract metadata from document
	 *
	 * @param \ZipArchive $package ZipArchive OpenDocument package
	 * @return array Key-value pairs containing document meta data
	 */
	protected function extractMetaData(\ZipArchive $package)
	{
		// Data holders
		$coreProperties = array();

		// Prevent php from loading remote resources
		$loadEntities = libxml_disable_entity_loader(true);

		// Read relations and search for core properties
		$sxe = simplexml_load_string($package->getFromName("meta.xml"));

		// Restore entity loader state
		libxml_disable_entity_loader($loadEntities);

		if (is_object($sxe) && $sxe instanceof \SimpleXMLElement) {

			$coreProperties['title'] = $this->extractTermsFromMetadata($sxe, $this::OASIS_XPATH_TITLE);

			$coreProperties['subject'] = $this->extractTermsFromMetadata($sxe, $this::OASIS_XPATH_SUBJECT);

			$coreProperties['creator'] = $this->extractTermsFromMetadata($sxe, $this::OASIS_XPATH_CREATOR);

			$coreProperties['keywords'] = $this->extractTermsFromMetadata($sxe, $this::OASIS_XPATH_KEYWORDS);

			//replace T in date string with ' '
			$coreProperties['created'] = str_replace('T', ' ', $this->extractTermsFromMetadata($sxe, $this::OASIS_XPATH_CREATED));

			$coreProperties['modified'] = str_replace('T', ' ', $this->extractTermsFromMetadata($sxe, $this::OASIS_XPATH_MODIFIED));
		}

		return $coreProperties;
	}

	private function extractTermsFromMetadata(\SimpleXMLElement $sxe, $path) {

		$terms = array();

		foreach ($sxe->xpath($path) as $value) {
			$terms[] = (string)$value;
		}

		return (implode(' ', $terms));

	}

}
