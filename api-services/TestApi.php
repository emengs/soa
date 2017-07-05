<?php
include_once 'Applications/Services/Clients/RpcClient.php';

$address_array = array (
		'tcp://127.0.0.1:2025'
);
// 配置服务端列表
RpcClient::config ( $address_array );

$uid = 567;

// User对应applications/JsonRpc/Services/User.php 中的User类
$user_client = RpcClient::instance ( 'User' );

// getInfoByUid对应User类中的getInfoByUid方法
$ret_sync = $user_client->getInfoByUid ( $uid );

?>