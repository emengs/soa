<?php

@date_default_timezone_set ( 'PRC' ); /* 设置服务器的时间为北京时间 */
define('FILES_PATH', dirname(dirname(__DIR__)));
define('ENV','local');
define('APP_ROOT',__DIR__);
require_once dirname(dirname(__DIR__)). '/vendor/system/Core/App.php';



 echo "<a href='apitest.php'>[接口测试]</a><br></br>";
 echo "<a href='wkdTest.php'>[微客多接口测试]</a><br></br>";
echo "---------------接收参数----------------";
 $data = $_REQUEST ? $_REQUEST : array();
$data['param_array']=json_decode($data['param'],true);
dump($data);
echo "---------------返回结果----------------";
$app = new App();
$result = $app->run ($data);

dump($result);