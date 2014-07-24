<?php

namespace OCA\Search_Lucene\Tests\Unit\Util;

use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Index\Term;
use ZendSearch\Lucene\SearchIndexInterface;
use ZendSearch\Lucene\Storage\Directory\DirectoryInterface;

class DummyIndex implements SearchIndexInterface {
	var $documents = array();

	public function addDocument(Document $document) {
		$this->documents[] = $document;
	}

	public function getDocument($id) {
		return $this->documents[$id];
	}

	public function addReference() {
	}

	public function closeTermsStream() {
	}

	public function commit() {
	}

	public function count() {
	}

	public function currentTerm() {
	}

	public function delete($id) {
	}

	public function docFreq(Term $term) {
	}

	public function find($query) {
	}

	public function getDirectory() {
	}

	public function getFieldNames($indexed = false) {
	}

	public function getFormatVersion() {
	}

	public function getMaxBufferedDocs() {
	}

	public function getMaxMergeDocs() {
	}

	public function getMergeFactor() {
	}

	public function getSimilarity() {
	}

	public function hasDeletions() {
	}

	public function hasTerm(Term $term) {
	}

	public function isDeleted($id) {
	}

	public function maxDoc() {
	}

	public function nextTerm() {
	}

	public function norm($id, $fieldName) {
	}

	public function numDocs() {
	}

	public function optimize() {
	}

	public function removeReference() {
	}

	public function resetTermsStream() {
	}

	public function setFormatVersion($formatVersion) {
	}

	public function setMaxBufferedDocs($maxBufferedDocs) {
	}

	public function setMaxMergeDocs($maxMergeDocs) {
	}

	public function setMergeFactor($mergeFactor) {
	}

	public function skipTo(Term $prefix) {
	}

	public function termDocs(Term $term, $docsFilter = null) {
	}

	public function termDocsFilter(Term $term, $docsFilter = null) {
	}

	public function termFreqs(Term $term, $docsFilter = null) {
	}

	public function termPositions(Term $term, $docsFilter = null) {
	}

	public function terms() {
	}

	public function undeleteAll() {
	}

	public static function getActualGeneration(DirectoryInterface $directory) {
	}

	public static function getDefaultSearchField() {
	}

	public static function getResultSetLimit() {
	}

	public static function getSegmentFileName($generation) {
	}

	public static function setDefaultSearchField($fieldName) {
	}

	public static function setResultSetLimit($limit) {
	}
}