<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Search
 */

namespace ZendSearch\Lucene\Document;

use ZendSearch\Lucene\Document;
use ZendXml\Security as XMLSecurity;

/**
 * OpenXML document.
 *
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Document
 */
abstract class AbstractOpenXML extends Document
{
    /**
     * Xml Schema - Relationships
     *
     * @var string
     */
    const SCHEMA_RELATIONSHIP = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /**
     * Xml Schema - Office document
     *
     * @var string
     */
    const SCHEMA_OFFICEDOCUMENT = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';

    /**
     * Xml Schema - Core properties
     *
     * @var string
     */
    const SCHEMA_OFFICE_COREPROPERTIES = 'http://schemas.openxmlformats.org/officedocument/2006/relationships/metadata/core-properties';

    /**
     * Xml Schema - Core properties
     *
     * @var string
     */
    const SCHEMA_COREPROPERTIES = 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties';

    /**
     * Xml Schema - Dublin Core
     *
     * @var string
     */
    const SCHEMA_DUBLINCORE = 'http://purl.org/dc/elements/1.1/';

    /**
     * Xml Schema - Dublin Core Terms
     *
     * @var string
     */
    const SCHEMA_DUBLINCORETERMS = 'http://purl.org/dc/terms/';

    /**
     * Extract metadata from document
     *
     * @param \ZipArchive $package    ZipArchive AbstractOpenXML package
     * @return array    Key-value pairs containing document meta data
     */
    protected function extractMetaData(\ZipArchive $package)
    {
        // Data holders
        $coreProperties = array();

        // Read relations and search for core properties
        $relations = XMLSecurity::Scan($package->getFromName("_rels/.rels"));

        foreach ($relations->Relationship as $rel) {
            if ($rel["Type"] == self::SCHEMA_COREPROPERTIES
                || $rel["Type"] == self::SCHEMA_OFFICE_COREPROPERTIES
            ) {
                // Found core properties! Read in contents...
                $contents = XMLSecurity::Scan(
                    $package->getFromName(dirname($rel["Target"]) . "/" . basename($rel["Target"]))
                );

                foreach ($contents->children(self::SCHEMA_DUBLINCORE) as $child) {
                    $coreProperties[$child->getName()] = (string)$child;
                }
                foreach ($contents->children(self::SCHEMA_COREPROPERTIES) as $child) {
                    $coreProperties[$child->getName()] = (string)$child;
                }
                foreach ($contents->children(self::SCHEMA_DUBLINCORETERMS) as $child) {
                    $coreProperties[$child->getName()] = (string)$child;
                }
            }
        }

        return $coreProperties;
    }

    /**
     * Determine absolute zip path
     *
     * @param string $path
     * @return string
     */
    protected function absoluteZipPath($path)
    {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode('/', $absolutes);
    }
}
