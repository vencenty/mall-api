<?php

namespace App\Models\Home;

use Core\Util\Database;
use Core\Util\Response;
use App\Extend\SMS;
use PDO;

class User
{

    public $errno;
    public $errmsg;
    private $link;
    private $userid;

    public function __construct()
    {
        $this->link = Database::connect();
        $this->userid = isset( $_SESSION['user']['id'] ) ?
            $_SESSION['user']['id'] :null;
    }

    public function login()
    {
        $openid = $_GET['openid'];

        $query = $this->link->prepare( "select `mid`,`id`,`openid`,`nickname` from `user` WHERE `openid` = ?" );
        $query->execute( [$openid] );
        $user = $query->fetch( PDO::FETCH_ASSOC );


        if( !$user || count( $user ) == 0 ){
            $this->errno  = 1002;
            $this->errmsg = '您不是微销用户,请注册后登陆';
            return false;
        }


        // 种下用户session;
        $_SESSION['user'] = $user;

        return $user;
    }


    // 获取用户信息
    public function getUserInfo()
    {
        $id = $_SESSION['user']['id'];

        $query = $this->link->prepare( "select `id`,`nickname`,`avatar`,`fgbouns`,`wxlevel` from `user` where id = ?" );
        $query->execute( [$id] );
        $user = $query->fetch( PDO::FETCH_ASSOC );

        return $user;
    }


    // 用户自动注册功能呢
    public function register( $agent_mobile, $username, $mobile,$password ){
        // 检查代理人手机是否存在
        $query = $this->link->prepare("SELECT * FROM `user` WHERE   `mobile` = ?");
        $query->execute( [$agent_mobile] );
        // 这是代理的用户信息
        $agent_user = $query->fetch( PDO::FETCH_ASSOC );

        if( !$agent_user || count( $agent_user ) == 0 ) {
            $this->errno  = 1005;
            $this->errmsg = '推荐人不存在'; 
            return false;
        }

        // 查看当前用户名是否合法
        $query = $this->link->prepare("SELECT * FROM `user` WHERE `username` = ? or `mobile` = ?");
        $query->execute( [ $username, $mobile ] );
        $user_exists = $query->fetchAll( PDO::FETCH_ASSOC );

        /**
        if( count( $user_exists ) ) {
            $this->errno  = 1006;
            $this->errmsg = '用户名或者手机号已经被注册';
            return false;
        }
        */
        

        // 生成盐值
        $salt = create_salt( 32 );

        // 生成密码
        $pwd = md5( $password . $salt );
        
        // 写入数据库
        $query = $this->link->prepare("INSERT `user`(`username`, `mobile`,`password`,`salt`,`newpid`,`createtime`) VALUES( ?, ?, ?, ?, ? ,? ) ");
        $query->execute( [ $username, $mobile, $pwd,$salt, $agent_user['id'] , date("Y-m-d H:i:s", time())] );
        $rowCount = $query->rowCount();

        if( !$rowCount || $rowCount == 0  ) {
            $this->errno = 1007;
            $this->errmsg = '注册失败';
            return false;
        }


        // 清空验证码session
        $_SESSION['user']['code'] = null;
        return true;
    }
        

    // 发送短信信息
    public function sendCode()
    {
        if( empty( $this->userid )){
            $this->errno  = 1012;
            $this->errmsg = '请登录后在进行操作';
            return false;
        }

        // 获取当前登录用户信息
        $query = $this->link->prepare("SELECT * FROM `user` WHERE id = ?");
        $query->execute( [$this->userid] );

        $user = $query->fetch( PDO::FETCH_ASSOC );

        $sms = new SMS();
        
        // 获取当前用户手机号码
        $mobile = $user['mobile'];
        // 要发送的信息
        $code = rand( 100000, 999999 );
        $message = [
            'code'    => (string)$code,
            'product' => '环润微销'
        ];
           
        // 获取短信验证码
        $message = json_encode( $message );


        // 模板ID
        $templateCode =  'SMS_66045006';

        // 签名名称  
        $sign_name = '注册验证';

        $response =  $sms->send( $mobile, $message, $templateCode, $sign_name );

    
        if( isset( $response->code ) ) { 
            $this->errno = 1015;
            $this->errmsg = $response;
            return false;
        }
        
        $_SESSION['user']['code'] = $code;

        // 短信发送成功之后放到session里面
        return true;

    }

    // 获取主账号信息
    public function getMainAccountInfo()
    {
        if( empty( $this->userid ) ) {
            $this->errno = 1018;
            $this->errmsg = '请登录后重试';
            return false;
        }
        

        $query = $this->link->prepare("SELECT * FROM `user` WHERE id = ?");
        $query->execute( [$this->userid] );
        $user = $query->fetch( PDO::FETCH_ASSOC );

        if ( !$user || count( $user ) == 0 ) {
            $this->errno = 1019;
            $this->errmsg = '用户不存在';
        }

        // 查找成功返回用户信息
        return $user;

    }

    public function registerSubAccount( $username,$password, $paypassword, $agent_mobile )
    {
        // 获取主账号信息
        $mainAccount = $this->getMainAccountInfo();
        
        // 子账号用户名是否已经存在了
        $query = $this->link->prepare("SELECT * FROM `user` WHERE `username` = ?");
        $query->execute( [ $username ] );
        
        $row = $query->fetch( PDO::FETCH_ASSOC );
        
        if( isset( $row ) ) {
            $this->errno = 1022;
            $this->errmsg = '用户名已被注册,请更改用户名后重新尝试';
            return false;
        }

        
        if( md5( $paypassword ) != $mainAccount['paypassword'] ) {
            $this->errno  = 1023;
            $this->errmsg = '支付密码错误';
            return false;
        }

        // 根据手机号码获取上级代理信息
        
        $query = $this->link->prepare("SELECT * FROM `user` WHERE   `mobile` = ?");
        $query->execute( [$agent_mobile] );
        // 这是代理的用户信息
        $agent_user = $query->fetch( PDO::FETCH_ASSOC );

        if( !$agent_user || count( $agent_user ) == 0 ) {
            $this->errno  = 1005;
            $this->errmsg = '推荐人不存在'; 
            return false;
        }

        
        $salt = create_salt();
        // 密码加盐值
        $pwd = md5( $password . $salt );
        
        // 否则的话写入数据库
        $query = $this->link->prepare("INSERT INTO `user`(`username`,`password`,`salt`,`mobile`,`newpid`,`createtime`) VALUES( ?, ? , ? , ?, ?, ?)");

        $query->execute( [ $username, $pwd, $salt, $mainAccount['mobile'], $agent_user['id'], date('Y-m-d H:i:s', time()) ] );
        
        $rowCount = $query->rowCount();

        if ( !$rowCount || $rowCount == 0 ) {
            $this->errno = 1023;
            $this->errmsg = '子账号注册失败';
            return false;
        }
        return true;


    } 
}
