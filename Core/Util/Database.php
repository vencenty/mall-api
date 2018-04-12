<?php

namespace Core\Util;

use Exception;
use PDO;

class Database
{
    protected static $instances = [];

    static function connect( $resource = 'default' )
    {
        if( isset( self::$instances[ $resource ] ) ){
            return self::$instances[ $resource ];
        }else{
            try{
                $item = ( new static )->make( $resource );
            } catch( Exception $e ){
                echo Response::json( 99999, $e->getMessage() );
                return false;
            }
            self::$instances[ $resource ] = $item;
            return $item;
        }
    }

    protected function make( $dbname )
    {
        // 千河商城数据库
        if( $dbname == 'we7' ){
            $link = new PDO( 'mysql:host=rm-2zen83c2mci6syit6o.mysql.rds.aliyuncs.com;dbname=we7', 'mxg2', 'Bijiao2@', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION) );
            $link->query( "set names utf8" );
        }elseif( $dbname == 'default' ){
            $link = new PDO( 'mysql:host=localhost;dbname=hrwx', 'root', 'root', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION) );
            $link->query( "set names utf8" );
        }else{
            throw new Exception( '数据库连接失败' );
        }

        return $link;

    }
}

