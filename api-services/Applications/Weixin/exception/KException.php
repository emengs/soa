<?php

namespace exception;

/**
 * 异常基础类
 * @author zhijiazou
 */
class KException extends \Exception
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