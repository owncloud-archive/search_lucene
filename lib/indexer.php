<?php

namespace OCA\Search_Lucene;

use OCA\Search_Lucene\Document\Ods;
use OCA\Search_Lucene\Document\Odt;
use OCA\Search_Lucene\Document\Pdf;
use OCP\Util;

/**
 * @author Jörn Dreyer <jfd@butonic.de>
 */
class Indexer {

	/**
	 * classname which used for hooks handling
	 * used as signalclass in OC_Hooks::emit()
	 */
	const CLASSNAME = 'Indexer';

	/**
	 * @var \OCP\Files\Folder
	 */
	private $folder;
	private $lucene;

	public function __construct(\OCP\Files\Folder $folder, Lucene $lucene) {
		$this->folder = $folder;
		$this->lucene = $lucene;
	}

	public function indexFiles (array $fileIds, \OC_EventSource $eventSource = null) {

		$skippedDirs = explode(
			';',
			\OCP\Config::getUserValue(\OCP\User::getUser(), 'search_lucene', 'skipped_dirs', '.git;.svn;.CVS;.bzr')
		);

		foreach ($fileIds as $id) {
			$skip = false;

			$fileStatus = \OCA\Search_Lucene\Status::fromFileId($id);

			try{
				// before we start mark the file as error so we know there
				// was a problem in case the php execution dies and we don't try
				// the file again
				$fileStatus->markError();

				// FIXME use Folder
				/** @var \OCP\Files\Node $folder */
				$folder = \OC::$server->getUserFolder()->getById($id);
				// TODO why does getById return an array?!?!
				if (empty($folder)) {
					$path = null;
				} else {
					$folder = $folder[0];
					//$path = \OC\Files\Filesystem::getPath($id);
					$path = $folder->getPath();
				}

				if (empty($path)) {
					$skip = true;
				} else {
					foreach ($skippedDirs as $skippedDir) {
						if (strpos($path, '/' . $skippedDir . '/') !== false //contains dir
							|| strrpos($path, '/' . $skippedDir) === strlen($path) - (strlen($skippedDir) + 1) // ends with dir
						) {
							$skip = true;
							break;
						}
					}
				}

				if ($skip) {
					$fileStatus->markSkipped();
					\OCP\Util::writeLog('search_lucene',
						'skipping file '.$id.':'.$path,
						\OCP\Util::DEBUG);
					continue;
				}
				if ($eventSource) {
					$eventSource->send('indexing', $path);
				}

				if ($this->indexFile($path)) {
					$fileStatus->markIndexed();
				} else {
					\OCP\JSON::error(array('message' => 'Could not index file '.$id.':'.$path));
					if ($eventSource) {
						$eventSource->send('error', $path);
					}
				}
			} catch (\Exception $e) { //sqlite might report database locked errors when stock filescan is in progress
				//this also catches db locked exception that might come up when using sqlite
				\OCP\Util::writeLog('search_lucene',
					$e->getMessage() . ' Trace:\n' . $e->getTraceAsString(),
					\OCP\Util::ERROR);
				\OCP\JSON::error(array('message' => 'Could not index file.'));
					if ($eventSource) {
						$eventSource->send('error', $e->getMessage());
					}
				//try to mark the file as new to let it reindex
				$fileStatus->markNew();  // Add UI to trigger rescan of files with status 'E'rror?
			}
		}
	}

	/**
	 * index a file
	 *
	 * @author Jörn Dreyer <jfd@butonic.de>
	 *
	 * @param string $path the path of the file
	 *
	 * @return bool true when something was stored in the index, false otherwise (eg, folders are not indexed)
	 * @throws \Exception indicating an error
	 */
	public function indexFile($path = '') {

		try {

			// the cache already knows mime and other basic stuff
			/** @var \OCP\Files\Node $data */
			$node = $this->folder->get($path);

			if ($node instanceof \OCP\Files\File) {

				// we decide how to index on mime type or file extension
				$mimeType = $node->getMimetype();
				$fileExtension = strtolower(pathinfo($node->getName(), PATHINFO_EXTENSION));

				// initialize plain lucene document
				$doc = new \Zend_Search_Lucene_Document();

				// index content for local files only
				$storage = $node->getStorage();

				$internalPath = $node->getInternalPath();
				$path = $node->getPath();

				if ($storage->isLocal()) {

					//try to use special lucene document types

					if ('text/plain' === $mimeType) {

						$body = $node->getContent();

						if ($body != '') {
							$doc->addField(\Zend_Search_Lucene_Field::UnStored('body', $body));
						}

					// FIXME other text files? c, php, java ...

					} else if ('text/html' === $mimeType) {

						//TODO could be indexed, even if not local
						$doc = \Zend_Search_Lucene_Document_Html::loadHTML($node->getContent());

					} else if ('application/pdf' === $mimeType) {

						$doc = Pdf::loadPdf($node->getContent());

					// the zend classes only understand docx and not doc files
					} else if ($fileExtension === 'docx') {

						$doc = \Zend_Search_Lucene_Document_Docx::loadDocxFile($path);

					//} else if ('application/msexcel' === $mimeType) {
					} else if ($fileExtension === 'xlsx') {

						$doc = \Zend_Search_Lucene_Document_Xlsx::loadXlsxFile($path);

					//} else if ('application/mspowerpoint' === $mimeType) {
					} else if ($fileExtension === 'pptx') {

						$doc = \Zend_Search_Lucene_Document_Pptx::loadPptxFile($path);

					} else if ($fileExtension === 'odt') {

						$doc = Odt::loadOdtFile($path);

					} else if ($fileExtension === 'ods') {

						$doc = Ods::loadOdsFile($path);

					}
				}

				// Store filecache id as unique id to lookup by when deleting
				$doc->addField(\Zend_Search_Lucene_Field::Keyword('fileid', $node->getId()));

				// Store document path for the search results
				$doc->addField(\Zend_Search_Lucene_Field::Text('path', $path, 'UTF-8'));

				$doc->addField(\Zend_Search_Lucene_Field::unIndexed('mtime', $node->getMTime()));

				$doc->addField(\Zend_Search_Lucene_Field::unIndexed('size', $node->getSize()));

				$doc->addField(\Zend_Search_Lucene_Field::unIndexed('mimetype', $mimeType));

				$this->lucene->updateFile($doc, $data->getId());
			}

			return true;

		} catch (\Exception $ex) {
			Util::writeLog(
				'search_lucene',
				$ex->getCode().':'.$ex->getMessage(),
				Util::DEBUG
			);
			return false;
		}
	}

}
