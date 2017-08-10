<?php

namespace OCA\Search_Lucene\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

/** Updates some fields to bigint if required */
class Version20170810212112 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];

		if ($schema->hasTable("${prefix}lucene_status")) {
			$table = $schema->getTable("{$prefix}lucene_status");

			$fileIdColumn = $table->getColumn('fileid');
			if ($fileIdColumn && $fileIdColumn->getType()->getName() !== Type::BIGINT) {
				$fileIdColumn->setType(Type::getType(Type::BIGINT));
				$fileIdColumn->setOptions(['length' => 20]);
			}
		}
	}
}
