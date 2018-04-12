<?php

namespace App\Models\Home;

use Core\Util\Database;
use PDO;

class Address
{
    private $link;
    public $errno;
    public $errmsg;
    private $userid;
    private $mid;

    public function __construct()
    {

        $this->link   = Database::connect();
        $this->userid = $_SESSION['user']['id'];
        $this->mid    = $_SESSION['user']['mid'];
    }

    /**
     *获取用户所有地址信息,如果没有返回false
     * @return bool|array
     */
    public function getUserAddress()
    {
        $query = $this->link->prepare( "select * from `address` where (`mid` = ? and `deleted`  = 0) or (`userid` = ? and `deleted` = 0) " );
        $query->execute( [ $this->mid, $this->userid ] );


        $address = $query->fetchAll( PDO::FETCH_ASSOC );

        if( !$address || count( $address ) == 0 ){
            $this->errno  = '3001';
            $this->errmsg = '用户地址不存在';
            return false;
        }

        return $address;
    }


    /**
     * 添加地址信息
     * @param $realname
     * @param $mobile
     * @param $province
     * @param $city
     * @param $area
     * @param $address
     * @param $street
     * @param $postcode
     * @return bool
     */
    public function addAddress( $realname, $mobile, $province, $city, $area, $address, $street, $postcode )
    {
        // 把用户地址存进去
        $query = $this->link->prepare( "INSERT INTO `address`(`userid`,`mobile`,`realname`,`province`,`city`,`area`,`address`,`street`,`postcode`) VALUES(?,?,?,?,?,?,?,?,?)" );
        $query->execute( [ $this->userid, $mobile, $realname, $province, $city, $area, $address, $street, $postcode ] );

        $rowCount = $query->rowCount();
        if( $rowCount == 0 ){
            $this->errno  = 3005;
            $this->errmsg = '千河商城写入成功';
            return false;
        }

        // 写入千河商城数据库

        $query = $this->link->prepare( "INSERT INTO `ims_ewei_shop_member_address`(`uniacid`,`openid`,`mobile`,`realname`,`province`,`city`,`area`,`address`,`street`,`streetdatavalue`) VALUES(?,?,?,?,?,?,?,?,?,?)" );
        $query->execute( [ 4, $_SESSION['user']['openid'], $mobile, $realname, $province, $city, $area, $address, $street, $postcode ] );


        // 获取执行结果
        $rowCount = $query->rowCount();
        if( $rowCount == 0 ){
            $this->errno  = 3005;
            $this->errmsg = '千河商城写入失败';
            return false;
        }

        return true;
    }


    /**
     * 删除用户地址
     * @param $addressid
     * @return bool
     */
    public function deleteAddress( $addressid )
    {
        // 查看地址是否已经被删除了
        $query = $this->link->prepare( "SELECT * FROM `address` WHERE id  = ?" );
        $query->execute( [ $addressid ] );

        $address = $query->fetch( PDO::FETCH_ASSOC );

        if( !$address || count( $address ) == 0 ){
            // 地址不存在
            $this->errno  = '123';
            $this->errmsg = '地址不存在';
            return false;
        }


        if( $address['deleted'] == 1 ){
            $this->errno  = '123';
            $this->errmsg = '地址已经被删除';
            return false;
        }


        $query = $this->link->prepare( "UPDATE `address` set `deleted` = 1 WHERE `userid` = ? and `id`= ?" );
        $query->execute( [ $this->userid, $addressid ] );

        $rowCount = $query->rowCount();

        if( $rowCount == 0 ){
            $this->errno  = '3003';
            $this->errmsg = '地址删除失败';

            return false;
        }

        return true;

    }

    /**
     * 把用户地址设置为默认
     * @param $addressid
     * @return bool
     */
    public function setAddressAsDefault( $addressid )
    {
        // 先把全部地址更新成为不是默认,
        $query = $this->link->prepare( "UPDATE `address` set `isdefault` = ? WHERE `userid` =? " );
        $query->execute( [ 0, $this->userid ] );

        $query = $this->link->prepare( "UPDATE `address` set `isdefault` = ? where id = ?" );
        $query->execute( [ 1, $addressid ] );

        return true;
    }


    public function getAddressById( $addressid )
    {
        $query = $this->link->prepare( "SELECT * FROM `address` WHERE `id` = ?" );
        $query->execute( [ $addressid ] );
        $address = $query->fetch( PDO::FETCH_ASSOC );


        return [
            'address' => $address
        ];
    }

    /**
     * 用户地址编辑
     * @param $addressid
     * @param $realname
     * @param $mobile
     * @param $province
     * @param $city
     * @param $area
     * @param $address
     * @param $street
     * @param $postcode
     * @return bool
     */
    public function editAddress( $addressid, $realname, $mobile, $province, $city, $area, $address, $street, $postcode )
    {
        // 存储用户地址i
        $query = $this->link->prepare( "UPDATE `address` set realname=?, mobile=?, province=?, city=?, area=?,address=?, street=?, postcode=? where `id` = ?" );
        $query->execute( [ $realname, $mobile, $province, $city, $area, $address, $street, $postcode, $addressid ] );

        // 获取受影响的记录条数
        $rowCount = $query->rowCount();


        if( $rowCount == 0 ){
            $this->errno  = 3005;
            $this->errmsg = '地址编辑失败';
            return false;
        }

        return true;
    }

}