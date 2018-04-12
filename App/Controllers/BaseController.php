<?php
namespace App\Controllers;

use Core\Util\Response;

class BaseController
{
    public function __construct()
    {
        if( empty($_SESSION['user']) ){
            echo Response::json('1001','请登录后重试',[
                'callbackurl'=>'http://cx.qhsc1319.com/mall/public/preview/login'
            ]);
            die;
        }
    }
}