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

namespace OCA\Search_Lucene\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method integer getFileId()
 * @method void setFileId(integer $fileId)
 * @method string getStatus()
 * @method setStatus(string $status)
 */
class Status extends Entity {

	const STATUS_NEW = 'N';
	const STATUS_INDEXED = 'I';
	const STATUS_SKIPPED = 'S';
	const STATUS_UNINDEXED = 'U';
	const STATUS_VANISHED = 'V';
	const STATUS_ERROR = 'E';

	public $fileId;
	public $status;

	// we use fileId as the primary key
	private $_fieldTypes = array('fileId' => 'integer');


	/**
	 * @param string $fileId
	 * @param string $status
	 */
	public function __construct($fileId = null, $status = null) {
		// use setters to mark properties as updated
		$this->setFileId($fileId);
		$this->setStatus($status);
	}
	/**
	 * @return array with attribute and type
	 */
	public function getFieldTypes() {
		return $this->_fieldTypes;
	}

	/**
	 * Adds type information for a field so that its automatically casted to
	 * that value once its being returned from the database
	 * @param string $fieldName the name of the attribute
	 * @param string $type the type which will be used to call setType()
	 */
	protected function addType($fieldName, $type){
		$this->_fieldTypes[$fieldName] = $type;
	}

	// we need to overwrite the setter because it would otherwise use _fieldTypes of the Entity class
	protected function setter($name, $args) {
		// setters should only work for existing attributes
		if(property_exists($this, $name)){
			if($this->$name === $args[0]) {
				return;
			}
			$this->markFieldUpdated($name);

			// if type definition exists, cast to correct type
			if($args[0] !== null && array_key_exists($name, $this->_fieldTypes)) {
				settype($args[0], $this->_fieldTypes[$name]);
			}
			$this->$name = $args[0];

		} else {
			throw new \BadFunctionCallException($name .
				' is not a valid attribute');
		}
	}

	/**
	 * Transform a database column name to a property
	 * @param string $columnName the name of the column
	 * @return string the property name
	 */
	public function columnToProperty($columnName) {
		if ($columnName === 'fileid') {
			$property = 'fileId';
		} else {
			$property = parent::columnToProperty($columnName);
		}
		return $property;
	}

	/**
	 * Transform a property to a database column name
	 * for search_lucene we don't magically insert a _ for CamelCase
	 * @param string $property the name of the property
	 * @return string the column name
	 */
	public function propertyToColumn($property){
		$column = strtolower($property);
		return $column;
	}
}
