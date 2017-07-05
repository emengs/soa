<?php

namespace exception;

/**
 * 配置信息异常类
 * @author zhijiazou
 */
use exception\KException;

class ConfigException extends KException
{

    public function response()
    {
        $response = [
          'code' => 4,
          'msg' => '',
          'data' => ''
        ];
        $response['msg'] = $this->getMessage();
        return $response;
    }
}
?>