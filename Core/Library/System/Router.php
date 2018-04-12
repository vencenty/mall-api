<?php

namespace Core\Library\System;

class Router
{
    public $module;
    public $controller;
    public $action;

    // 加载路由
    public function __construct()
    {
        $requestPath = trim( current( array_keys( $_GET ) ), '/' );

        $requestPathArr = explode( '/', $requestPath );

        // 卸载掉数组第一个元素,第一个元素为用户restfulAPI
        unset( $_GET[current( array_keys( $_GET ) )] );

        $this->module     = isset( $requestPathArr[0] ) ? $requestPathArr[0] : null;
        $this->controller = isset( $requestPathArr[1] ) ? $requestPathArr[1] : null;
        $this->action     = isset( $requestPathArr[2] ) ? $requestPathArr[2] : null;
    
        // 字母数字下划线
        if( !( $this->module && $this->controller && $this->action ) ){
            throw new \Exception( '模块，控制器,方法都不能为空' );
        }


    }
}
