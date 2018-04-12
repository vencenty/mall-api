<?php

namespace App\Controllers\Home;

use App\Controllers\BaseController;
use App\Models\Home\Goods;
use App\Models\Home\User;
use Core\Util\Request;
use Core\Util\Response;

class GoodsController extends BaseController
{
    /**
     * 获取商品列表信息
     * @return bool
     */
    public function info()
    {
        // 拉取商品信息
        $data = ( new Goods() )->getGoodsInfo();
        echo Response::json( 0, '', $data );
        return true;
    }

    /**
     * 获取商品详情
     * @return bool
     */
    public function detail()
    {
        $goodsid = Request::getQuery( 'id' );

        if( empty( $goodsid ) ){
            echo Response::json( 2001, '请选择要查看的商品' );
            return false;
        }


        $model = new Goods();

        if( $data = $model->getGoodsDetail( $goodsid ) ){
            echo Response::json( 0, '', $data );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;

    }


}