<?php

namespace Core\Util;

class Request
{
    // 获取post参数
    static function postQuery( $request, $default = null )
    {
        return isset( $_POST[$request] ) ? $_POST[$request] : $default;
    }

    // 返回get参数
    static function getQuery( $request, $default = null )
    {
        return isset( $_GET[$request] ) ? $_GET[$request] : $default;
    }

}