<?php

namespace App\Models\Home;

use Core\Util\Database;
use Core\Util\ErrMap;
use PDO;

class Order
{

    public $errno;
    public $errmsg;
    private $link;
    private $userid;

    public function __construct()
    {
        //// 本地数据库连接
        //$this->local =  Database::connect('local')->link;
        // 远程数据库连接
        $this->link = Database::connect();
        // 获取用户ID
        $this->userid = $_SESSION['user']['id'];
    }

    // 生成订单
    public function storeOrder( $data, $isCart )
    {
        // 计算订单总价格
        $orderprice = 0;
        foreach( $data as $key => $val ){
            $orderprice = $orderprice + (float)$val['price'] * $val['total'];
        }

        // 订单表写入数据
        $query = $this->link->prepare( "INSERT INTO `order`(userid,realprice,`status`,createtime) VALUES(?, ?, ?, ?)" );
        $query->execute( [ $this->userid, $orderprice, 0, date( 'Y-m-d H:i:s', time() ) ] );
        $rowCount = $query->rowCount();

        if( $rowCount == 0 ){
            $this->errno  = '6001';
            $this->errmsg = '订单写入失败';
            return false;
        }

        // 获取订单ID
        $orderid = $this->link->lastInsertId();


        // 定义要删除的商品ID
        $goodsids = [];
        foreach( $data as $key => $value ){

            $sql   = "INSERT INTO `order_goods`(`orderid`,`goodsid`,`userid`,`total`,`price`,`createtime`) VALUES(?, ?, ?, ?, ?, ?)";
            $query = $this->link->prepare( $sql );
            $query->execute( [ $orderid, $value['goodsid'], $this->userid, $value['total'], $value['price'], date( 'Y-m-d H:i:s', time() ) ] );

            // 影像记录条数
            $rowCount = $query->rowCount();

            if( $rowCount == 0 ){
                $this->errno  = 6002;
                $this->errmsg = '订单商品表写入失败';
                return false;
            }


            $goodsids[] = $value['goodsid'];
        }

        $goodsids = implode( ',', $goodsids );

        //var_dump("DELETE FROM `cart` WHERE `userid` = ? and `goodsid` in ($goodsids) ");

        // 删除购物车商品
        if( $isCart == 1 ){
            // 删除掉购物车商品
            $query = $this->link->prepare( "DELETE FROM `cart` WHERE `userid` = ? and `goodsid` in ($goodsids) " );
            $query->execute( [ $this->userid ] );
            $rowCount = $query->rowCount();
            //$res = $query->errorInfo();
            //var_dump($res);
            if( $rowCount == 0 ){
                $this->errno  = 6003;
                $this->errmsg = '购物车商品删除失败';
                return false;
            }
        }


        return [ 'orderid' => $orderid ];
    }


    // 获取用户订单信息
    public function getUserOrderById( $orderid )
    {
        // 获取订单商品表信息
        $query = $this->link->prepare( "SELECT * FROM order_goods WHERE orderid = ? " );
        $query->execute( [ $orderid ] );
        $orderGoods = $query->fetchAll( PDO::FETCH_ASSOC );

        if( !$orderGoods ){
            $this->errno  = 6004;
            $this->errmsg = '订单信息为空';
            return false;
        }

        // 以订单价格为准
        $goodsids = implode( ',', array_column( $orderGoods, 'goodsid' ) );

        $goods = $this->link->query( "SELECT `id`,`title`,`thumb` FROM goods WHERE id in ($goodsids)" )
            ->fetchAll( PDO::FETCH_ASSOC );


        // 获取前台提交的地址ID,如果不为空,查出对应的地址信息
        $addressid = isset( $_GET['addressid'] ) ? trim( $_GET['addressid'] ) : 0;
        // 用户不选择地址,默认选择在数据库中默认的地址,否则选择用户应该的地址
        if( is_numeric( $addressid ) ){
            if( $addressid == 0 ){
                $query = $this->link->prepare( "SELECT * FROM address WHERE userid=? and isdefault = ?" );
                $query->execute( [ $this->userid, 1 ] );
            }else{
                $query = $this->link->prepare( "SELECT * FROM address WHERE  userid = ? and id = ?" );
                $query->execute( [ $this->userid, $addressid ] );
            }
        }else{
            $this->errno  = '6008';
            $this->errmsg = '用户地址参数不正确';
            return false;
        }

        $address = $query->fetch( PDO::FETCH_ASSOC );

        if( !$address || count( $address ) == 0 ){
            $this->errno  = '6005';
            $this->errmsg = '用户地址为空';
            return false;
        }

        // 获取当前用户复购余额
        $query = $this->link->prepare( "SELECT * from user WHERE id =  ? " );
        $query->execute( [ $this->userid ] );
        $fgbouns = $query->fetch( PDO::FETCH_ASSOC )['fgbouns'];


        $result = [];
        $order  = [];
        foreach( $goods as $key => $item ){
            foreach( $orderGoods as $k => $v ){
                if( $v['goodsid'] == $item['id'] ){
                    $order[ $k ] = [
                        'goodsid' => $item['id'],
                        'thumb'   => $item['thumb'],
                        'title'   => $item['title'],
                        'price'   => $v['price'],
                        'total'   => $v['total'],
                    ];
                }
            }
        }


        $result['order']              = $order;
        $result['userDefaultAddress'] = $address;
        $result['fgbouns']            = $fgbouns;
        $result['orderid']            = $orderid;

        return $result;

    }


    // 获取用户所有订单信息
    public function getAllOrder()
    {
        // 获取当前用户所有订单
        $query = $this->link->prepare( "SELECT * FROM `order` WHERE userid = ? and `deleted` <> ? ORDER  by `createtime` desc" );
        $query->execute( [ $this->userid, 1 ] );
        $orders = $query->fetchAll( PDO::FETCH_ASSOC );


        if( empty( $orders ) ){
            //list( $this->errno, $this->errmsg ) = ErrMap::get(4008);
            $this->errno  = '6007';
            $this->errmsg = '该用户暂无订单';
            return false;
        }


        $orderids = implode( ',', array_column( $orders, 'id' ) );

        // 获取所有的商品订单ID
        $orderGoods = $this->link->query( "SELECT * FROM `order_goods` WHERE orderid in ($orderids)" )
            ->fetchAll( PDO::FETCH_ASSOC );

        $goodsids = implode( ',', array_unique( array_column( $orderGoods, 'goodsid' ) ) );

        $goods = $this->link->query( "SELECT `id`,`title`,`thumb` FROM goods WHERE id in ($goodsids)" )
            ->fetchAll( PDO::FETCH_ASSOC );


        $order = [];
        foreach( $orders as $key => $value ){
            foreach( $orderGoods as $k => $v ){
                if( $v['orderid'] == $value['id'] ){
                    foreach( $goods as $i => $item ){
                        if( $item['id'] == $v['goodsid'] ){
                            // 商品信息
                            $order[ $value['id'] ]['goodsInfo'][] = [
                                'title' => $item['title'],
                                'thumb' => $item['thumb'],
                                'total' => $v['total'],
                                'price' => $v['price']
                            ];
                            $order[ $value['id'] ]['orderid']     = $value['id'];
                            $order[ $value['id'] ]['orderprice']  = $value['realprice'];
                            $order[ $value['id'] ]['status']      = $value['status'];
                        }
                    }
                }
            }
        }


        return array_values( $order );

    }


    /**
     * 根据订单id取消订单
     * @param $orderid
     * @return bool
     */
    public function cacenlOrder( $orderid )
    {

        // 先看看订单是否是取消状态
        $query = $this->link->prepare( "SELECT `status` from `order` WHERE id = ? " );
        $query->execute( [ $orderid ] );
        $status = $query->fetch( PDO::FETCH_ASSOC )['status'];

        if( $status == '-1' ){
            $this->errno  = '6011';
            $this->errmsg = '订单已经被取消';
            return false;
        }


        $query = $this->link->prepare( "UPDATE `order` set `status` = ? where id = ?" );
        $query->execute( [ - 1, $orderid ] );

        $count = $query->rowCount();

        if( !$count || count( $count ) == 0 ){
            $this->errno  = '6012';
            $this->errmsg = '取消订单失败';
            return false;
        }

        return true;

    }

    /**
     * 根据订单ID删除订单,如果成功返回被删除的订单号码
     * @param $orderid
     * @return array|bool
     */
    public function deleteOrder( $orderid )
    {

        // 先看看订单状态
        $query = $this->link->prepare( "SELECT * FROM `order` WHERE id = ?" );
        $query->execute( [ $orderid ] );

        $order = $query->fetch( PDO::FETCH_ASSOC );

        if( $order['deleted'] == 1 ){
            $this->errno  = 6013;
            $this->errmsg = '订单已经被删除了';
            return false;
        }


        $query = $this->link->prepare( "UPDATE `order` set `deleted` = ? WHERE id = ? and `userid` = ?" );
        $query->execute( [ 1, $orderid, $this->userid ] );

        $rowCount = $query->rowCount();

        if( !$rowCount || $rowCount == 0 ){
            $this->errno  = 6013;
            $this->errmsg = '订单删除失败';
            return false;
        }

        return [ 'orderid' => $orderid ];

    }


}