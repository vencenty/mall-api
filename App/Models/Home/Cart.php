<?php

namespace App\Models\Home;

use Core\Util\Database;
use PDO;

class Cart
{
    public $errno;
    public $errmsg;
    private $link;
    private $userid;

    public function __construct()
    {
        $this->link = Database::connect();
        $this->userid  = $_SESSION['user']['id'];
    }

    /**
     * 获取用户所有购物车信息,如果没有返回false
     * @return array|bool
     */
    public function getUserCart()
    {
        // 获取所有购物车信息
        $query = $this->link->prepare("SELECT * from cart WHERE userid = ? ");
        $query->execute( [$this->userid] );
        $carts = $query->fetchAll( PDO::FETCH_ASSOC );

        if( count($carts) == 0 ){
            $this->errno = 5006;
            $this->errmsg = '购物车无信息';
            return false;
        }

        $goodsids = implode(',',array_column( $carts, 'goodsid' ));

        $goods = $this->link->query("SELECT `id`,`title`,`thumb`,`realprice` FROM goods WHERE id in ($goodsids)")
            ->fetchAll( PDO::FETCH_ASSOC );

        $result = [];
        foreach( $goods as $key=> $value ){
            foreach( $carts as $k => $cart ){
                if( $cart['goodsid'] == $value['id'] ){
                    $result[$k] = [
                        'checked'   => false,
                        'goodsid'   => $cart['goodsid'],
                        'title'     => $value['title'],
                        'total'     => $cart['total'],
                        'price'     => $cart['goodsprice'],
                        'thumb'     => $value['thumb'],
                        'createtime'=> $cart['createtime']
                    ];
                }
            }
        }

        $result = array_values($result);

        return $result;
    }

    /**
     * 用户添加商品到购物车
     * @param int $goodsid
     * @param float $price
     * @param int $number
     * @return bool
     */
    public function addCart( $goodsid, $price, $number )
    {
        // 查看一下当前用户是否有当前商品的购物车信息
        $query = $this->link->prepare("SELECT * FROM cart WHERE userid = ? and goodsid = ? ");
        $query->execute( [$this->userid, $goodsid] );
        $count = $query->fetch();


        // 添加购物车
        if( !$count || count($count[0]) == 0 ){
            $query = $this->link->prepare("INSERT INTO cart(`userid`,`goodsid`,`goodsprice`,`total`,`createtime`) VALUES( ?, ?, ?, ?, ? )");
            $result = $query->execute( [$this->userid, $goodsid, $price,$number, date('Y-m-d H:i:s',time())] );
            if( !$result ){
                $this->errno = 5003;
                $this->errmsg = '购物车添加失败';
                return false;
            }
        } else {
            $query = $this->link->prepare("UPDATE cart set `total` = `total` + ? where goodsid = ? and userid = ?");
            $result  = $query->execute( [$number, $goodsid , $this->userid] );

            if( !$result ){
                $this->errno = 5003;
                $this->errmsg = '购物车添加失败';
                return false;
            }

        }

        return true;
    }


    /**
     * 删除购物车信息
     * @param int $goodsid
     * @return bool
     */
    public function deleteGoods( $goodsid )
    {
        $query = $this->link->prepare("DELETE FROM `cart` WHERE `userid` = ? and `goodsid` = ? ");
        $query->execute( [$this->userid, $goodsid] );

        $result = $query->rowCount();


        if( !$result || count($result) == 0 ){
            // 删除购物车数据失败
            $this->errmsg = '删除商品失败';
            $this->errno = 4002;
            return false;
        }

        return true;

    }


}

