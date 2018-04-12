<?php

namespace App\Controllers\Query;

use App\Controllers\BaseController;

use App\Extend\SMS;
use App\Models\Query\Pay;
use Core\Util\Request;
use Core\Util\Response;

class PayController extends BaseController
{
    // 设置支付密码
    public function setPassword()
    {
        $paypassword   = (integer)Request::postQuery( 'paypassword' );
        $repaypassword = (integer)Request::postQuery( 'repaypassword' );


        if( !( $paypassword && $repaypassword ) ){
            echo Response::json( 8001, '参数不全' );
            return false;
        }

        if( $paypassword != $repaypassword ){
            echo Response::json( 8004, '两次密码输入不一致' );
            return false;
        }


        if( false == preg_match( '/(\d){6}/', $paypassword ) || false == preg_match( '/(\d){6}/', $repaypassword ) ){
            echo Response::json( '8007', '支付密码只能六位纯数字' );
            return false;
        }

        $model = new Pay();

        if( $model->storePassword( $paypassword ) ){
            echo Response::json( 0 );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }


        return true;
    }


    /**
     * 重置支付密码
     * @return bool
     */
    public function resetPassword()
    {
        $question    = Request::postQuery( 'question' ); // 验证问题
        $code        = Request::postQuery( 'code' ); // 短信验证码
        $paypassword = Request::postQuery( 'paypassword' ); // 新的支付密码

        $model = new Pay();

        if( $model->resetPassword( $question, $code, $paypassword ) ){
            echo Response::json( 0 );
        }else{
            echo Response::json(
                $model->errno,
                $model->errmsg
            );
            return false;
        }
        return true;

    }


    // 设置安全问题
    public function setQuestion()
    {
        // 获取所有支付问题
        $question = Request::postQuery( 'question' );

        if( empty( $question ) ){
            echo Response::json( 8001, '参数不全' );
            return false;
        }

        $model = new Pay();

        // 写入数据库
        if( $model->storeQuestion( $question ) ){
            echo Response::json( 0, '设置成功' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }
        return true;
    }


    /**
     * 发送短信验证码
     */
    public function code()
    {


        $model = new Pay();


        if( $data = $model->sendCode() ){
            echo Response::json( 0, '',$data );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }
        return true;
    }


}
