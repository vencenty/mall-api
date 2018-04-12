<?php

namespace Core;

use Core\Library\System\Router;
use Core\Util\Response;
use Exception;

class Core
{
    protected static $classMap = [];

    // 构造build方法
    static function init()
    {
        $model = ( new static );
        $model->autoloadRegister();
        $model->loadRoute();
    }

    // 自动注册相关类
    protected function autoloadRegister()
    {
        spl_autoload_register( 'self::autoload' );
    }

    /**
     * 核心的类库自动载入,根据命名直接导入各类命名空间,如果已经加载过了,直接返回true,不多次进行加载
     * @param $className
     * @return bool
     */
    protected function autoload( $className )
    {
        // 加载LIB函数库里面的方法
        $class        = str_replace( '\\', '/', BASEDIR . '/' . $className . '.php' );
        $encryptClass = md5( $className );
        if( !isset( static::$classMap[$encryptClass] ) ){
            if( is_file( $class ) ){
                require $class;
                static::$classMap[$encryptClass] = $class;
            }else{
                return true;
            }
        }else{
            return true;
        }
    }

    // 加载路由
    protected function loadRoute()
    {
        $route = new Router();
        // 加载控制器
        $controller = ucfirst( $route->controller ) . 'Controller';
        $action     = $route->action;
        $module     = ucfirst( $route->module );


        // 获得类名
        $class = "\\App\\Controllers\\" . $module . "\\" . $controller;

        // 如果存在类,实例化并且调用它,如果不存在,
        if( class_exists( $class ) ){
            $object = new $class();
            if( method_exists( $class, $action ) ){
                $object->$action();
            }else{
                throw new Exception( '调用了不存在的方法' );
            }
        }else{
            throw new Exception( '类不存在' );
        }
    }

}