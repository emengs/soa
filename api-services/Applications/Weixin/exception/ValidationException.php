<?php

namespace exception;

/**
 * 业务异常基础类
 * @author zhijiazou
 */
use exception\KException;

class ValidationException extends KException
{

    public function response()
    {
        $response = [
          'code' => 2,
          'msg' => '',
          'data' => ''
        ];
        $response['msg'] = $this->getMessage();
        return $response;
    }
}
?>