<?php
class AppSetting
{
    public static $Consul = [
        'consul_host' => '10.100.100.72',
        'consul_port' => 8500,
        'services' => [
            [
                'service_host' => '10.100.100.72',
                'service_port' => 8870,
                'id'    => 'dkh.module.shake.webadmin72', // 服务唯一标识
                'name'  => 'dkh.module.shake.webadmin.dev', // 服务名称
                'tags'  => ['dkh.module.shake.webadmin.dev'], // 服务标签
                'http'  =>'http://10.100.100.72:8870',
                'script' => '',    // 服务健康检测地址
                
            ],
            [

                'service_host' => '10.100.100.72',
                'service_port' => 8880,
                'id'    => 'dkh.module.shake.weixin72', // 服务唯一标识
                'name'  => 'dkh.module.shake.weixin.dev', // 服务名称
                'tags'  => ['dkh.module.shake.weixin.dev'], // 服务标签
                'http'  =>'http://10.100.100.72:8880',
                'script' => ''    // 服务健康检测地址
            ],

            [

                'service_host' => '10.100.100.72',
                'service_port' => 8860,
                'id'    => 'dkh.module.shake.console72', // 服务唯一标识
                'name'  => 'dkh.module.shake.console.dev', // 服务名称
                'tags'  => ['dkh.module.shake.console.dev'], // 服务标签
                'http'  =>'http://10.100.100.72:8860',
                'script' => ''    // 服务健康检测地址
            ],
        ]
    ];

}

