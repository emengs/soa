<?php

//网站信息配置
$config['version']                  = '1.0.2017.0425';    //版本号,2017.04025表示发布日期
$config['copyright']                = '深圳盛灿科技股份有限公司';  //网站版权

$config['division']                  = '_';   //服务分割符号


$config['database'] = [
    'default'=>[
        'host' => '10.100.100.35',
        'port' => '3306',
        'user' => 'root',
        'password' => '123456',
        'charset' => 'utf8',
        'dbname' => 'dkh_module_shake_new_beta',
    ],
];
$config['redis'] = array(
    'host' => '10.100.100.35',//地址
    'port' => '6379',// 端口
    'prefix' => '',// 前缀
    'timeout' => '',// 服务器连接限制时间 (秒)
    'expire' => 60*60*24*30, // 缓存有效时间 (秒) 一个月
    'auth' => '',// 缓存秘钥
    'db' =>'3',//库名称为空根据数据类型分库，库名在1-5之间
);

$config['shopinfo'] = [
    'app_id' => 'vbRHfZaA9A6KwxlPMd',
    'app_secret' => 'c49ce3d2fa44b570425e1e6549663b85',
    'shop_id' => '108089',
    'login_url'=>'http://weiqufang.vikduo.weixin.zhsqbeta.snsshop.net/weiqufang/user/shake'
];



?>