<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use \Applications\ApiGateway\RouteHelper;
use \Applications\ApiGateway\Common\ConsulHelper;

include_once __DIR__ . '/Common/ConsulHelper.php';
include_once __DIR__ . '/Common/RouteHelper.php';
include_once __DIR__ . '/Common/ErrorHandle.php';
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        $response = [
          'code'  => 0,
          'msg'   => '参数错误(50005)',
          'data'  => ''
        ];
        $request = array_merge($message ['get'], $message ['post']);
        print_r($message);
        if (empty($request ['service']) || empty($request ['action']))
        {
            Gateway::sendToClient($client_id, json_encode($response));
            //记录服务请求失败日志
            $logData = array(
              'request'   => array('data'=>$request,'file'=>__FILE__,'line'=>__LINE__),
              'response'  => $response,
              'type'      => 'gateway');
            \Log4p::warn($logData);
            return;
        }

        $service = $request ['service'];
        $action = $request ['action'];
        $params = empty($request ['param']) ? array() : $request ['param'];
        if (!is_array($params))
        {
            $t = json_decode($params, true);
            $params = is_array($t) ? $t : array();
        }
        $temp = $request;
        unset($temp['service'], $temp['action'], $temp['param']);
        $params = array_merge($params, $temp);
        if (!empty($service))
        {
            $services = self::getService($service);
            print_r($services);
            if (!empty($services) && is_array($services))
            {
                $host = $services ['host'];
                $port = $services ['port'];
                $serviceArr = explode('.', $service);
                $service = array_pop($serviceArr); // 提取服务名称
                if (!empty($host) && !empty($action))
                {
                    try
                    {
                        $response = RouteHelper::getInstance($host, $port)->request($service, $action, $params);
                        echo '--------------------------------------------------------------------->', PHP_EOL;
                        echo date('Y-m-d H:i:s'), ' response:', print_r($response), PHP_EOL;
                    }
                    catch (\Exception $e)
                    {
                        $response = [
                          'code' => 0,
                          'msg'  => $e->getMessage(),
                          'data' => ''
                        ];
                        //记录服务请求失败日志
                        $logData = array(
                          'request'   => array('data'=>$request,'file'=>__FILE__,'line'=>__LINE__),
                          'response'  => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage()),
                          'type'      => 'excepition');
                        \Log4p::error($logData);
                    }
                }
            }
            else
            {
                //项目初期记录服务访问失败日志
                $logData = array(
                  'request'   => array('data'=>$request,'file'=>__FILE__,'line'=>__LINE__),
                  'response'  => sprintf('服务(%s)不存在！', $service),
                  'type'      => 'gateway');
                \Log4p::warn($logData);
                $response ['msg'] = sprintf('服务(%s)不存在！', $service);
            }
        }

        // 向客户端发送响应数据
        Gateway::sendToClient($client_id, json_encode($response));
    }

    /**
     * 通过服务名称进行服务发现
     * @param string $serviceName
     */
    public static function getService($serviceName)
    {
        $serviceInfo = array();
        $consul = new ConsulHelper();
        $response = $consul->getHealthServiceInfo($serviceName);
        if (!empty($response))
        {
            if (!empty($response) && is_array($response))
            {
                $rndidx = array_rand($response);
                $response = $response[$rndidx];
                echo '--------------->', print_r($response);
                $serviceInfo['host'] = empty($response['ServiceAddress']) ? $response['Address'] : $response['ServiceAddress'];
                $serviceInfo['port'] = $response['Port'];
            }
        }
        //记录服务返回数据
        $logData = array(
            'request'   => array('action'=>'getService','file'=>__FILE__,'line'=>__LINE__),
            'response'  => $response,
            'type'      => 'info');
        \Log4p::info($logData);
        return $serviceInfo;
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        //$response = ['code'=>0,'msg'=>'连接断开(5008)','data'=>''];
        echo sprintf('客户端【%s】连接断开(5008)', $client_id), PHP_EOL;
        //GateWay::sendToAll(json_encode($response));
    }
}