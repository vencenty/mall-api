<?php

namespace App\Models\Home;

use Core\Util\Database;
use Core\Util\Response;
use PDO;

class Goods
{
    public $errno;
    public $errmsg;
    private $link;

    public function __construct()
    {
        $this->link = Database::connect();
    }

    public function getGoodsInfo()
    {
        $query = $this->link->query( "select `id`,`title`,`displayorder`,`thumb`,`realprice`,`status` from  `goods`" );
        $goods = $query->fetchAll( PDO::FETCH_ASSOC );

        return $goods;
    }

    // 获取商品详情页面
    public function getGoodsDetail( $goodsid )
    {
        $query = $this->link->prepare( "select `realprice`,`price`,`id`,`content`,`title`,`thumb`,`total`,`sales` from goods WHERE id = ?" );
        $query->execute( [ $goodsid ] );
        $detail = $query->fetch( PDO::FETCH_ASSOC );

        if( !$detail || count( $detail ) == 0 ){
            $this->errno  = 1005;
            $this->errmsg = '商品查找失败';
            return false;
        }


        return $detail;
    }
}