<?php
define('LOGER_ROOT', __DIR__);

require_once (LOGER_ROOT . '/Logger.php');
/**
 * 日志操作类
 * @author sunnyzeng
 * @version 1.0
 * @created 03-五月-2017 15:09:34
 */
class Log4p
{
    protected static $logger = null;
    protected static $conf = ['type' => 'file', 'config' => ['path' => APP_ROOT . '/Runtime/Logs/']];

    /**
     * 记录调试日志
     * @param message
     */
    public static function debug($message)
    {
        if (empty(self::$logger))
        {
            self::$logger = new Loggers(self::$conf);
        }
        return self::$logger->write($message, 'debug');
    }

    /**
     * 记录错误日志
     * @param message
     */
    public static function error($message)
    {
        if (empty(self::$logger))
        {
            self::$logger = new Loggers(self::$conf);
        }
        return self::$logger->write($message, 'error');
    }

    /**
     * 记录业务日志
     * @param message
     */
    public static function info($message)
    {
        if (empty(self::$logger))
        {
            self::$logger = new Loggers(self::$conf);
        }
        return self::$logger->write($message, 'info');
    }

    /**
     * 记录警告日志
     * @param message
     */
    public static function warn($message)
    {
        if (empty(self::$logger))
        {
            self::$logger = new Loggers(self::$conf);
        }
        return self::$logger->write($message, 'warn');
    }

    /**
     * 记录自定义日志
     * @param message
     * @param type
     */
    public static function log($message, $type)
    {
        if (empty(self::$logger))
        {
            self::$logger = new Loggers(self::$conf);
        }
        return self::$logger->write($message, $type);
    }
}
?>