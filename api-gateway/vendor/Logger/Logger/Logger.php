<?php
require_once (LOGER_ROOT . '/LogStorage.php');
/**
 * 日志操作基类
 * @author sunnyzeng
 * @version 1.0
 * @created 03-五月-2017 15:09:34
 */
class Loggers
{
    /**
     * 日志组件配置
     */
    public $config = ['type' => 'file', 'config' => []];
    /**
     * 日志存储对象
     * @var LogStorage
     */
    private $storageHandle = null;

    // 日志存储
    const STORAGE_FILE = 'file';
    const STORAGE_REDIS = 'redis';
    const STORAGE_SOCKET = 'socket';
    // 日志级别
    const TYPE_DEBUG = 'debug';
    const TYPE_ERROR = 'error';
    const TYPE_WARN = 'warn';
    const TYPE_INFO = 'info';
    const TYPE_LOG = 'log';
    const TYPE_NOTICE = 'notice';
    const TYPE_SQL = 'sql';

    /**
     * 初始化对象
     * @param array $conf 日志组件配置参数
     */
    function __construct($conf = [])
    {
        if (is_array($conf))
        {
            $this->config = array_merge($this->config, $conf);
        }
        if (empty($this->config['config']))
        {
            throw new Exception("Error Processing Request", 1);
        }
        $this->loadStorage();
    }

    /**
     * 初始化日志存储器
     * @return [type] [description]
     */
    protected function loadStorage()
    {
        if (isset($this->config['type']))
        {
            $conf = $this->config['config'];
            switch ($this->config['type'])
            {
                case self::STORAGE_FILE:
                    require_once LOGER_ROOT . '/File.php';
                    $this->storageHandle = new File($conf);
                    break;
                case self::STORAGE_REDIS:
                    require_once LOGER_ROOT . '/Redis.php';
                    $this->storageHandle = new Redis($conf);
                    break;
                case self::STORAGE_SOCKET:
                    require_once LOGER_ROOT . '/Socket.php';
                    $this->storageHandle = new Socket($conf);
                    break;
                default:
                    # code...
                    break;
            }
        }
    }

    /**
     * 
     * @param message    日志内容
     * @param type    日志类型
     */
    public function write($message, $type = "log")
    {
        if (isset($this->storageHandle) && $this->storageHandle instanceof \LogStorage)
        {
            return $this->storageHandle->save($message, $type);
        }
        return false;
    }

    function __destruct()
    {
        $this->storageHandle = null;
    }
}
?>