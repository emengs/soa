<?php

namespace Applications\ApiGateway\Common;

include_once dirname(__DIR__) . '/Config/AppSetting.php';
include_once APP_ROOT . '/vendor/Logger/Logger/Log4p.php';
use Applications\ApiGateway\Config\AppSetting;

/**
 * Consul客户端连接操作工具类
 *
 * @author SunnyZeng
 * @since v1.0.0
 */
class ConsulHelper
{
    const SERVICES_HEALTH_API = '/v1/health/service/'; //查询服务接口地址 包含健康状态
    /**
     * 注册服务接口地址
     * @var string
     */
    const REGISTER_API = '/v1/agent/service/register';
    /**
     * 查询服务接口地址
     * @var string
     */
    const SERVICES_API = '/v1/catalog/service/';

    /**
     * 服务注册中心主机地址
     * @var string
     */
    public $serverHost = '';
    /**
     * 服务注册中心主机端口号
     * @var int
     */
    public $port = 8500;
    
    /**
     * 类的构造函数
     * @param string $host 服务中心主机地址
     */
    public function __construct($host = NULL, $port = NULL)
    {
        if (!empty($host))
        {
            $this->serverHost = $host;
            $this->port = $port;
        }
        else
        {
            $setting = AppSetting::$Consul;
            $this->serverHost = $setting['host'];
            $this->port = $setting['port'];
        }
    }

    /**
     * 注册服务
     * 
     * @param string $serviceName 服务名称
     * @param string $serviceId 服务ID
     * @param string $serviceHost 服务地址
     * @param string $servicePort 服务端口号
     * @param string $checkScript 服务状态检测脚本
     * @return boolean
     */
    public function registerService($serviceId, $serviceName, $serviceHost, $servicePort, $checkScript)
    {
        $serverUrl = sprintf('http://%s:%s%s', $this->serverHost, $this->port, self::REGISTER_API);
        $serviceInfo = [
          'ID' => $serviceId,
          'Name' => $serviceName,
          'Tags' => [],
          'Address' => $serviceHost,
          'Port' => $servicePort,
          'EnableTagOverride' => true
        ];
        if (!empty($checkScript))
        {
            $serviceInfo['Check'] = [
            "DeregisterCriticalServiceAfter" => "90m",
            "Script" => $checkScript,
            "HTTP" => "",
            "Interval" => "10s",
            "TTL" => "15s"
        ];
        }
        $response = $this->request('POST', $serverUrl, $serviceInfo);
        $serviceInfo['serverUrl'] = $serverUrl;
        \Log4p::info(array(
            'type'      => 'Consul', 
            'request'   => array('action'=>'registerService','file'=>__FILE__,'line'=>__LINE__,'data'=> $serviceInfo), 
            'response'  => $response));
        return empty($response) ? : false;
    }

    /**
     * 通过服务名获取服务信息
     * 
     * @param string $serviceName 服务名称
     * @return string
     */
    public function getServiceInfo($serviceName)
    {
        $serverUrl = sprintf('http://%s:%s%s%s', $this->serverHost, $this->port, self::SERVICES_API, $serviceName);
        $response = $this->request('GET', $serverUrl);
        return empty($response) ? '' : $response;
    }
    
    /**
     * 通过服务名获取健康的服务
     * 
     * @param string $serviceName 服务名称
     * @return string
     */
    public function getHealthServiceInfo($serviceName)
    {
        $serverUrl = sprintf('http://%s:%s%s%s', $this->serverHost, $this->port, self::SERVICES_HEALTH_API, $serviceName);
        $response = $this->request('GET', $serverUrl);
        if (empty($response))
        {
            return '';
        }
        if (!is_array($response))
        {
            $response = json_decode($response, TRUE);
        }
        $services = array();
        foreach ($response as $value)
        {
            $service = $value['Service'];
            $service_id = $service['ID'];
            $checks = $value['Checks'];
            foreach ($checks as $check)
            {
                $check_id = "service:$service_id";
                if ($check_id == $check['CheckID'] && $check['Status'] == 'passing')
                {
                    $services[] = $service;
                }
            }
        }
        \Log4p::info(array(
            'type'      => 'Consul', 
            'request'   => array('action'=>'getHealthServiceInfo','file'=>__FILE__,'line'=>__LINE__,'data'=> $serviceName), 
            'response'  => $response));
        return $services;
    }

    /**
     * 发起http请求
     * @param sting $url
     * @param array $options
     * @return string
     */
    private function request($method = 'get', $url, $options = array())
    {
        $html = '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (strtolower($method) == 'post' && !empty($options))
        {
            $postData = json_encode($options);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type:application/json',
              'Content-Length: ' . strlen($postData)
            ));
        }

        try
        {
            $html = curl_exec($ch);
            if ($html === false)
            {
                \Log4p::error(array('type' => 'Consul', 'request' => array('action'=>'request','file'=>__FILE__,'line'=>__LINE__,'data'=>$postData), 'response' => $ch));
                return false;
            }
        }
        catch (\Exception $e)
        {
            \Log4p::error(array('type' => 'Consul', 'request' => $postData, 
              'response' => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage())));
            return false;
        }
        curl_close($ch);
        return $html;
    }
}