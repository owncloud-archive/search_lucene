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

namespace OCA\Search_Lucene\Search;

use OCA\Search_Lucene\AppInfo\Application;
use OCP\Search\Provider;

class LuceneProvider extends Provider {

	/**
	 * performs a search on the users index
	 * 
	 * @param string $query lucene search query
	 * @return \OCA\Search_Lucene\Search\LuceneResult[]
	 */
	public function search($query){

		$app = new Application();
		$container = $app->getContainer();

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
				$index = $container->query('Index');
				//default is 3, 0 needed to keep current search behaviour
				//Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0); 
				
				//$term  = new Zend_Search_Lucene_Index_Term($query);
				//$query = new Zend_Search_Lucene_Search_Query_Term($term);
				
				$hits = $index->find($query);

				//limit results. we cant show more than ~30 anyway. TODO use paging later
				for ($i = 0; $i < 30 && $i < count($hits); $i++) {
					$results[] = new LuceneResult($hits[$i]);
				}

			} catch ( \Exception $e ) {
				$container->query('Logger')->error( $e->getMessage().' Trace:\n'.$e->getTraceAsString() );
			}

		}
		return $results;
	}

	/**
	 * get the base type of an internet media type string
	 * 
	 * returns 'text' for 'text/plain'
	 * 
	 * @param string $mimeType internet media type
	 * @return string top-level type
	 */
	public static function baseTypeOf($mimeType) {
		return substr($mimeType, 0, strpos($mimeType, '/'));
	}

}