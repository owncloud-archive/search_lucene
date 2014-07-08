<?php

namespace OCA\Search_Lucene;

use OCA\Search_Lucene\Document\Ods;
use OCA\Search_Lucene\Document\Odt;
use OCA\Search_Lucene\Document\Pdf;

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

			$fileStatus = Status::fromFileId($id);

			try {
				// before we start mark the file as error so we know there
				// was a problem in case the php execution dies and we don't try
				// the file again
				$fileStatus->markError();

				/** @var \OCP\Files\Node $folder */
				$nodes = \OC::$server->getUserFolder()->getById($id);
				// getById can return more than one id because the containing storage might be mounted more than once
				// Since we only want to index the file once, we only use the first entry

				if (isset($nodes[0])) {
					/** @var \OCP\Files\File $node */
					$node = $nodes[0];
				} else {
					throw new \Exception('no file found for fileid '.$id);
				}

				if ( ! $node instanceof \OCP\Files\File ) {
					throw new NotIndexedException();
				}

				$path = $node->getPath();

				foreach ($skippedDirs as $skippedDir) {
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
					$fileStatus->markIndexed();
				}

			} catch (NotIndexedException $e) {

				$fileStatus->markUnIndexed();

			} catch (SkippedException $e) {

				$fileStatus->markSkipped();
				\OCP\Util::writeLog('search_lucene', $e->getMessage(), \OCP\Util::DEBUG);

			} catch (\Exception $e) {
				//sqlite might report database locked errors when stock filescan is in progress
				//this also catches db locked exception that might come up when using sqlite
				\OCP\Util::writeLog('search_lucene',
					$e->getMessage() . ' Trace:\n' . $e->getTraceAsString(),
					\OCP\Util::ERROR);
				$fileStatus->markError();  // Add UI to trigger rescan of files with status 'E'rror?
				if ($eventSource) {
					$eventSource->send('error', $e->getMessage());
				}
			}
		}
		$this->lucene->commit();
	}

	/**
	 * index a file
	 *
	 * @author Jörn Dreyer <jfd@butonic.de>
	 *
	 * @param \OCP\Files\File $file the file to be indexed
	 * @param bool $commit
	 *
	 * @return bool true when something was stored in the index, false otherwise (eg, folders are not indexed)
	 * @throws \OCA\Search_Lucene\NotIndexedException when an unsupported file type is encvountered
	 */
	public function indexFile(\OCP\Files\File $file, $commit = true) {

		// we decide how to index on mime type or file extension
		$mimeType = $file->getMimeType();
		$fileExtension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));

		// initialize plain lucene document
		$doc = new \Zend_Search_Lucene_Document();

		// index content for local files only
		$storage = $file->getStorage();

		if ($storage->isLocal()) {

			$path = $storage->getLocalFile($file->getInternalPath());

			//try to use special lucene document types

			if ('text/html' === $mimeType) {

				//TODO could be indexed, even if not local
				$doc = \Zend_Search_Lucene_Document_Html::loadHTML($file->getContent());
			} else if ('text/' === substr($mimeType, 0, 5)) {

				$body = $file->getContent();

				if ($body != '') {
					$doc->addField(\Zend_Search_Lucene_Field::UnStored('body', $body));
				}

			} else if ('application/pdf' === $mimeType) {

				$doc = Pdf::loadPdf($file->getContent());

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
		$doc->addField(\Zend_Search_Lucene_Field::Keyword('fileid', $file->getId()));

		// Store document path for the search results
		$doc->addField(\Zend_Search_Lucene_Field::Text('path', $file->getPath(), 'UTF-8'));

		$doc->addField(\Zend_Search_Lucene_Field::unIndexed('mtime', $file->getMTime()));

		$doc->addField(\Zend_Search_Lucene_Field::unIndexed('size', $file->getSize()));

		$doc->addField(\Zend_Search_Lucene_Field::unIndexed('mimetype', $mimeType));

		$this->lucene->updateFile($doc, $file->getId(), $commit);

		return true;

	}

}
