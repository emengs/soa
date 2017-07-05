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

// 自动加载类

// 开启的端口
$worker = new Worker('JsonNL://0.0.0.0:3050');
//测试用2041
// 启动多少服务进程
$worker->count = 5;
// worker名称，php start.php status 时展示使用
$worker->name = 'ShakeAPI';




require_once dirname(dirname(__DIR__)). '/vendor/System/Core/App.php';



$worker->onMessage = function ($connection, $data)
{
    $app = new App();
    $result = $app->run ($data);
	// 发送数据给客户端，请求包错误
//    $result = array('system'=>'webadmin');
    return $connection->send($result);
		
    
};


// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START'))
{
    Worker::runAll();
}
