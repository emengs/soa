<?php
require_once (LOGER_ROOT . '/LogStorage.php');
require_once (LOGER_ROOT . '/Logger.php');
/**
 * Redis 方式存储日志
 * @author sunnyzeng
 * @version 1.0
 * @created 03-五月-2017 15:09:34
 */
class Redis implements LogStorage
{

    function __construct()
    {
        
    }

    /**
     * 保存日志内容到文件
     * @param  string $message [日志内容]
     * @return bool            [日志存储结果]
     */
    public function save(string $message)
    {
        
    }

    function __destruct()
    {
        
    }
}
?>