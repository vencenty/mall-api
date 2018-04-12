<?php


header('Access-Control-Allow-Origin:*');
session_start();
date_default_timezone_set('PRC');
// 定义接口根路径
define( "BASEDIR", __DIR__ );
define( "CORE", __DIR__ . '/Core/' );
define( "APP", __DIR__. '/App/');
define( "LIB", __DIR__ . '/Core/Library/' );
// 是否开启DEBUG模式
define( "APP_DEBUG", true );

if( APP_DEBUG == true ){
    ini_set('display_errors','On');
}else {
    ini_set('display_errors','Off');
}

// 加载助手函数库
include CORE . '/Common/helpers.php';
// 加载框架
include __DIR__ . '/Core/Core.php';

\Core\Core::init();
