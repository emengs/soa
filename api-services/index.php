<?php
define("ROOT_PATH", dirname(__FILE__));
$path = ROOT_PATH . "/start.php";
var_dump($path);
$output = shell_exec("php $path restart -d");
var_dump($output);



