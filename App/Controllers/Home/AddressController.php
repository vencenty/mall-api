<?php

namespace App\Controllers\Home;

use App\Controllers\BaseController;
use App\Models\Home\Address;
use Core\Util\ErrMap;
use Core\Util\Request;
use Core\Util\Response;

class AddressController extends BaseController
{

    /**
     * 获取用户所有地址信息
     * @return bool
     */
    public function get()
    {
        $model = new Address();

        if( $data = $model->getUserAddress() ){
            echo Response::json( 0, '', $data );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }
        return true;
    }

    // 添加用户地址
    public function add()
    {
        // post参数
        $realname = Request::postQuery( 'realname' );
        $mobile   = Request::postQuery( 'mobile' );
        $province = Request::postQuery( 'province' );
        $city     = Request::postQuery( 'city' );
        $area     = Request::postQuery( 'area' );
        $address  = Request::postQuery( 'address' );
        $street   = Request::postQuery( 'street' );
        $postcode = Request::postQuery( 'postcode' );

        /**
         * 用户信息校验
         */
        if( !( $realname && $mobile && $province && $city && $area && $address && $street && $postcode ) ){
            echo Response::json( 3002, '信息不完整' );
            return false;
        }

        // 手机号码是否合法
        if( false == preg_match( '/^0?(13|14|15|17|18)[0-9]{9}$/', $mobile ) ){
            echo Response::json( 3011, '手机号码不合法' );
            return false;
        }

        $model = new Address();

        if( $model->addAddress( $realname, $mobile, $province, $city, $area, $address, $street, $postcode ) ){
            echo Response::json( 0, '' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;

    }


    public function delete()
    {
        $addressid = Request::getQuery( 'addressid' );

        if( !$addressid ){
            echo Response::json( 3014, '地址ID不能为空' );
        }

        $model = new Address();

        if( $data = $model->deleteAddress( $addressid ) ){
            echo Response::json( 0, '' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
        }

    }


    /**
     * 用户设置默认地址
     * @return bool
     */
    public function setDefault()
    {
        $addressid = Request::postQuery( 'addressid' );

        if( empty( $addressid ) ){
            echo Response::json( 3015, '设置默认地址失败' );
            return false;
        }

        $model = new Address();

        if( $model->setAddressAsDefault( $addressid ) ){
            echo Response::json( 0, '' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
        }
        return true;
    }



    /**
     * 根据地址获取用户所有地址信息
     * @return bool
     */
    public function getAddressInfo()
    {
        $addressid = Request::getQuery( 'addressid' );


        $model = new Address();

        if( $data = $model->getAddressById( $addressid ) ){
            echo Response::json( 0, '', $data );
            return false;
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

    }

    /**
     * 修改地址信息
     * @return bool
     */
    public function edit()
    {
        // post参数
        $realname  = Request::postQuery( 'realname' );
        $mobile    = Request::postQuery( 'mobile' );
        $province  = Request::postQuery( 'province' );
        $city      = Request::postQuery( 'city' );
        $area      = Request::postQuery( 'area' );
        $address   = Request::postQuery( 'address' );
        $street    = Request::postQuery( 'street' );
        $postcode  = Request::postQuery( 'postcode' );
        $addressid = Request::postQuery( 'addressid' );

        // 检查用户信息
        if( !( $addressid && $realname && $mobile && $province && $city && $area && $address && $street && $postcode ) ){
            echo Response::json( 3002, '信息不完整' );
            return false;
        }

        // 手机号码是否合法
        if( false == preg_match( '/^0?(13|14|15|17|18)[0-9]{9}$/', $mobile ) ){
            echo Response::json( 3011, '手机号码不合法' );
            return false;
        }

        $model = new Address();

        if( $model->editAddress( $addressid, $realname, $mobile, $province, $city, $area, $address, $street, $postcode ) ){
            echo Response::json( 0, '地址修改成功' );
        }else{
            echo Response::json( $model->errno, $model->errmsg );
            return false;
        }

        return true;
    }
}














