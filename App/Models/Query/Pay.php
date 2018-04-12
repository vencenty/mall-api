<?php

namespace App\Models\Query;

use App\Extend\SMS;
use Core\Util\Database;
use Core\Util\Response;
use PDO;

class Pay
{
    public $errno;
    public $errmsg;
    private $link;
    private $userid;


    public function __construct( $sms = null )
    {
        $this->link   = Database::connect();
        $this->userid = $_SESSION['user']['id'];

    }

    // 保存安全问题
    public function storeQuestion( $question )
    {
        $query = $this->link->prepare( "SELECT *  FROM `user` WHERE `id` = ?" );
        $query->execute( [$this->userid] );
        $user = $query->fetch( PDO::FETCH_ASSOC );

        if( isset( $user['question'] ) ){
            $this->errno  = 8003;
            $this->errmsg = '安全问题已经设置,无需重复设置';
            return false;
        }


        $query = $this->link->prepare( 'UPDATE `user` SET `question` = ? WHERE `id`=?' );
        $query->execute( [$question, $this->userid] );

        $rowCount = $query->rowCount();

        if( !$rowCount ){
            $this->errno  = 8002;
            $this->errmsg = '安全问题设置失败';
            return false;
        }

        return true;
    }

    // 保存支付密码
    public function storePassword( $password )
    {
        // 看看有没有支付密码
        $query = $this->link->prepare( "SELECT * FROM `user` where id = ?" );
        $query->execute( [$this->userid] );
        $user = $query->fetch( PDO::FETCH_ASSOC );


        if( !empty( $user['paypassword'] ) ){
            $this->errno  = 8006;
            $this->errmsg = '支付密码已经设置成功,请勿重新设置';
            return false;
        }


        $pwd   = md5( $password );
        $query = $this->link->prepare( "UPDATE `user` set `paypassword` = ? where `id` = ?" );
        $query->execute( [$pwd, $this->userid] );

        $rowCount = $query->rowCount();

        if( $rowCount == 0 ){
            $this->errno  = 8005;
            $this->errmsg = '支付密码保存失败';
        }

        return true;
    }


    /**
     *重置支付密码
     */
    public function resetPassword( $question, $code, $paypassword )
    {
        $query = $this->link->prepare( "SELECT * FROM `user` WHERE  `id` = ? " );
        $query->execute( [$this->userid] );
        $user = $query->fetch( PDO::FETCH_ASSOC );


        // 必须全部正确
        if( $user['question'] != $question ){
            $this->errno  = 8005;
            $this->errmsg = '安全问题出错啦';
            return false;
        }

        return true;

    }

    /**
     *
     */
    public function sendCode()
    {
        /**
         * 获取用户信息
         */
        $query = $this->link->prepare( "SELECT * FROM `user` WHERE  `id` = ?" );
        $query->execute( [$this->userid] );
        $user = $query->fetch( PDO::FETCH_ASSOC );


        // 生成随机验证码\
        $code = rand( 100000, 999999 );

        $result = SMS::send( $user['mobile'], "{\"code\":\"$code\"}", 'SMS_122020009', '变更验证' );

        // 发送短信
        if( isset( $result->code ) ){
            $this->errno = '8888';
            $this->errmsg = $result;
            return false;
        }
        // 写入session中
        $_SESSION['user']['code'] = $code;

        return $result;
    }


}