<?php
/**
 * ownCloud - search_lucene
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Devin M. Ceartas <devin@nacredata.com>
 * @copyright Devin M. Ceartas 2016
 */

namespace OCA\Search_Lucene\Hooks;

use OCA\Search_Lucene\AppInfo\Application;
use OCA\Search_Lucene\Db\FilesInFolder;

class Share {

    private static function reIndex($fileIds) {
      $app = new Application();
      $container = $app->getContainer();
      self::logit(get_class($container));      
      $container->query('Indexer')->indexFiles($fileIds);
    }

    public static function postShareHook(array $param) {
      if ('file' == $param['itemType']) {
        self::reIndex(array($param['itemSource']));
      }
      else {
        $app = new Application();
        $container = $app->getContainer();
        $files = $container->query('FilesInFolder')->files($param['itemSource']);
        self::reIndex($files);
      }    
    }  

    public static function postUnshareHook(array $param) {
      if ('file' == $param['itemType']) {
        self::reIndex(array($param['itemSource']));
      }
      else {
        $app = new Application();
        $container = $app->getContainer();
        $files = $container->query('FilesInFolder')->files($param['itemSource']);
        self::reIndex($files);
      }
    }  
    
    public static function logit( $text ) {
        $FH = fopen( '/var/www/html/data/borealis.log', 'a' );
        if( is_string( $text ) || is_numeric( $text ) || is_bool( $text ) ) {
          fwrite( $FH, "$text\n" );
        }
        else {
          fwrite( $FH, print_r( $text, true ) );
        }
        fclose( $FH );
    }

}
