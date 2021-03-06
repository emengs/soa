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
use Workerman\Worker;
use Workerman\Lib\Timer;

// 自动加载类
// 开启的端口
$worker = new Worker('http://0.0.0.0:8870');
// 启动多少服务进程
$worker->count = 5;
// worker名称，php start.php status 时展示使用
$worker->name = 'ShakeAdminAPI_HTTP';

require_once dirname(dirname(__DIR__)) . '/vendor/System/Core/App.php';

$worker->onMessage = function ($connection, $data)
{
    var_dump($data);
    echo '..........................................',$connection->id,PHP_EOL;
    $request = array_merge($data ['get'], $data ['post']);
    $new_data=[
        'class'         => 'webadmin',
        'method'        => empty($request['action']) ? '' : $request['action'],
        'param_array'   => empty($request['param']) ? '' : json_decode($request['param'],true)
    ];
    var_dump($new_data);
    $app = new App();
    $result = $app->run($new_data);
    // 发送数据给客户端，请求包错误
    $new_result= json_encode($result);
    $connection->send($new_result);
};


// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START'))
{
    Worker::runAll();
}
