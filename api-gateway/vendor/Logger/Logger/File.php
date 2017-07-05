<?php
require_once (LOGER_ROOT . '/LogStorage.php');
require_once (LOGER_ROOT . '/Logger.php');
define('DS', DIRECTORY_SEPARATOR);
/**
 * 写文件日志类
 * @author sunnyzeng
 * @version 1.0
 * @created 03-五月-2017 15:09:34
 */
class File implements LogStorage
{
    protected $config = [
      'time_format' => ' c ',
      'file_size' => 2097152,
      'path' => './',
      'apart_level' => [],
    ];
    protected $writed = [];

    /**
     * 实例化类对象
     * @param array $config 配置参数
     */
    function __construct($config)
    {
        if (is_array($config))
        {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 保存日志内容到文件
     * @param  string $message [日志内容]
     * @return bool            [日志存储结果]
     */
    public function save($message, $type = 'log')
    {
        if (empty($message))
        {
            return true;
        }
        if (!is_array($message))
        {
            $message = json_decode($message, TRUE);
        }
        if (!is_array($message) && is_string("$message"))
        {
            $message = array('response' => $message);
        }
        $log_array = array();
        $cli = PHP_SAPI == 'cli' ? '_cli' : '';
        $isCli = PHP_SAPI == 'cli' ? true : false;
        $destination = $this->config['path'] . date('Ym') . DS . date('d') . $cli . '.log';
        try
        {

            $path = dirname($destination);
            !is_dir($path) && mkdir($path, 0755, true);

            //检测日志文件大小，超过配置大小则备份日志文件重新生成
            if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination))
            {
                rename($destination, dirname($destination) . DS . $_SERVER['REQUEST_TIME'] . '-' . basename($destination));
                $this->writed[$destination] = false;
            }

            if (empty($this->writed[$destination]) && !$isCli)
            {
                $log_array['time'] = date($this->config['time_format']);
                $log_array['server'] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '0.0.0.0';
                $log_array['remote'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
                $log_array['method'] = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
                $log_array['uri'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

                $this->writed[$destination] = true;
            }

            if ($isCli)
            {
                $log_array['time'] = date($this->config['time_format']);
            }
            $log_array ['error_type'] = $type;
            $log_array['ip'] = $this->getServerIp();
            $log_array['type'] = isset($message['type']) ? $message['type'] : "defalut";
            $log_array['reqdata'] = isset($message['request']) ? $message['request'] : "";
            $log_array['repdata'] = isset($message['response']) ? $message['response'] : "";
            $chars = array(':' => '=', '{' => '<', '}' => '>', '[' => '(', ']' => ')', "'" => ' ', '"' => ' ');
            $log_array = $this->recursiveReplace($log_array, $chars);
            $log_json = json_encode($log_array) . PHP_EOL;
            return error_log($log_json, 3, $destination);
        }
        catch (\Exception $e)
        {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * 递归替换字符
     * @param  array 要替换的数组
     * @param  array $strings 将数组的key值替换成value值
     */
    public function recursiveReplace($arr, $strings)
    {
        foreach ($arr as $key => $value)
        {
            if (is_array($value))
            {
                $arr[$key] = $this->recursiveReplace($value, $strings);
            }
            else
            {
                foreach ($strings as $source => $target)
                {
                    $value = str_replace($source, $target, $value);
                }
                $arr[$key] = $value;
            }
        }
        return $arr;
    }
    
        /**
     * 获取ip
     */
    public function getServerIp()
    {
        $ret = exec('/sbin/ifconfig eth0 | sed -n \'s/^ *.*addr:\\([0-9.]\\{7,\\}\\) .*$/\\1/p\'', $arr);
        $ret = isset($arr[0]) ? $arr[0] : 0;
        return $ret;
    }


    /**
     * 释放类对象
     */
    function __destruct()
    {
        
    }
}
?>