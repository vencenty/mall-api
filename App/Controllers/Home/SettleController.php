<?php

namespace App\Controllers\Home;

use App\Controllers\BaseController;
use App\Models\Home\Settle;
use Core\Util\Request;
use Core\Util\Response;

class SettleController extends BaseController
{
    /**
     * 立即结算
     * @return bool
     */
    public function buyNow()
    {

        $orderid     = isset( $_POST['orderid'] ) ? $_POST['orderid'] : null;
        $paypassword = isset( $_POST['paypassword'] ) ? $_POST['paypassword'] : null;
        $addressid   = isset( $_POST['addressid'] ) ? $_POST['addressid'] : null;
        $remark      = Request::postQuery( 'remark' );


        if( empty( $paypassword ) || empty( $addressid ) || empty( $orderid ) ){
            echo Response::json( 7001, '支付密码地址ID和订单都不能为空' );
            return false;
        }


        $model = new Settle();
        // 处理的订单
        if( $data = $model->count( $orderid, $paypassword, $addressid, $remark ) ){
            echo Response::json( 0, '支付成功' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;
    }
}