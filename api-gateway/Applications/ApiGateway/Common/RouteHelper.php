<?php

namespace Applications\ApiGateway;

include_once __DIR__ . '/CommonHelper.php';
include_once APP_ROOT . '/vendor/Logger/Logger/Log4p.php';
/**
 * 路由处理类
 *
 * @author sunnyzeng
 * @since 2017/03/10
 * @version v1.0.0
 */
class RouteHelper
{
    /**
     * 路由操作类
     * @var RouteHelper
     */
    public static $instance = null;
    /**
     * 到服务端的socket连接
     * @var array
     */
    protected static $connectCache = array();
    protected static $connectIdentify = '';
    /**
     * 当前连接
     * @var resource
     */
    protected $currConnect = null;
    private static $host = '';
    private static $port = '';

    /**
     * 发送数据和接收数据的超时时间  单位S
     * @var integer
     */
    const TIME_OUT = 30;
    const REQUEST_TYPE_HTTP = 'http';
    const REQUEST_TYPE_SOCKET = 'tcp';
    public static $requestType = 'tcp';
    private $debugTrace = array();

    /**
     * 构造函数
     */
    private function __construct($host, $port)
    {
    }

    /**
     * 实例化
     *
     * @param string $serviceHost 服务地址
     * @param string $servicePort 服务商品
     * @return \Applications\ApiGateway\RouteHelper
     */
    public static function getInstance($serviceHost, $servicePort = null)
    {
        if (empty($serviceHost))
        {
            throw new \Exception("The service's host is must not empty", 0);
        }
        $identify = md5($serviceHost . $servicePort);
        self::$host = $serviceHost;
        self::$port = $servicePort;
        self::$connectIdentify = $identify;
        self::$instance = new self($serviceHost, $servicePort);
        return self::$instance;
    }

    /**
     * 发送数据请求
     * @param string $service 服务名
     * @param string $action  方法名
     * @param array $params   参数名
     */
    public function request($service, $action, $params)
    {
        $response = array('code' => 1, 'msg' => 'ok', 'data' => '');
        switch (self::$requestType) {
            case self::REQUEST_TYPE_HTTP: 
                $response = $this->HttpRequest(self::$host,self::$port,$service,$action,$params);
                break;
            case self::REQUEST_TYPE_SOCKET:
                //$response = $this->SocketClient(self::$host,self::$port,$service,$action,$params);
                $response = $this->CallService($service, $action, $params);
                break;
            default:
                # code...
                break;
        }
        return $response;
    }
    /**
     * 调用接口服务
     */
    protected function CallService($service, $action, $params){
        if ($this->getCurrConnect())
        {
            if (is_string($params))
            {
                $params = json_decode($params, true);
            }

            $request = $this->sendData($service, $action, $params);
            if ($request)
            {
                $response = $this->recvData();
            }
            else
            {
                //记录服务请求失败日志
                $logData = array(
                    'request'   => array('data'=>array_merge($service, $action, $params),'file'=>__FILE__,'line'=>__LINE__),
                    'response'  => 'service lost connect',
                    'type'      => 'service');
                \Log4p::warn($logData);
            }

        }
        return $response;
    }
    /**
     * 发送数据给服务端
     * 
     * @param string $action        	
     * @param array $arguments        	
     */
    public function sendData($service, $action, $arguments)
    {
        $bin_data = JsonProtocol::encode(array(
            'class' => $service,
            'method' => $action,
            'param_array' => empty($arguments) ? array() : $arguments
        ));
        echo 'currentConnect:', var_dump($this->currConnect), '  sendData:', $bin_data, PHP_EOL;

        if (empty($this->currConnect))
        {
            $this->getCurrConnect();
        }
        if (fwrite($this->currConnect, $bin_data) !== strlen($bin_data))
        {
            //记录服务请求失败日志
            $logData = array(
                'request'   => array('data'=>$bin_data,'file'=>__FILE__,'line'=>__LINE__),
                'response'  => "The service's connect is break",
                'type'      => 'service');
            \Log4p::warn($logData);
            return false;
        }
        return true;
    }

    /**
     * 从服务端接收数据
     * 
     * @throws Exception
     */
    public function recvData()
    {
        $ret = fgets($this->currConnect);
        if ($ret === false)
        {
            $this->debugTrace[] = getDebugInfo();
            echo date('Y-m-d H:i:s'), "The service's connect is break";

            //记录服务请求失败日志
            $logData = array(
                'request'   => array('action'=>'recvData','file'=>__FILE__,'line'=>__LINE__),
                'response'  => "The service's connect is break",
                'type'      => 'service');
            \Log4p::warn($logData);

            return false;
        }

        //记录服务返回数据
        $logData = array(
            'request'   => array('action'=>'recvData'),
            'response'  => $ret,
            'type'      => 'info');
        \Log4p::info($logData);

        $response = JsonProtocol::decode($ret);
        return $response;
    }

    /**
     * 获取当前客户端连接
     */
    private function getCurrConnect()
    {
        $isConnected = true;
        if (empty($this->currConnect) || empty(self::$connectCache [self::$connectIdentify]) ||
          $this->isConnect(self::$connectCache [self::$connectIdentify]) == false)
        {
            $isConnected = $this->openConnection(self::$host, self::$port);
        }
        return $isConnected;
    }

    /**
     * 检测连接是否已断开
     * @return boolean
     */
    private function isConnect($connect)
    {
        if (empty($connect))
        {
            return false;
        }
        try
        {
            $msg = "hello\r\n";
            $result = @fwrite($connect, $msg, strlen($msg));
            if ($result)
            {
                @fread($connect, 1024);
                return true;
            }
            return false;
        }
        catch (\Exception $e)
        {
            //记录代码异常日志
            $logData = array(
                'request'   => array('action'=>'isConnect','file'=>__FILE__,'line'=>__LINE__),
                'response'  => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage()),
                'type'      => 'excepition');
            \Log4p::error($logData);
            return false;
        }
    }

    /**
     * 打开到服务端的连接
     *
     * @return void
     */
    protected function openConnection($host, $port)
    {
        $address = $host . ':' . $port;
        try
        {
            $this->currConnect = stream_socket_client($address, $err_no, $err_msg, STREAM_CLIENT_PERSISTENT);
            if (!$this->currConnect)
            {
                $this->setError(0, $err_msg);

                //记录代码异常日志
                $logData = array(
                    'request'   => array('action'=>'openConnection','host'=>$host,'port'=>$port,'file'=>__FILE__,'line'=>__LINE__),
                    'response'  => array('ex_file' => __FILE__, 'ex_line' => __LINE__, 'ex_msg' => $err_msg),
                    'type'      => 'excepition');
                \Log4p::error($logData);

                return false;
            }
            echo date('Y-m-d H:i:s'), '  identify:', self::$connectIdentify, '   host:', $host, '  port:', $port, PHP_EOL;

            self::$connectCache [self::$connectIdentify] = $this->currConnect;
            stream_set_blocking($this->currConnect, true);
            stream_set_timeout($this->currConnect, self::TIME_OUT);
            return true;
        }
        catch (\Exception $e)
        {
            //记录代码异常日志
            $logData = array(
                'request'   => array('action'=>'openConnection','host'=>$host,'port'=>$port,'file'=>__FILE__,'line'=>__LINE__),
                'response'  => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage()),
                'type'      => 'excepition');
            \Log4p::error($logData);
            return false;
        }
    }

    /**
     * 关闭所有缓存中的服务连接
     *
     * @return void
     */
    public static function closeConnection()
    {
        if (!empty(self::$connectCache))
        {
            foreach (self::$connectCache as $key => $connect)
            {
                fclose($connect);
                unset(self::$connectCache[$key]);
            }
        }
    }

    /**
     * 关闭当前服务化接口连接
     * @return void
     */
    protected function closeCurrConnect()
    {
        if (!empty($this->currConnect))
        {
            try
            {
                @fclose($this->currConnect);
                $this->currConnect = null;
            }
            catch (\Exception $e)
            {
                echo date('Y-m-d H:i:s'), ' --> error message:', $e->getMessage(), PHP_EOL;
                //记录代码异常日志
                $logData = array(
                    'request'   => array('action'=>'closeCurrConnect','file'=>__FILE__,'line'=>__LINE__),
                    'response'  => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage()),
                    'type'      => 'excepition');
                \Log4p::error($logData);
            }
        }
    }

    /**
     * Tcp通信
     * @return  void
     */
    public function SocketClient($host,$port,$service,$action,$params){
        try {
	          $addr = sprintf('tcp://%s:%s',$host,$port);
            $client = stream_socket_client($addr,$err_no,$err_msg,self::TIME_OUT);
            $response = array();
            if (!$client) {
                $logData = array(
                        'request'   => array('action'=>'SocketClient','file'=>__FILE__,'line'=>__LINE__),
                        'response'  => array('ex_file' => __FILE__, 'ex_line' => __LINE__, 'ex_msg' => $err_msg),
                        'type'      => 'excepition');
                    \Log4p::error($logData);
            }else{
                $sendData = JsonProtocol::encode(array(
                    'class'  => $service,
                    'method' => $action,
                    'param_array' => empty($params) ? array() : $params
                ));
                $result = fwrite($client,$sendData);
		            echo print_r($sendData),PHP_EOL;
                if ($result) {
                    $response = fgets($client);
                    fclose($client);
                }
            }
            return JsonProtocol::decode($response);
        } catch (Exception $e) {
            //记录代码异常日志
            $logData = array(
                'request'   => array('action' =>'SocketClient','file' =>__FILE__,'line' =>__LINE__),
                'response'  => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage()),
                'type'      => 'excepition');
            \Log4p::error($logData);
            return false;
        }
    }
    /**
     * Http数据请求
     */
    public function HttpRequest($host,$port=80,$service,$action,$params,$method='post'){
        $html = '';
        //$url  = sprintf('http://%s:%s/?service=%s&action=%s&param=%s',$host,$port,$service,$action,json_encode($params));
        $url  = sprintf('http://%s:%s/?service=%s&action=%s',$host,$port,$service,$action);
        // 写日志
        \Log4p::info(array('type' => 'HttpRequest', 'request' => array('action'=>'request','file'=>__FILE__,'line'=>__LINE__,'data'=>$url), 'response' => ''));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 30000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $options = array('param'=>json_encode($params));
        if (strtolower($method) == 'post' && !empty($options))
        {
            $postData = json_encode($options);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type:application/json',
              //'Content-Length: ' . strlen($postData)
            ));
        }

        try
        {
            $html = curl_exec($ch);
            if ($html === false)
            {
                \Log4p::error(array('type' => 'HttpRequest', 'request' => array('action'=>'request','file'=>__FILE__,'line'=>__LINE__,'data'=>$postData), 'response' => $html));
                return false;
            }
        }
        catch (\Exception $e)
        {
            \Log4p::error(array('type' => 'HttpRequest', 'request' => $postData, 
              'response' => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage())));
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        if (!empty($html)) {
            $html = JsonProtocol::decode($html,true);
        }
        return $html;
    }
    /**
     * 组装错误信息
     * @param int $code
     * @param string $msg
     * @return array
     */
    private function setError($code, $msg)
    {
        return [
          'code'    => empty($code) ? 0 : $code,
          'msg'     => empty($msg) ? 'fail' : $msg,
          'data'    => ''
        ];
    }

    public function __destruct()
    {
        $this->closeCurrConnect();
    }
}
/**
 * RPC 协议解析 相关
 * 协议格式为 [json字符串\n]
 *
 * @author walkor <worker-man@qq.com>
 *        
 */
class JsonProtocol
{
    /**
     * 从socket缓冲区中预读长度
     *
     * @var integer
     */
    const PRREAD_LENGTH = 87380;

    /**
     * 判断数据包是否接收完整
     *
     * @param string $bin_data        	
     * @param mixed $data        	
     * @return integer 0代表接收完毕，大于0代表还要接收数据
     */
    public static function dealInput($bin_data)
    {
        $bin_data_length = strlen($bin_data);
        // 判断最后一个字符是否为\n，\n代表一个数据包的结束
        if ($bin_data [$bin_data_length - 1] != "\n")
        {
            // 再读
            return self::PRREAD_LENGTH;
        }
        return 0;
    }

    /**
     * 将数据打包成Rpc协议数据
     *
     * @param mixed $data        	
     * @return string
     */
    public static function encode($data)
    {
        return json_encode($data) . "\n";
    }

    /**
     * 解析Rpc协议数据
     *
     * @param string $bin_data        	
     * @return mixed
     */
    public static function decode($bin_data)
    {
        return json_decode(trim($bin_data), true);
    }
}
/*
  $totalStart = microtime(true);
  for ($i=0; $i < 10000; $i++) {
  $start = microtime(true);
  $response = RouteHelper::getInstance('tcp://127.0.0.1','2015')->request('User','getInfoByUid',[rand(10,100000)]);
  $end = microtime(true);
  echo 'start time: ',$start,' end time:',$end,' interval: ',$end-$start,PHP_EOL;
  usleep(10);
  }
  $totalEnd = microtime(true);
  echo "------------------------------------------------------------------------------------------------>",PHP_EOL;
  echo 'run complete: start time: ',$totalStart,' end time:',$totalEnd,' interval: ',$totalEnd-$totalStart,PHP_EOL;
 */
?>