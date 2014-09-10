<?php

/**
 * ownCloud search lucene
 *
 * @author Jörn Dreyer
 * @copyright 2014 Jörn Friedrich Dreyer jfd@butonic.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Search_Lucene\Tests\Unit;

use OCA\Search_Lucene\AppInfo\Application;
use OCA\Search_Lucene\Db\StatusMapper;
use OCA\Search_Lucene\Db\Status;

class TestStatus extends TestCase {

	/**
	 * @dataProvider statusDataProvider
	 */
	function testMarkingMethods($fileName, $method, $expectedStatus) {

		// preparation
		$fileId = $this->getFileId($fileName);
		$this->assertNotNull($fileId, 'Precondition failed: file id not found!');

		$app = new Application();
		$container = $app->getContainer();
		/** @var StatusMapper $mapper */
		$mapper = $container->query('StatusMapper');

		// run test
		$status = $mapper->getOrCreateFromFileId($fileId);
		$mapper->$method($status);

		$this->assertInstanceOf('OCA\Search_Lucene\Db\Status', $status);
		$this->assertEquals($fileId, $status->getFileId());
		$this->assertEquals($expectedStatus, $status->getStatus());

		//check after loading from db
		$status2 = $mapper->getOrCreateFromFileId($fileId);

		$this->assertInstanceOf('OCA\Search_Lucene\Db\Status', $status2);
		$this->assertEquals($fileId, $status2->getFileId());
		$this->assertEquals($status->getFileId(), $status2->getFileId());
		$this->assertEquals($expectedStatus, $status2->getStatus());

	}

	public function statusDataProvider() {
		return array(
			array('/documents/document.pdf',	'markNew',			Status::STATUS_NEW),
			array('/documents/document.pdf',	'markSkipped',		Status::STATUS_SKIPPED),
			array('/documents/document.pdf',	'markIndexed',		Status::STATUS_INDEXED),
			array('/documents/document.pdf',	'markUnIndexed',	Status::STATUS_UNINDEXED),
			array('/documents/document.pdf',	'markError',		Status::STATUS_ERROR),
			array('/documents/document.pdf',	'markVanished',		Status::STATUS_VANISHED),

			array('/documents/document.docx',	'markNew',			Status::STATUS_NEW),
			array('/documents/document.docx',	'markSkipped',		Status::STATUS_SKIPPED),
			array('/documents/document.docx',	'markIndexed',		Status::STATUS_INDEXED),
			array('/documents/document.docx',	'markUnIndexed',	Status::STATUS_UNINDEXED),
			array('/documents/document.docx',	'markError',		Status::STATUS_ERROR),
			array('/documents/document.docx',	'markVanished',		Status::STATUS_VANISHED),

			array('/documents/document.odt',	'markNew',			Status::STATUS_NEW),
			array('/documents/document.odt',	'markSkipped',		Status::STATUS_SKIPPED),
			array('/documents/document.odt',	'markIndexed',		Status::STATUS_INDEXED),
			array('/documents/document.odt',	'markUnIndexed',	Status::STATUS_UNINDEXED),
			array('/documents/document.odt',	'markError',		Status::STATUS_ERROR),
			array('/documents/document.odt',	'markVanished',		Status::STATUS_VANISHED),

			array('/documents/document.txt',	'markNew',			Status::STATUS_NEW),
			array('/documents/document.txt',	'markSkipped',		Status::STATUS_SKIPPED),
			array('/documents/document.txt',	'markIndexed',		Status::STATUS_INDEXED),
			array('/documents/document.txt',	'markUnIndexed',	Status::STATUS_UNINDEXED),
			array('/documents/document.txt',	'markError',		Status::STATUS_ERROR),
			array('/documents/document.txt',	'markVanished',		Status::STATUS_VANISHED),
		);
	}
}
