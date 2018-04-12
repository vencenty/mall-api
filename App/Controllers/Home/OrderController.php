<?php

namespace App\Controllers\Home;

// 指定允许其他域名访问
use App\Controllers\BaseController;
use App\Models\Home\Order;
use Core\Library\Cache\Cache;
use Core\Util\Build;
use Core\Util\ErrMap;
use Core\Util\Request;
use Core\Util\Response;

class OrderController extends BaseController
{
    /**
     * 生成订单信息
     * @return bool
     */
    public function store()
    {
        $data = Request::postQuery( 'data' );

        if( empty( $data ) ){
            echo Response::json( 6066, '参数不全' );
            return false;
        }

        // 解码前端数据
        $data = json_decode( $data, true );

        // 带0是直接结算， 带1是从购物车直接结算
        $isCart = isset( $_POST['isCart'] ) ? $_POST['isCart'] : 'error';

        // 是否是通过购物车添加的商品
        $model = new Order();

        if( $insertId = $model->storeOrder( $data, $isCart ) ){
            echo Response::json( 0, '下单成功', $insertId );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;
    }

    /**
     * 获取当前用户所有订单信息
     * @return bool
     */
    public function get()
    {

        $orderid = isset( $_GET['orderid'] ) ? trim( $_GET['orderid'] ) : null;


        if( empty( $orderid ) ){
            echo Response::json( 6003, '订单ID不能为空' );
            return false;
        }

        $model = new Order();

        // 获取订单信息
        if( $data = $model->getUserOrderById( $orderid ) ){
            echo Response::json( 0, '', $data );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
        }
    }

    // 所有订单信息
    public function all()
    {
        $model = new Order();

        if( $data = $model->getAllOrder() ){
            echo Response::json( 0, '', $data );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
        }

    }

    /**
     * 取消用户订单
     * @return bool
     */
    public function cancel()
    {
        $orderid = isset( $_POST['orderid'] ) ? trim( $_POST['orderid'] ) : null;

        if( empty( $orderid ) ){
            echo Response::json( 6010, '订单不能为空' );
            return false;
        }
        $model = new Order();

        if( $data = $model->cacenlOrder( $orderid ) ){
            echo Response::json( 0, '' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;
    }


    /**
     * 删除用户订单
     * @return bool
     */
    public function delete()
    {
        $orderid = Request::getQuery('orderid');

        if( empty( $orderid ) ){
            echo Response::json( 6012, '订单ID不能为空' );
            return false;
        }

        $model = new Order();

        if( $data = $model->deleteOrder( $orderid ) ){
            echo Response::json( 0 );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;
    }

}