<?php

namespace App\Controllers\Home;

use App\Models\Home\User;
use Core\Util\ErrMap;
use Core\Util\Request;
use Core\Util\Response;
use App\Extend\SMS;

class UserController
{
    /**
     * 用户登陆,返回是否登陆成功
     * @return bool
     */
    public function login()
    {
        // 检测有没有openid,没有的直接T到首页
        $openid = Request::getQuery( 'openid' );

        // 没有openid的话直接跳转到千河商城首页
        if( empty( $openid ) ){
            header( 'location:http://cx.qhsc1319.com/mall/public/preview/login' );
        }

        $model = new User();


        if( $data = $model->login() ){
            echo Response::json( 0, '', $data );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;


    }




    /**
     * 用户退出
     * @return bool
     */
    public function logout()
    {
        // 清空服务器所有session
        session_destroy();
        echo Response::json( 0 );
        return true;
    }


    // 用户我的界面
    public function me()
    {
        if( empty( $_SESSION['user'] ) ){
            echo Response::json( '1001', '请登录后重试', [
                'callbackurl' => 'http://cx.qhsc1319.com/mall/public/preview/login'
            ] );
            die;
        }
        // 获取用户信息
        $user = ( new User )->getUserInfo();

        echo Response::json( 0, '', $user );


    }

    // 注册发短信
    public function sendCode()
    {

        // 给当前登录用户发短信
        $model = new User(); 
        
        if ( $model->sendCode() ) {
            echo Response::json( 0 );
        } else {
            echo Response::json(
                $model->errno,
                $model->errmsg
            );
            return false;
        }
        return true;
        
    }


    

    /**
    * 用户自动注册功能
    */
    public function register()
    {
        $agent_mobile       = Request::postQuery("agent_mobile");
        $username           = Request::postQuery("username");
        $mobile             = Request::postQuery("mobile");
        $password           = Request::postQuery("password");
        $repassword         = Request::postQuery("repassword"); // 确认支付密码
        $code               = Request::postQuery("code"); // 手机验证码


        // 验证手机号码是否全
        if( !( $agent_mobile && $mobile && $password && $code ) ){
            echo Response::json( 1003, '参数不全' );
            return false;
        }

        // 看看有没有验证码
        if( empty( $_SESSION['user']['code'] ) ) {
            echo Response::json( 1009, '请先获取验证码' );
            return false;
        }
        
        // 验证验证码是否合适
        if( $code != $_SESSION['user']['code'] ) {
            echo Response::json(1010, '验证码不正确' );
            return false;
        }

        // 验证两次密码是否相同
        if( $password != $repassword ) {
            echo Response::json( 1004, "两次输入密码不一致" );
            return false;
        }
        

        // 获取用户模型
        $model = new User();

        if( $model->register( $agent_mobile, $username, $mobile, $password, $code ) ){
            echo Response::json( 0 );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }
        return true;
    }


    // 获取主账号信息
    public function mainAccountInfo()
    {
        $model = new User();
        // 获取主账号所有信息
        if ( $data = $model->getMainAccountInfo() ) {
            echo Response::json( 0, '', $data );
        } else {
            echo Response::json( 
                $model->errno,
                $model->ermsg
            );
            return false;
        }
        return true;
    }

    // 注册子账号功能
    public function subAccountRegister()
    {
        $username       = Request::postQuery( 'username' );
        $password       = Request::postQuery( 'password' );
        $agent_mobile   = Request::postQuery( 'agent_mobile' );
        // 支付密码验证
        $paypassword    = Request::postQuery( 'paypassword' );

        if( !( $username && $paypassword && $password && $agent_mobile) ) {
            echo Response::json( 1020, '参数不全' );
            return false;
        }

        $model = new User();

        if( $data = $model->registerSubAccount( $username,$password, $paypassword, $agent_mobile ) ) {
            echo Response::json( 0 );
        } else {
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;
        
    }



}
