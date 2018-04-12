<?php

/**
 * @param $array  array 二维数组
 * @param string $keyword 要获得的键名
 * @return string   返回拼好后的字符串,例如 '1,2,3,4'
 */
function implodeSql( $array, $keyword = 'id' )
{
    return implode( array_column($array,$keyword), ',' );
}


// 获取转码之后的函数
function get_sms_param( array $paramArray ) {
    return addslashes( json_encode( $paramArray ) );
} 


// 生成随机盐值
function create_salt( $length = 32 )
{   
   $key = '';
   $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';  
    for($i=0;$i<$length;$i++)   
    {   
        $key .= $pattern{mt_rand(0,35)};    //生成php随机数   
    }   
    return $key;   
}    



// 二维数组排序
function gettop($input){
    $tmp=array('selftotal'=>'0');
    foreach($input as $key => $val){
        if($val['selftotal']>=$tmp['selftotal']){
            $tmp=$val;
            $tmp['key']=$key;
        }
    }

    return $tmp;
}

function gettopn($input,$num){
    $top=array();
    for($i=0;$i<$num;$i++){
        if(count($input) == 0){
            return $top;
        }
        $top[$i]=gettop($input);
        unset($input[$top[$i]['key']]);
    }
    return $top;
}


/**
 *
 * @param $data mixed
 * @param $id  integer
 * @return array
 */
function getTree($data,$id)
{

    function parttree($tree,$id){

        $tmp=[];
        $array=[];
        $tmp[]=array($id);
        $array[]=$id;
        $floor="";
        for($i=1;$i<1000;$i++){
            $array=getfloor($tree,$array);
            $echo="";
            foreach($array as $key=>$val){
                $echo.=$val.",";
            }
            $echo =trim($echo,",");
            $floor.=count($array)."~~~".$echo."</br>";

            $tmp[]=$array;
            if(count($array)>0){
                continue;
            }elseif(count($array)==pow(2,$i)&&$i==9){
                $forten="com";
            }else{
                $result=find($tree,$tmp[$i-1]);
                //print_r($tmp);
                break;
            }
        }
        $res=[];
        $res['echo']=$floor;
        $res['forten']=@$forten;
        $res['gid']=$result;
        $res['array']=$tmp;
        return $res;
    }

    function find($array,$ids){
        foreach($ids as $key=>$val){
            if($array[$val]['key1']==0){
                $tmp=$val;
                break;
            }else{

            }
            if($array[$val]['key2']==0){
                $tmp=$val;
                break;
            }else{

            }
        }
        unset($array);
        return $tmp;
    }
    function getfloor($array,$ids){
        $tmp=[];
        foreach($ids as $key=>$val){
            if($array[$val]['key1']>0){
                $tmp[]=$array[$val]['key1'];
            }else{

            }
            if($array[$val]['key2']>0){
                $tmp[]=$array[$val]['key2'];
            }else{

            }
        }
        unset($array);
        return $tmp;
    }

    return parttree($data,$id);
}
