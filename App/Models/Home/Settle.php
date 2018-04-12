<?php

namespace App\Models\Home;

use Core\Util\Database;
use PDO;
use Exception;

class Settle
{
    public $errno;
    public $errmsg;
    private $link;
    private $userid;

    public function __construct()
    {
        $this->link   = Database::connect();
        $this->userid = $_SESSION['user']['id'];
    }

    /**
     * 生成订单,奖金发放,生成日志
     * @param $orderid
     * @param $paypassword
     * @param $addressid
     * @param $remark
     * @return bool
     */
    public function count( $orderid, $paypassword, $addressid, $remark )
    {

        // 校验支付密码是否正确
        $query = $this->link->prepare( "SELECT * FROM `user` WHERE id = ?" );
        $query->execute( [ $this->userid ] );
        $user = $query->fetch( PDO::FETCH_ASSOC );

        // 512110 小姐姐支付密码


        if( md5( $paypassword ) != $user['paypassword'] ){
            $this->errno  = '7003';
            $this->errmsg = '支付密码不正确';
            return false;
        }


        // 判断自己的复购余额是否超过了订单价格,如果超过了,返回错误信息
        $query = $this->link->prepare( "SELECT * FROM `order` where `id` = ?" );
        $query->execute( [ $orderid ] );
        $order = $query->fetch( PDO::FETCH_ASSOC );


        // 复购余额检测,当前用户额检测
        if( (float)$order['realprice'] > (float)$user['fgbouns'] ){
            $this->errno  = 7003;
            $this->errmsg = '您的复购余额不足';
            return false;
        }

        /**
         * 写入订单表,添加订单地址,修改订单状态为 1买家已经支付
         */


        $this->link->beginTransaction();

        try{
            // 开启错误提示
            $this->link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

            // 修改订单地址状态
            $query = $this->link->prepare( "UPDATE `order` SET `status` = ?, `addressid` = ?,`remark` = ?  where id = ?" );
            $query->execute( [ 1, $addressid, $remark, $orderid ] );

            $rowCount = $query->rowCount();

            if( $rowCount == 0 ){
                throw new Exception( '订单状态修改失败' );
            }

            // 扣除对应的奖金信息
            $query = $this->link->prepare( "UPDATE `user` set  `fgbouns` = `fgbouns` - ? where id = ?" );
            $query->execute( [ $order['realprice'], $this->userid ] );
            $rowCount = $query->rowCount();

            if( $rowCount == 0 ){
                throw new Exception( '奖金扣除失败' );
            }

            // 写入日志表
            // 写入订单ID,扣除的钱,用户的ID,扣除时间
            $query = $this->link->prepare( "INSERT INTO `order_log`(`userid`,`orderid`,`money`,`createtime`) VALUES(?,?,?,?)" );
            $query->execute( [ $this->userid, $orderid, $order['realprice'], date( 'Y-m-d H:i:s', time() ) ] );
            $rowCount = $query->rowCount();

            if( $rowCount == 0 ){
                throw new Exception( '日志写入失败' );
            }
            // 提交事物
            $this->link->commit();
        } catch( Exception $e ){
            $this->link->rollBack();
            $this->errno  = 10000;
            $this->errmsg = $e->getMessage();
            return false;
        }


        // 发奖金
        $bouns = new Bouns();
        // 获取订单所有信息
        $order = $this->link->query( "select * from `order` where id = {$orderid}" )->fetch( PDO::FETCH_ASSOC );
        // 调用方法发奖金

        $bouns->iswx( $order, $_SESSION['user']['mid'] );

        // 更新一下对应的本地的USERid字段

        // 千河商城ID会员集合
        $users = $this->link->query( "select `id`,`mid` from `user`" )->fetchAll( PDO::FETCH_ASSOC );
        // 本地会员ID集合
        $members = $this->link->query( "SELECT * FROM `bouns`" )->fetchAll( PDO::FETCH_ASSOC );


        // 放到字典里面
        foreach( $users as $key => $user ){
            foreach( $members as $k => $member ){
                if( $user['mid'] == $member['mid'] ){
                    $query = $this->link->prepare( "UPDATE `bouns`  set `userid`=?  where `mid` = ?" );
                    $query->execute( [ $user['id'], $user['mid'] ] );
                }

                if( $user['mid'] == $member['fromid'] ){
                    $query = $this->link->prepare( "UPDATE `bouns` set `originid` = ? WHERE `fromid` = ?" );
                    $query->execute( [ $user['id'], $user['mid'] ] );
                }
            }

        }

        return true;
    }
}