<?php

namespace Webadmin\Exception;

use Webadmin\Exception\KException;

/**
 * 配置信息异常类
 * @author zhijiazou
 */
class ConfigException extends KException
{

    public function __construct($message = "", $code = 2, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
?>