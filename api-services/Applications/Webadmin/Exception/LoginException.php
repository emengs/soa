<?php

namespace Webadmin\Exception;

use Webadmin\Exception\KException;

/**
 * 登陆异常类
 * @author zhijiazou
 */
class LoginException extends KException
{

    public function __construct($message = "", $code = 3, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
?>