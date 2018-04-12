<?php

namespace App\Models\Home;

use Core\Util\Database;
use Core\Util\Response;
use PDO;
use Exception;

class Account
{
    public $errno;
    public $errmsg;
    private $userid;
    private $link;

    public function __construct()
    {
        $this->link   = Database::connect()->link;
        $this->userid = $_SESSION['user']['id'];
    }

    /**
     * 用户转账
     * @param $mobile
     * @param $money
     * @param $paypassword
     * @return bool
     */
    public function execute( $mobile, $money, $paypassword )
    {
        // 获取当前用户信息
        $query = $this->link->prepare( "SELECT * FROM `user` WHERE `id`=?" );
        $query->execute( [ $this->userid ] );
        $user = $query->fetch( PDO::FETCH_ASSOC );

        // 获取获得转账的用户的信息
        $query = $this->link->prepare( "SELECT * FROM `user` WHERE `mobile` = ?" );
        $query->execute( [ $mobile ] );
        $accept_user = $query->fetch( PDO::FETCH_ASSOC );

        if( !$accept_user || count( $accept_user ) == 0 ){
            $this->errno  = '9008';
            $this->errmsg = '您要转账的用户不存在';
            return false;
        }


        // 验证支付密码
        $pwd = md5( $paypassword );

        if( $pwd != $user['paypassword'] ){
            $this->errno  = 9004;
            $this->errmsg = '支付密码不正确';
            return false;
        }

        // 验证钱是否足额
        if( (float)$user['fgbouns'] < (float)$money ){
            $this->errno  = 9005;
            $this->errmsg = '复购余额不足';
            return false;
        }


        /**
         * 事务转账,如果执行中语句发生错误,或者用户转账过程中发生错误都将回滚
         */
        $this->link->beginTransaction();

        try{
            // 开启PDO错误提示
            $this->link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            // 收账人收钱
            $query = $this->link->prepare( "UPDATE `user` SET `fgbouns` = `fgbouns` + ? WHERE  `id` = ?" );
            $query->execute( [ $money, $accept_user['id'] ] );
            $transfer_row = $query->rowCount();

            // 转账人扣钱
            $query = $this->link->prepare( "UPDATE `user` SET `fgbouns` = `fgbouns` - ? WHERE `id` = ?" );
            $query->execute( [ $money, $user['id'] ] );
            $accept_row = $query->rowCount();

            // 两方面操作都成功了,提交否则回滚
            if( $transfer_row && $accept_row ){
                $this->link->commit();
            }else{
                $this->link->rollBack();
                // 转账失败
                $this->errno = 9000;
                return false;
            }
        } catch( Exception $e ){
            $this->link->rollBack();
            // 转账失败
            $this->errno  = 9000;
            $this->errmsg = $e->getMessage();
            return false;
        }


        // 转账记录写入转账日志表
        $query = $this->link->prepare( "INSERT INTO `transfer_log`(`transferid`,`transfermobile`,`acceptid`,`acceptmobile`,`money`,`createtime`) VALUES(?,?,?,?,?,?)" );
        $query->execute( [ $user['id'], $user['mobile'], $accept_user['id'], $accept_user['mobile'], $money, date( 'Y-m-d H:i:s', time() ) ] );

        $rowCount = $query->rowCount();

        if( $rowCount == 0 ){
            $this->errno  = 9007;
            $this->errmsg = '转账成功,但是日志写入失败';
            return false;
        }

        return true;

    }


    /**
     * 获取当前用户所有转账信息
     * @return array
     */
    public function getTransferRecord()
    {

        // 获取当前用户的复购余额
        $query = $this->link->prepare( "SELECT * FROM `user` WHERE id = ?" );
        $query->execute( [ $this->userid ] );
        $user = $query->fetch( PDO::FETCH_ASSOC );

        // 当前用户的复购余额
        $fgbouns = $user['fgbouns'];

        // 获取当前用户信息和转账信息
        $query = $this->link->prepare( "SELECT * FROM `transfer_log` WHERE `transferid` = ?" );
        $query->execute( [ $this->userid ] );
        $rows = $query->fetchAll( PDO::FETCH_ASSOC );


        if( !$rows || count( $rows ) == 0 ){
            $rows = null;
        }


        return [
            'fgbouns' => $user['fgbouns'],
            'rows'    => $rows
        ];

    }
}