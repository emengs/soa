<?php
require_once (LOGER_ROOT . '/Logger.php');
/**
 * 日志存储接口
 * @author sunnyzeng
 * @version 1.0
 * @created 03-五月-2017 15:09:34
 */
interface LogStorage
{

    /**
     * 日志存储操作
     * @param message
     */
    public function save($message);
}
?>