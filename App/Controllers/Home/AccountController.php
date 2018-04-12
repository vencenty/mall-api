<?php

namespace App\Controllers\Home;

use App\Controllers\BaseController;
use App\Models\Home\Account;
use Core\Util\Request;
use Core\Util\Response;

class AccountController extends BaseController
{
    /**
     * 用户转账功能
     * @return bool
     */
    public function transfer()
    {
        $paypassword = Request::postQuery( 'paypassword' );
        $money       = Request::postQuery( 'money' );
        $mobile      = Request::postQuery( 'mobile' );


        if( empty( $paypassword ) || empty( $money ) || empty( $mobile ) ){
            echo Response::json( 9001, '参数不全' );
            return false;
        }


        if( false == preg_match( '/^0?(13|14|15|17|18)[0-9]{9}$/', $mobile ) ){
            echo Response::json( 9002, '手机号码不合法' );
            return false;
        }


        $model = new Account();

        if( $model->execute( $mobile, $money, $paypassword ) ){
            echo Response::json( 0, '转账成功' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;
    }


    /**
     * 获取用户所有转账的流水
     * @return bool
     */
    public function transferRecord()
    {
        $model = new Account();

        // 获取日志信息
        if( $data = $model->getTransferRecord() ){
            echo Response::json( 0, '', $data );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;
    }
}