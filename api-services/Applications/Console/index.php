<?php
@date_default_timezone_set('PRC'); /* 设置服务器的时间为北京时间 */
define('ENV','local');
define('FILES_PATH', dirname(dirname(__DIR__)));
define('APP_ROOT',__DIR__);
require_once dirname(dirname(__DIR__)) . '/vendor/system/Core/App.php';



$data = $_GET ? $_GET : array();
$data['param_array']=json_decode($data['param'],true);
$app = new App();
$result = $app->run($data);
echo json_encode($result, TRUE);
