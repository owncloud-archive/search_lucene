<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


namespace OCA\Search_Lucene;

use \OCP\Util;

/**
 * @author Jörn Dreyer <jfd@butonic.de>
 */
class SearchProvider extends \OCP\Search\Provider {

	/**
	 * performs a search on the users index
	 * 
	 * @author Jörn Dreyer <jfd@butonic.de>
	 * 
	 * @param string $query lucene search query
	 * @return array of \OCP\Search\Result
	 */
	public function search($query){
		$results=array();
		if ( $query !== null ) {
			// * query * kills performance for bigger indexes
			// query * works ok
			// query is still best
			//FIXME emulates the old search but breaks all the nice lucene search query options
			//$query = '*' . $query . '*';
			//if (strpos($query, '*')===false) {
			//	$query = $query.='*'; // append query *, works ok
			//	TODO add end user guide for search terms ... 
			//}
			try {
				$lucene = new Lucene();
				//default is 3, 0 needed to keep current search behaviour
				//Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0); 
				
				//$term  = new Zend_Search_Lucene_Index_Term($query);
				//$query = new Zend_Search_Lucene_Search_Query_Term($term);
				
				$hits = $lucene->find($query);

				//limit results. we cant show more than ~30 anyway. TODO use paging later
				for ($i = 0; $i < 30 && $i < count($hits); $i++) {
					$results[] = new Result\Content($hits[$i]);
				}

			} catch ( \Exception $e ) {
				Util::writeLog(
					'search_lucene',
					$e->getMessage().' Trace:\n'.$e->getTraceAsString(),
					Util::ERROR
				);
			}

		}
		return $results;
	}

	/**
	 * get the base type of a mimetype string
	 * 
	 * returns 'text' for 'text/plain'
	 * 
	 * @author Jörn Dreyer <jfd@butonic.de>
	 * 
	 * @param string $mimetype mimetype
	 * @return string basetype 
	 */
	public static function baseTypeOf($mimetype) {
		return substr($mimetype, 0, strpos($mimetype, '/'));
	}

}