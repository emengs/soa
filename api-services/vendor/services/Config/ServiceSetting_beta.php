<?php
class AppSetting
{
    public static $Consul = [
        'consul_host' => '10.100.100.35',
        'consul_port' => 8500,
        'services' => [
            [
                'service_host' => '10.100.100.35',
                'service_port' => 3040,
                'id' => 'dkh.module.shake.webadmin', // 服务唯一标识
                'name' => 'dkh.module.shake.webadmin', // 服务名称
                'tags' => ['dkh.module.shake.webadmin'], // 服务标签
                'script' => ''    // 服务健康检测地址
            ],
            [

                'service_host' => '10.100.100.35',
                'service_port' => 3050,
                'id' => 'dkh.module.shake.weixin', // 服务唯一标识
                'name' => 'dkh.module.shake.weixin', // 服务名称
                'tags' => ['dkh.module.shake.weixin'], // 服务标签
                'script' => ''    // 服务健康检测地址
            ],

            [

                'service_host' => '10.100.100.35',
                'service_port' => 3060,
                'id' => 'dkh.module.shake.console', // 服务唯一标识
                'name' => 'dkh.module.shake.console', // 服务名称
                'tags' => ['dkh.module.shake.console'], // 服务标签
                'script' => ''    // 服务健康检测地址
            ],
        ]
    ];

}

