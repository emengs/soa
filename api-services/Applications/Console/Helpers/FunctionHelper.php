<?php


/**
 * 载入配置函数
 * @param type $file_name
 * @param type $config_param
 * @return type
 */
function console_load_config($file_name='',$config_param='')
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
/**
 *@desc：用来过滤字符串和字符串数组，防止被挂马和sql注入
 *@param $data，待过滤的字符串或字符串数组，
 *@param $force为true，忽略get_magic_quotes_gpc
 **/
function weixin_in($data, $force = false, $htmlspecialchar = true) {
	if (is_string ( $data )) {
		if ($htmlspecialchar == true) {
			$data = trim ( htmlspecialchars ( $data ) ); // 防止被挂马，跨站攻击
		}
		if (($force == true) || (! get_magic_quotes_gpc ())) {
			$data = addslashes ( $data ); // 防止sql注入
		}
		return $data;
	} else if (is_array ( $data )) { // 如果是数组采用递归过滤
		foreach ( $data as $key => $value ) {
			$data [$key] = in ( $value, $force );
		}
		return $data;
	} else {
		return $data;
	}
}

// 用来还原字符串和字符串数组，把已经转义的字符还原回来
function weixin_out($data) {
	if (is_string ( $data )) {
		return $data = stripslashes ( $data );
	} else if (is_array ( $data )) { // 如果是数组采用递归过滤
		foreach ( $data as $key => $value ) {
			$data [$key] = out ( $value );
		}
		return $data;
	} else {
		return $data;
	}
}

// 获取客户端IP地址
function weixin_get_client_ip() {
	$ip = '';
	if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
		$ip = getenv ( "HTTP_X_FORWARDED_FOR" );
	else if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
		$ip = getenv ( "HTTP_CLIENT_IP" );
	else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
		$ip = getenv ( "REMOTE_ADDR" );
	else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
		$ip = $_SERVER ['REMOTE_ADDR'];
	else
		$ip = "unknown";
	return $ip;
}

function weixin_check_mobile($num = '')
{
    if (preg_match("/^1[34578]\d{9}$/", $num))
    {
        return TRUE;
    }

    return FALSE;
}
/**
 * 验证必填
 * @param type $data
 * @param type $params
 * @return type
 */
function weixin_check_need($data,$params)
{
    $return = array();
    foreach ($data as $key=>$val)
    {
        if(!isset($params[$key]) || empty($params[$key]))
        {
           return  array('code'=>4,'msg'=>$val);
        }
        $return[$key] = $params[$key];
    }
    return $return;
}
?>