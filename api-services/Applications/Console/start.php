<?php
use Workerman\Worker;

define('FILES_PATH', dirname(dirname(__DIR__)));
// 自动加载类
// 开启的端口
$worker = new Worker('JsonNL://0.0.0.0:8860');
// 启动多少服务进程
$worker->count = 5;
// worker名称，php start.php status 时展示使用
$worker->name = 'ShakeConsole';

require_once dirname(dirname(__DIR__)) . '/vendor/System/Core/App.php';


$worker->onWorkerStart=function ($connection){
    if ($connection->id === 0) {
        $app = new App();
        $data = ['class' => 'console', 'method' => 'crontab_initialize', 'param' => []];
        $result = $app->run($data);
    }
};

$worker->onMessage = function ($connection, $data)
{

    $app = new App();
    $result = $app->run($data);
    // 发送数据给客户端，请求包错误
//    $result = array('system'=>'webadmin');
    return $connection->send($result);
};

$worker->onWorkerStop  = function($connection)
{
    $app = new App();
    $data=['class'=>'console','method'=>'crontab_delip','param'=>[]];
    $result = $app->run($data);

};

$worker->onWorkerReload  = function($connection)
{
    if ($connection->id === 0) {
    $app = new App();
    $data=['class'=>'console','method'=>'crontab_reloadip','param'=>[]];
    $result = $app->run($data);
        }

};


// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START'))
{
    Worker::runAll();
}
