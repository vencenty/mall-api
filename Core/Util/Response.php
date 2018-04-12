<?php
namespace Core\Util;

class Response
{

    static function json( $errno = 0, $errmsg = '', $data = [] )
    {
        $result     = [
            'errno'     => $errno,
            'errmsg'    => $errmsg,
        ];

        if( !empty($data) ){
            $result['data']     = $data;
        }
        return json_encode( $result );
    }
}