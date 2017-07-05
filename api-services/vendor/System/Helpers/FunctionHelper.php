<?php
/**
 * 输出函数
 * @param type $val
 * @param type $return
 * @return string
 */
function dump($val, $return = false)
{
    $out = "<pre style=\"background: #000; color: #ccc; font: 16px 'Consolas'; text-align: left; width: 90%; padding: 5px\">\n";
    $out .= print_r($val, true);
    $out .= "</pre>\n";

    if ($return)
    {
        return $out;
    }

    echo $out;
}
/**
 * 载入配置函数
 * @param type $file_name
 * @param type $config_param
 * @return type
 */
function load_config($file_name='',$config_param='')
{
    $file_name = $file_name ? $file_name : 'Config';
    $file_name = ucfirst($file_name);
 
    $file_real = dirname(__DIR__)."/Config/{$file_name}.php";
    if (!is_file ( $file_real )) {
        return array('msg'=>'config not exists ');
    }
    include($file_real);
    if($config_param && isset($config_param))
    {
        return $config[$config_param];
    }
    else
    {
        return $config;
    }
}

function check_required($required,$data)
{
    foreach ($required as $kk=>$vv){
        if(!isset ($data[$kk]) || empty ($data[$kk]))
        {
            return array('success'=>FALSE,'msg'=>$vv);
        } 
    }
    return array('success'=>TRUE,'msg'=>'验证通过');
}
/**
 *@todo : 写错误日志
 */
function log_result($name,$data,$filename='error.log')
{
    
    $path = dirname(__DIR__).'/Logs/'.date('Y_m').$filename;

   // error_log(date('Y-m-d H:i:s')."--{$name}=>> ".var_export($data, true)."\r\n", 3, $path);//生成记录文件
}

/**
 * @todo : 生成随机数
 */
function rand_str($l = 6, $t = 'num')
{
    switch ($t)
    {
        case 'str':
            $str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            break;
        case 'upstr':
            $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            break;
         case 'lwstr':
            $str = "abcdefghijklmnopqrstuvwxyz0123456789";
            break;
        default :
            $str = "1234567890";
            break;
    }
     $str = "abcdefghijklmnopqrstuvwxyz0123456789";
    $randStr = str_shuffle($str);
    $rand = substr($randStr, 0, $l);
    return $rand;
}

?>