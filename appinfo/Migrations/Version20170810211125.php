<?php

namespace OCA\Search_Lucene\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/** Creates initial schema */
class Version20170810211125 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if (!$schema->hasTable("{$prefix}lucene_status")) {
			$table = $schema->createTable("{$prefix}lucene_status");
			$table->addColumn('fileid', 'bigint', [
				'unsigned' => true,
				'notnull' => true,
				'length' => 11,
			]);

			$table->addColumn('status', 'string', [
				'notnull' => false,
				'length' => 1,
				'default' => null,
			]);
			$table->setPrimaryKey(['fileid']);
			$table->addIndex(
				['status'],
				'status_index'
			);
		}
	}
}
