<?php

namespace App\Controllers\Home;

use App\Controllers\BaseController;
use App\Models\Home\Cart;
use Core\Util\Request;
use Core\Util\Response;

class CartController extends BaseController
{
    /**
     * 获取当前用户购物车所有信息
     * @return bool
     */
    public function get()
    {
        $model = new Cart();

        if( $data = $model->getUserCart() ){
            echo Response::json( 0, '', $data );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
        }
        return true;
    }

    /**
     * 用户添加购物车功能
     * @return bool
     */
    public function add()
    {
        $id = $_SESSION['user']['id'];

        $goodsid = Request::postQuery( 'id' );
        $price   = Request::postQuery( 'price' );
        $number  = Request::postQuery( 'number' );


        if( !( $goodsid && $price && $number ) ){
            echo Response::json( 4001, '产品ID，价格,数量都不能为空' );
            return false;
        }


        $model = new Cart();

        if( $data = $model->addCart( $goodsid, $price, $number ) ){
            echo Response::json( 0, '', [
                'userid' => $id
            ] );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }
        return true;
    }


    // 购物车信息删除
    public function delete()
    {
        $goodsid = Request::postQuery( 'goodsid' );

        if( empty( $goodsid ) ){
            echo Response::json( 4001, '商品ID不能为空' );
            return false;
        }


        $model = new Cart();

        if( $model->deleteGoods( $goodsid ) ){
            echo Response::json( 0, '删除成功' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
        }
    }
}

















