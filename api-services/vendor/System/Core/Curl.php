<?php
namespace System\Core;

/**
 *  模拟GET/POST请求
 * @author guibinYu
 */
class Curl {
    /**
     * 执行get请求
     * @param type $url
     * @param type $params
     * @return type
     */
    public function get($url, $params=array()) {
        $url = strstr($url, "?") ? $url."&".http_build_query($params) : $url."?".http_build_query($params);
		$ch = curl_init ();
		curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//获取的信息以文件流的形式返回，而不是直接输出
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000 );//cURL允许执行的最长毫秒数
		$response = curl_exec ( $ch );
        $status = curl_getinfo($ch);
        $error = curl_error ( $ch );
        curl_close ( $ch );
        return $response;
	}
    /**
     * 执行post请求
     * @param type $url
     * @param type $params
     * @return type
     */
	public function post($url, $params=array()) {
        $params = json_encode($params);
		$ch = curl_init ();
        if (stripos($url, "https://") !== FALSE) 
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
		curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//获取的信息以文件流的形式返回，而不是直接输出
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000 );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
                'Content-Type:application/json',
                'Content-Length: ' . strlen ( $params ) 
        ) );
        $response = curl_exec ( $ch );
//        $status = curl_getinfo($ch);
//        $error = curl_error ( $ch );
        curl_close ( $ch );
        return $response;
	}
    
}