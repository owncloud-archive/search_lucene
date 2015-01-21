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

namespace OCA\Search_Lucene\Lucene;

use OCP\Files\File;
use OCA\Search_Lucene\Core\Files;
use OCA\Search_Lucene\Db\StatusMapper;
use OCA\Search_Lucene\Document\Ods;
use OCA\Search_Lucene\Document\Odt;
use OCA\Search_Lucene\Document\Pdf;
use OCP\ILogger;
use OCP\IServerContainer;
use ZendSearch\Lucene\Document\HTML;
use ZendSearch\Lucene\Document;

class Indexer {
/*
	// TODO use dublin core for fieldnames? would require touching zend documents or provide a metadata mapping
	const FIELD_TYPE_FILE_ID = 'fileId';
	const FIELD_TYPE_KEYWORDS = 'keywords';
	//the rest is derived from dublin core
	const FIELD_TYPE_CREATOR = 'creator'; // alternative is author
	const FIELD_TYPE_SUBJECT = 'subject';
	const FIELD_TYPE_TITLE = 'title';
	const FIELD_TYPE_DESCRIPTION = 'description';
	const FIELD_TYPE_CREATED = 'created';
	const FIELD_TYPE_MODIFIED = 'modified';
	const FIELD_TYPE_PAGES = 'pages';
	const FIELD_TYPE_PUBLISHER = 'publisher';
	const FIELD_TYPE_ACCESS_RIGHTS = 'accessRights'; // user searchable? multivalue?
	const FIELD_TYPE_FILE_FORMAT = 'FileFormat'; // alternative is mimetype
	const FIELD_TYPE_LOCATION = 'location'; // alternative is path
	const FIELD_TYPE_SIZE = 'size'; // Size Or Duration
	//TODO when user uses author:Jörn, author should be replaced by creator
*/
	/**
	 * @var Files
	 */
	private $files;
	/**
	 * @var IServerContainer
	 */
	private $server;
	/**
	 * @var Index
	 */
	private $index;
	/**
	 * @var array
	 */
	private $skippedDirs;
	/**
	 * @var StatusMapper
	 */
	private $mapper;
	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * @param Files $files
	 * @param IServerContainer $server
	 * @param Index $index
	 * @param array $skippedDirs
	 * @param StatusMapper $mapper
	 * @param ILogger $logger
	 */
	public function __construct(Files $files, IServerContainer $server, Index $index, array $skippedDirs, StatusMapper $mapper, ILogger $logger) {
		$this->files = $files;
		$this->server = $server;
		$this->index = $index;
		$this->skippedDirs = $skippedDirs;
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	/**
	 * @param array $fileIds
	 * @param \OC_EventSource $eventSource
	 */
	public function indexFiles (array $fileIds, \OC_EventSource $eventSource = null) {

		foreach ($fileIds as $id) {

			$fileStatus = $this->mapper->getOrCreateFromFileId($id);

			try {
				// before we start mark the file as error so we know there
				// was a problem in case the php execution dies and we don't try
				// the file again
				$this->mapper->markError($fileStatus);

				$nodes = $this->server->getUserFolder()->getById($id);
				// getById can return more than one id because the containing storage might be mounted more than once
				// Since we only want to index the file once, we only use the first entry

				if (isset($nodes[0])) {
					/** @var File $node */
					$node = $nodes[0];
				} else {
					throw new VanishedException($id);
				}

				if ( ! $node instanceof File ) {
					throw new NotIndexedException();
				}

				$path = $node->getPath();

				foreach ($this->skippedDirs as $skippedDir) {
					if (strpos($path, '/' . $skippedDir . '/') !== false //contains dir
						|| strrpos($path, '/' . $skippedDir) === strlen($path) - (strlen($skippedDir) + 1) // ends with dir
					) {
						throw new SkippedException('skipping file '.$id.':'.$path);
					}
				}

				if ($eventSource) {
					$eventSource->send('indexing', $path);
				}

				if ($this->indexFile($node, false)) {
					$this->mapper->markIndexed($fileStatus);
				}

			} catch (VanishedException $e) {

				$this->mapper->markVanished($fileStatus);

			} catch (NotIndexedException $e) {

				$this->mapper->markUnIndexed($fileStatus);

			} catch (SkippedException $e) {

				$this->mapper->markSkipped($fileStatus);
				$this->logger->debug( $e->getMessage() );

			} catch (\Exception $e) {
				//sqlite might report database locked errors when stock filescan is in progress
				//this also catches db locked exception that might come up when using sqlite
				$this->logger->error($e->getMessage() . ' Trace:\n' . $e->getTraceAsString() );
				$this->mapper->markError($fileStatus);
				// TODO Add UI to trigger rescan of files with status 'E'rror?
				if ($eventSource) {
					$eventSource->send('error', $e->getMessage());
				}
			}
		}

		$this->index->commit();
	}

	/**
	 * index a file
	 *
	 * @param File $file the file to be indexed
	 * @param bool $commit
	 *
	 * @return bool true when something was stored in the index, false otherwise (eg, folders are not indexed)
	 * @throws NotIndexedException when an unsupported file type is encountered
	 */
	public function indexFile(File $file, $commit = true) {

			// we decide how to index on mime type or file extension
			$mimeType = $file->getMimeType();
			$fileExtension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));

			// initialize plain lucene document
			$doc = new Document();

			// index content for local files only
			$storage = $file->getStorage();

			if ($storage->isLocal()) {

				$path = $storage->getLocalFile($file->getInternalPath());

				//try to use special lucene document types

				if ('text/html' === $mimeType) {

					//TODO could be indexed, even if not local
					$doc = HTML::loadHTML($file->getContent());

				} else if ('text/' === substr($mimeType, 0, 5)
					|| 'application/x-tex' === $mimeType) {

					$body = $file->getContent();

					if ($body != '') {
						$doc->addField(Document\Field::UnStored('body', $body));
					}

				} else if ('application/pdf' === $mimeType) {

					$doc = Pdf::loadPdf($file->getContent());

				// the zend classes only understand docx and not doc files
				} else if ($fileExtension === 'docx') {

					$doc = Document\Docx::loadDocxFile($path);

				//} else if ('application/msexcel' === $mimeType) {
				} else if ($fileExtension === 'xlsx') {

					$doc = Document\Xlsx::loadXlsxFile($path);

				//} else if ('application/mspowerpoint' === $mimeType) {
				} else if ($fileExtension === 'pptx') {

					$doc = Document\Pptx::loadPptxFile($path);

				} else if ($fileExtension === 'odt') {

					$doc = Odt::loadOdtFile($path);

				} else if ($fileExtension === 'ods') {

					$doc = Ods::loadOdsFile($path);

				} else {
					throw new NotIndexedException();
				}
			}

			// Store filecache id as unique id to lookup by when deleting
			$doc->addField(Document\Field::Keyword('fileId', $file->getId()));

			// Store document path for the search results
			$doc->addField(Document\Field::Text('path', $file->getPath(), 'UTF-8'));

			$doc->addField(Document\Field::unIndexed('mtime', $file->getMTime()));

			$doc->addField(Document\Field::unIndexed('size', $file->getSize()));

			$doc->addField(Document\Field::unIndexed('mimetype', $mimeType));

			$this->index->updateFile($doc, $file->getId(), $commit);

			return true;

	}

}
