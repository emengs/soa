<?php

namespace Applications\ApiGateway\Config;

/**
 * 应用配置类
 *
 * @author SunnyZeng
 * @since v1.0.0
 */
class AppSetting
{
    /**
     * 服务中心配置
     * 
     * @var array
     */
    static $Consul = array(
      'host' => '10.100.100.72',
      'port' => '8500'
    );

}
?>