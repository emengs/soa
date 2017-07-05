<?php
include_once dirname(__DIR__) . '/Config/ServiceSetting_'.ENV.'.php';

/**
 * Consul客户端连接操作工具类
 *
 * @author SunnyZeng
 * @since v1.0.0
 */
class ConsulHelper
{
    const REGISTER_API = '/v1/agent/service/register'; //注册服务接口地址
    const SERVICES_API = '/v1/catalog/service/'; //查询服务接口地址
    const SERVICES_HEALTH_API = '/v1/health/service/'; //查询服务接口地址 包含健康状态
    const SERVICES_DEREGISTER = '/v1/agent/service/deregister/'; //服务注销

    public $consul_host = ''; //服务注册中心主机地址
    public $consul_port = 8500; //服务注册中心主机端口号
    public $services = []; //服务配置项

    /**
     * 类的构造函数
     * @param array $config 服务注册配置
     */

    public function __construct($config = [])
    {
        if (!empty($config))
        {
            $this->consul_host = $config['consul_host'];
            $this->consul_port = $config['consul_port'];
            $this->services = $config['services'];
        }
        else
        {
            $setting = \AppSetting::$Consul;
            $this->consul_host = $setting['consul_host'];
            $this->consul_port = $setting['consul_port'];
            $this->services = $setting['services'];
        }
    }

    /**
     * 注册服务
     * @return boolean
     */
    public function registerService()
    {
        $services = $this->services;
        if (!empty($services) && is_array($services))
        {
            foreach ($services as $items)
            {


                $service_host = isset($items['service_host']) ? $items['service_host'] : $this->getServerIp();
                $service_port = isset($items['service_port']) ? $items['service_port'] : $this->consul_port;

                $serverUrl = sprintf('http://%s:%s%s', $this->consul_host, $this->consul_port, self::REGISTER_API);
                echo $serverUrl, PHP_EOL;
                $serviceInfo = [
                    'ID'    => $items['id'],
                    'Name'  => $items['name'],
                    'Tags'  => isset($items['tags']) ? $items['tags'] : [],
                    'Address' => $service_host,
                    'Port'  => $service_port,
                    'EnableTagOverride' => true
                ];
                //暂时支持tcp，若有需要可调整
                if (isset($items['tcp']) && $items['tcp'])
                {
                    $serviceInfo['Check'] = [
                        "DeregisterCriticalServiceAfter" => "10m", //check失败后10分钟删除本服务
                        "TCP"       => "$service_host:$service_port",
                        "Interval"  => "10s",
                        "status"    => "passing"
                    ];
                }else if (isset($items['http']) && $items['http']) {
                    $serviceInfo['Check'] = [
                        "DeregisterCriticalServiceAfter" => "10m", //check失败后10分钟删除本服务
                        "HTTP"      => isset($items['http']) ? $items['http'] : '',
                        "Interval"  => "10s",
                        "status"    => "passing"
                    ];
                }
                //服务注销
                $deregister_url = sprintf('http://%s:%s%s%s', $this->consul_host, $this->consul_port, self::SERVICES_DEREGISTER, $items['id']);
                $deregister_response = $this->request('get', $deregister_url);
                echo $deregister_response, PHP_EOL;
                //服务注册
                $response = $this->request('POST', $serverUrl, $serviceInfo);
                echo $response, PHP_EOL;
            }
        }
    }

    /**
     * 通过服务名获取服务信息
     *
     * @param string $serviceName 服务名称
     * @return string
     */
    public function getServiceInfo($serviceName)
    {
        $serverUrl = sprintf('http://%s:%s%s%s', $this->consul_host, $this->consul_port, self::SERVICES_API, $serviceName);
        $response = $this->request('GET', $serverUrl);
        if (empty($response))
        {
            return '';
        }
        return $response;
    }

    /**
     * 通过服务名获取健康的服务
     *
     * @param string $serviceName 服务名称
     * @return string
     */
    public function getHealthServiceInfo($serviceName)
    {
        $serverUrl = sprintf('http://%s:%s%s%s', $this->consul_host, $this->consul_port, self::SERVICES_HEALTH_API, $serviceName);
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
            $checks = $value['Checks'];
            //------兼容没做健康监测的  ----------
//            foreach ($checks as $check)
//            {
//                if ($check['Status'] == 'passing')
//                {
//                    $services[] = $service;
//                }
//                continue;
//            }

            //--------必须设置健康监测并且健康的服务-------------
            $service_id = $service['ID'];
            foreach ($checks as $check)
            {
                $check_id = "service:$service_id";
                if ($check_id == $check['CheckID'] && $check['Status'] == 'passing')
                {
                    $services[] = $service;
                    continue;
                }
            }
        }
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (strtolower($method) == 'post' && !empty($options))
        {
            $postData = json_encode($options);
            curl_setopt($ch, CURLOPT_POST, true);
            //curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json',
                'Content-Length: ' . strlen($postData)
            ));
        }

        try
        {
            if (curl_exec($ch) === false)
            {
                echo 'Curl error: ' . curl_error($ch);
                return false;
            }
            else
            {
                $html = curl_exec($ch);
            }
        }
        catch (\Exception $e)
        {
            echo $e->getMessage();
            return false;
        }
        curl_close($ch);
        return $html;
    }

    /**
     * 获取服务器ip地址
     */
    public function getServerIp(){
        $ss = exec('/sbin/ifconfig eth0 | sed -n \'s/^ *.*addr:\\([0-9.]\\{7,\\}\\) .*$/\\1/p\'',$arr);
        $ret = isset($arr[0])?$arr[0]:0;
        return $ret;
    }
}