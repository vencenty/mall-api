<?php

namespace App\Models\Home;

use Core\Util\Database;
use PDO;

class Bouns
{

    private $link;

    /*
     * rm-2zen83c2mci6syit6o.mysql.rds.aliyuncs.com
        mxg2
        Bijiao2@

     */

    public function __construct()
    {
        $this->link = Database::connect()->link;
        $this->link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }

    public function iswx( $order, $userid )
    {
        // global $_W,$_GPC;
        // 订单openid查找一行member表用户信息
        //$member = m('member')->getMember($order['openid'], true);
        $member = $this->link->query( "select * from `user` WHERE  `mid` = '{$userid}'" )
            ->fetch( PDO::FETCH_ASSOC );


        $sql = "select key1,key2,mid,gid,openid,`pid`,selftotal,grouptotal from `user` where openid<>''";
        unset( $result );
        // 所有用户信息
        $result = $this->link->query( $sql )->fetchAll( PDO::FETCH_ASSOC );


        foreach( $result as $val ){
            $members[ $val['mid'] ] = $val;
        }


        if( $member['gid'] == 0 ){
            unset( $result );
            $gid = $this->parttree( $members, $member['pid'] );
            $gid = $gid['gid'];
            if( $members[ $gid ]['key1'] == 0 ){
                //pdo_update('ewei_shop_member', array('key1'=>$member['id']), array('id' => $gid));
                $query = $this->link->prepare( "UPDATE `user` SET `key1` = ? where `mid` = ?" );
                $query->execute( [ $member['id'], $gid ] );


                $members[ $gid ]['key1'] = $member['id'];
            }else{
                //pdo_update('ewei_shop_member', array('key2'=>$member['id']), array('id' => $gid));
                $query = $this->link->prepare( "UPDATE `user` SET `key2` = ? where `mid`` = ?" );
                $query->execute( [ $member['id'], $gid ] );

                $members[ $gid ]['key2'] = $member['id'];
            }
            $members[ $member['id'] ]['gid'] = $gid;
            //pdo_update('ewei_shop_member', array('gid' => $gid ), array('id' => $member['id']));
            $query = $this->link->query( "UPDATE  `user` SET `gid` = ? where mid = ?" );
            $query->execute( [ $gid, $member['id'] ] );

        }
        if( $order['realprice'] == 6000 ){
            $level = 2;
        }elseif( $order['realprice'] == 3000 ){
            $level = 3;

        }elseif( $order['realprice'] == 1000 ){
            $level = 1;
        }
        $level1 = intval( $order['realprice'] );


        //pdo_update('ewei_shop_member', array('level' => $level , 'wxlevel' => $level1), array('id' => $member['id']));
        //$query = $this->link->prepare("UPDATE `ims_ewei_shop_member` SET level = ?,wxlevel = ? where id = ?");
        //$query->execute( [$level, $level1, $member['id']] );


        $this->ffbouns( $order, $members );

        // 找出跟他关联的个人网提的个人ID
        // member表里面所有信息,member要查找的用户ID
        $pids = $this->forpid( $members, $member );
        $gids = $this->forgid( $members, $member );
//		file_put_contents("/www/web/pt_chuangweisy_com/public_html/addons/ewei_shopv2/core/mobile/order/result.txt",print_r($pids,true),FILE_APPEND);
//		file_put_contents("/www/web/pt_chuangweisy_com/public_html/addons/ewei_shopv2/core/mobile/order/result.txt",print_r($gids,true),FILE_APPEND);
        $price = $order['realprice'];
        $inarr = "(";
        foreach( $pids as $val ){
            $inarr .= $val;
            $inarr .= ",";
        }
        $inarr = trim( $inarr, "," );
        $inarr .= ")";
//		file_put_contents("/www/web/pt_chuangweisy_com/public_html/addons/ewei_shopv2/core/mobile/order/result.txt",$inarr,FILE_APPEND);
//        pdo_query("UPDATE ".tablename('ewei_shop_member')." SET selftotal = selftotal + {$price} WHERE id IN {$inarr}");
        $this->link->query( "UPDATE `user` SET selftotal = selftotal + {$price} WHERE id IN {$inarr}" );

        $inarr = "(";

        // gid 所有团队网体 pids是个人网提
        foreach( $gids as $val ){
            $inarr .= $val;
            $inarr .= ",";
        }
        $inarr = trim( $inarr, "," );
        $inarr .= ")";
        //pdo_query("UPDATE ".tablename('ewei_shop_member')." SET grouptotal = grouptotal + {$price} WHERE id IN {$inarr}");
        $this->link->query( "UPDATE `user` SET grouptotal = grouptotal + {$price} WHERE id IN {$inarr}" );

        //file_put_contents("/www/web/pt_chuangweisy_com/public_html/addons/ewei_shopv2/core/mobile/order/result.txt",print_r(pdo_debug(),true),FILE_APPEND);
    }

    protected function ffbouns( $order, $members )
    {


        //$openid=$order['openid'];
        $bounprice = $order['realprice'];
        $member    = $this->link->query( "select * from `user` WHERE  `id` = '{$order["userid"]}'" )
            ->fetch( PDO::FETCH_ASSOC );


        $bouns['mid']       = $member['pid'];
        $bouns['orderid']   = $order['id'];
        $bouns['type']      = '0';
        $bouns['bouns']     = $bounprice * 0.15 * 0.9;
        $bouns['costprice'] = $bounprice * 0.15 * 0.1;
        $bouns['fromid']    = $member['mid'];
        $bouns['beizhu']    = "直推奖：{$bouns['bouns']}，直推消费积分：{$bouns['costprice']}，来源：{$bouns['fromid']}。";


        $bouns['year']  = date( 'Y' );
        $bouns['month'] = date( 'm' );
        $bouns['day']   = date( 'd' );


        $query = $this->link->prepare( "INSERT INTO `bouns`(`mid`,`orderid`,`type`,`bouns`,`costprice`,`fromid`,`beizhu`,`datey`,`datemon`,`dated`) VALUES(?,?,?,?,?,?,?,?,?,?)" );
        $query->execute( [
            $bouns['mid'],
            $bouns['orderid'],
            $bouns['type'],
            $bouns['bouns'],
            $bouns['costprice'],
            $bouns['fromid'],
            $bouns['beizhu'],
            $bouns['year'],
            $bouns['month'],
            $bouns['day']
        ] );

        unset( $bouns );


        $bouns['mid'] = $members[ $member['pid'] ]['pid'];
        //$bouns['openid']=$members[$bouns['mid']]['openid'];
        $bouns['orderid']   = $order['id'];
        $bouns['type']      = '0';
        $bouns['bouns']     = $bounprice * 0.2 * 0.9;
        $bouns['costprice'] = $bounprice * 0.2 * 0.1;
        $bouns['fromid']    = $member['mid'];
        $bouns['beizhu']    = "间推奖：{$bouns['bouns']}，间推消费积分：{$bouns['costprice']}，来源：{$bouns['fromid']}。";

        $bouns['year']  = date( 'Y' );
        $bouns['month'] = date( 'm' );
        $bouns['day']   = date( 'd' );


        $query = $this->link->prepare( "INSERT INTO `bouns`(`mid`,`orderid`,`type`,`bouns`,`costprice`,`fromid`,`beizhu`,`datey`,`datemon`,`dated`) VALUES(?,?,?,?,?,?,?,?,?,?)" );
        $query->execute( [
            $bouns['mid'],
            $bouns['orderid'],
            $bouns['type'],
            $bouns['bouns'],
            $bouns['costprice'],
            $bouns['fromid'],
            $bouns['beizhu'],
            $bouns['year'],
            $bouns['month'],
            $bouns['day']
        ] );

        //unset($bouns);

        $groupbouns = $this->group( $member, $members );
        $netbouns   = $this->selfnet( $member, $members );
        foreach( $netbouns as $key => $val ){
            //$bouns['openid']=$members[$val['id']]['openid'];
            $bouns['bounstype'] = '1';
            $bouns['mid']       = $val['id'];
            $bouns['orderid']   = $order['id'];
            $bouns['type']      = '0';
            $bouns['bouns']     = $bounprice * $val['bouns'] * 0.9;
            $bouns['costprice'] = $bounprice * $val['bouns'] * 0.1;
            $bouns['fromid']    = $member['mid'];
            $bouns['beizhu']    = "个人网体管理奖金：{$bouns['bouns']}，个人网体管理消费积分：{$bouns['costprice']}，来源：{$bouns['fromid']}。";

            $bouns['year']  = date( 'Y' );
            $bouns['month'] = date( 'm' );
            $bouns['day']   = date( 'd' );


            $query = $this->link->prepare( "INSERT INTO `bouns`(`bounstype`,`mid`,`orderid`,`type`,`bouns`,`costprice`,`fromid`,`beizhu`,`datey`,`datemon`,`dated`) VALUES(?,?,?,?,?,?,?,?,?,?,?)" );
            $query->execute( [
                $bouns['bounstype'],
                $bouns['mid'],
                $bouns['orderid'],
                $bouns['type'],
                $bouns['bouns'],
                $bouns['costprice'],
                $bouns['fromid'],
                $bouns['beizhu'],
                $bouns['year'],
                $bouns['month'],
                $bouns['day']
            ] );
        }

        foreach( $groupbouns as $key => $val ){
            $bouns['mid']       = $val['id'];
            $bouns['orderid']   = $order['id'];
            $bouns['type']      = '0';
            $bouns['bounstype'] = '2';
            $bouns['bouns']     = $bounprice * $val['bouns'] * 0.9;
            $bouns['costprice'] = $bounprice * $val['bouns'] * 0.1;
            $bouns['fromid']    = $member['mid'];
            $bouns['beizhu']    = "团队网体管理奖金：{$bouns['bouns']}，团队网体管理消费积分：{$bouns['costprice']}，来源：{$bouns['fromid']}。";

            $bouns['year']  = date( 'Y' );
            $bouns['month'] = date( 'm' );
            $bouns['day']   = date( 'd' );

            $query = $this->link->prepare( "INSERT INTO `bouns`(`bounstype`,`mid`,`orderid`,`type`,`bouns`,`costprice`,`fromid`,`beizhu`,`datey`,`datemon`,`dated`) VALUES(?,?,?,?,?,?,?,?,?,?,?)" );
            $query->execute( [
                $bouns['bounstype'],
                $bouns['mid'],
                $bouns['orderid'],
                $bouns['type'],
                $bouns['bouns'],
                $bouns['costprice'],
                $bouns['fromid'],
                $bouns['beizhu'],
                $bouns['year'],
                $bouns['month'],
                $bouns['day']
            ] );

        }
    }


    protected function group( $member, $members )
    {

        if( $member['gid'] ){
            $groupfid = $member['gid'];
        }


        $groupbouns = [];


        do{
            if( $members[ $groupfid ] > 0 ){
                if( $members[ $groupfid ]['grouptotal'] >= 4000000 && $members[ $groupfid ]['grouptotal'] < 8000000 && !isset( $groupbouns[0] ) ){
                    $groupbouns[0]          = [];
                    $groupbouns[0]['bouns'] = 0.05;
                    $groupbouns[0]['id']    = $groupfid;
                }
                if( $members[ $groupfid ]['grouptotal'] >= 8000000 && $members[ $groupfid ]['grouptotal'] < 16000000 ){
                    if( !isset( $groupbouns[0] ) ){
                        $groupbouns[0]          = [];
                        $groupbouns[0]['bouns'] = 0.05;
                        $groupbouns[0]['id']    = $groupfid;
                        $groupbouns[1]          = [];
                        $groupbouns[1]['bouns'] = 0.05;
                        $groupbouns[1]['id']    = $groupfid;
                    }else{
                        $groupbouns[1]          = [];
                        $groupbouns[1]['bouns'] = 0.05;
                        $groupbouns[1]['id']    = $groupfid;
                    }
                    break;
                }
            }else{
                $groupfid = 0;
            }

            $groupfid = $members[ $groupfid ]['gid'];
        } while( $groupfid > 0 );
        return $groupbouns;
    }

    protected function selfnet( $member, $members )
    {
        if( $member['pid'] ){
            $netfid = $member['pid'];
        }

        $netbouns = [];
        do{
            if( $members[ $netfid ] > 0 ){

                if( $members[ $netfid ]['selftotal'] >= 100000 && $members[ $netfid ]['selftotal'] < 500000 && !isset( $netbouns[0] ) ){
                    $netbouns[0]          = [];
                    $netbouns[0]['bouns'] = 0.05;
                    $netbouns[0]['id']    = $netfid;
                }
                if( $members[ $netfid ]['selftotal'] >= 500000 && $members[ $netfid ]['selftotal'] < 1000000 && !isset( $netbouns[1] ) ){

                    if( !isset( $netbouns[0] ) ){
                        $netbouns[0]          = [];
                        $netbouns[0]['bouns'] = 0.05;
                        $netbouns[0]['id']    = $netfid;
                        $netbouns[1]          = [];
                        $netbouns[1]['bouns'] = 0.05;
                        $netbouns[1]['id']    = $netfid;
                    }else{
                        $netbouns[1]          = [];
                        $netbouns[1]['bouns'] = 0.05;
                        $netbouns[1]['id']    = $netfid;
                    }
                }
                if( $members[ $netfid ]['selftotal'] >= 1000000 && $members[ $netfid ]['selftotal'] < 2000000 && !isset( $netbouns[2] ) ){
                    if( !isset( $netbouns[0] ) ){
                        $netbouns[0]          = [];
                        $netbouns[0]['bouns'] = 0.05;
                        $netbouns[0]['id']    = $netfid;
                        $netbouns[1]          = [];
                        $netbouns[1]['bouns'] = 0.05;
                        $netbouns[1]['id']    = $netfid;
                        $netbouns[2]          = [];
                        $netbouns[2]['bouns'] = 0.02;
                        $netbouns[2]['id']    = $netfid;
                    }elseif( !isset( $netbouns[1] ) ){
                        $netbouns[1]          = [];
                        $netbouns[1]['bouns'] = 0.05;
                        $netbouns[1]['id']    = $netfid;
                        $netbouns[2]          = [];
                        $netbouns[2]['bouns'] = 0.02;
                        $netbouns[2]['id']    = $netfid;
                    }else{
                        $netbouns[2]          = [];
                        $netbouns[2]['bouns'] = 0.02;
                        $netbouns[2]['id']    = $netfid;
                    }
                }
                if( $members[ $netfid ]['selftotal'] >= 2000000 && !isset( $netbouns[3] ) ){
                    if( !isset( $netbouns[0] ) ){
                        $netbouns[0]          = [];
                        $netbouns[0]['bouns'] = 0.05;
                        $netbouns[0]['id']    = $netfid;

                        $netbouns[1]          = [];
                        $netbouns[1]['bouns'] = 0.05;
                        $netbouns[1]['id']    = $netfid;

                        $netbouns[2]          = [];
                        $netbouns[2]['bouns'] = 0.02;
                        $netbouns[2]['id']    = $netfid;

                        $netbouns[3]          = [];
                        $netbouns[3]['bouns'] = 0.03;
                        $netbouns[3]['id']    = $netfid;
                    }elseif( !isset( $netbouns[1] ) ){
                        $netbouns[1]          = [];
                        $netbouns[1]['bouns'] = 0.05;
                        $netbouns[1]['id']    = $netfid;

                        $netbouns[2]          = [];
                        $netbouns[2]['bouns'] = 0.02;
                        $netbouns[2]['id']    = $netfid;

                        $netbouns[3]          = [];
                        $netbouns[3]['bouns'] = 0.03;
                        $netbouns[3]['id']    = $netfid;

                    }elseif( !isset( $netbouns[2] ) ){

                        $netbouns[2]          = [];
                        $netbouns[2]['bouns'] = 0.02;
                        $netbouns[2]['id']    = $netfid;

                        $netbouns[3]          = [];
                        $netbouns[3]['bouns'] = 0.03;
                        $netbouns[3]['id']    = $netfid;
                    }else{

                        $netbouns[3]          = [];
                        $netbouns[3]['bouns'] = 0.03;
                        $netbouns[3]['id']    = $netfid;
                    }
                    break;
                }
            }else{
                $netfid = 0;
            }
            $netfid = $members[ $netfid ]['pid'];
        } while( $netfid > 0 );

        return $netbouns;

    }

    protected function parttree( $tree, $id )
    {

        //	print_r($tree);
        //	echo "</br>";
        $tmp     = [];
        $array   = [];
        $tmp[]   = array( $id );
        $array[] = $id;
        $floor   = "";
        for( $i = 1; $i < 1000; $i ++ ){
            $array = $this->getfloor( $tree, $array );
            $echo  = "";
            foreach( $array as $key => $val ){
                $echo .= $val . ",";
            }
            $echo  = trim( $echo, "," );
            $floor .= count( $array ) . "~~~" . $echo . "</br>";

            $tmp[] = $array;
            if( count( $array ) == pow( 2, $i ) && $i != 9 ){
                continue;
            }elseif( count( $array ) == pow( 2, $i ) && $i == 9 ){
                $forten = "com";
            }else{
                $result = $this->find( $tree, $tmp[ $i - 1 ] );
                //print_r($tmp);
                break;
            }
        }
        $res = [];
//		$res['echo']=$floor;
        $res['forten'] = $forten;
        $res['gid']    = $result;
        //	$res['array']=$tmp;
        return $res;
    }

    protected function find( $array, $ids )
    {
        foreach( $ids as $key => $val ){
            if( $array[ $val ]['key1'] == 0 ){
                $tmp = $val;
                break;
            }else{

            }
            if( $array[ $val ]['key2'] == 0 ){
                $tmp = $val;
                break;
            }else{

            }
        }
        unset( $array );
        return $tmp;
    }

    protected function getfloor( $array, $ids )
    {
        $tmp = [];
        foreach( $ids as $key => $val ){
            if( $array[ $val ]['key1'] > 0 ){
                $tmp[] = $array[ $val ]['key1'];
            }else{

            }
            if( $array[ $val ]['key2'] > 0 ){
                $tmp[] = $array[ $val ]['key2'];
            }else{

            }
        }
        unset( $array );
        return $tmp;
    }

    protected function forpid( $members, $member )
    {
        $id  = $member['mid'];
        $tmp = [];
        do{
            $id = $members[ $id ]['pid'];
            if( $members[ $id ]['pid'] == 0 ){
                return $tmp;
            }
            $tmp[] = $id;
        } while( 1 );
        return $tmp;
    }

    //
    protected function forgid( $members, $member )
    {
        $id  = $member['mid'];
        $tmp = [];
        do{
            $id = $members[ $id ]['gid'];
            if( $members[ $id ]['gid'] == 0 ){
                return $tmp;
            }
            $tmp[] = $id;
        } while( 1 );

    }
}