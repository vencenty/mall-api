<?php

namespace App\Extend;

use TopClient;
use AlibabaAliqinFcSmsNumSendRequest;

/**
 * 短信验证码发送类
 * Class SMS
 * @package App\Extend
 */
class SMS
{
    protected $client;
    protected $request;
    protected $response;


    /**
     *
     * @param $mobile integer 手机号码
     * @param $message string 发送的json短信数据
     * @param $templateCode  string 短信模板编号
     * @param $SmsFreeSignName string 短信签名
     * @return mixed|\ResultSet|\SimpleXMLElement
     */
    static function send( $mobile, string $message, $templateCode, $SmsFreeSignName )
    {
        require_once LIB . 'ThirdParty/SMS/TopSdk.php';
        $client            = new TopClient;
        $client->appkey    = '23799575';
        $client->secretKey = '0661436753fd6ec3d1ddb3a7d70d5d59';
        $request           = new AlibabaAliqinFcSmsNumSendRequest;
        $request->setSmsType( "normal" );
        $request->setSmsFreeSignName( $SmsFreeSignName );
        $request->setSmsParam( $message );
        $request->setRecNum( $mobile );
        $request->setSmsTemplateCode( $templateCode );
        $response = $client->execute( $request );
        return $response;
    }



}
