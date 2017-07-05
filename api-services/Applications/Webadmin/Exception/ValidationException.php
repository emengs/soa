<?php

namespace Webadmin\Exception;

use Webadmin\Exception\KException;

/**
 * 业务异常基础类
 * @author zhijiazou
 */
class ValidationException extends KException
{

    public function __construct($message = "", $code = 4, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
?>