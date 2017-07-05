<?php

namespace Common;

/**
 * 网络请求操作类
 *
 * @author sunnyzeng
 * @since 2017/03/13
 */
class HttpRequest
{

    public static function request($url, $param)
    {
        $response = [
          'code' => 1,
          'msg' => '',
          'data' => ''
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (!empty($param))
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }

        try
        {

            $result = curl_exec($ch);
            if ($result === false)
            {
                $response['code'] = 5;
                $response['msg'] = curl_error($ch);
            }
            else
            {
                $response['data'] = $result;
            }
        }
        catch (\Exception $e)
        {
            $response ['msg'] = $e->getMessage();
        }
        curl_close($ch);
        return $response;
    }
}
?>