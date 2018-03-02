<?php
ini_set('display_errors', 'on');
function run(){
	$event = event_new();
	$event_base = event_base_new();
	$ret = event_set($event,$fd,EV_READ|EV_WRITE,'callback',array('hello','world'));
	if ($ret) {
		$ret = event_base_set($event, $event_base) ;
		if ($ret) {
			$ret = event_add($event);
			if($ret){
				$ret = event_base_loop($event_base);
			}
		}
	}
	
}

function callback($a,$b){
	echo 'run : ',$a,' ,',$b,"\r\n";
}


function testA(){
	// 创建并且初始事件
	$base = event_base_new();
	// 创建一个新的事件
	$event = event_new();
	// 准备想要在event_add中添加事件
	event_set($event,0,EV_TIMEOUT,function(){
		echo "hello world...".PHP_EOL;
	});
	// 关联事件到事件base
	event_base_set($event,$base);
	// 向指定的设置中添加一个执行事件
	event_add($event,5000000);
	// 处理事件，根据指定的base来处理事件循环
	event_base_loop($base);
}

function testB(){
	// create base and event
	$base = event_base_new();
	$event = event_new();

	$fd = STDIN;
	// set event flags
	event_set($event, $fd, EV_READ | EV_PERSIST,function($fd,$events,$arg){
		static $max_requests = 0;
	    $max_requests++;
	    if ($max_requests == 10) {
	        // exit loop after 10 writes
	        event_base_loopexit($arg[1]);
	    }

	    echo  fgets($fd);
	}, array($event, $base));
	// set event base
	event_base_set($event, $base);
	// enable event
	event_add($event);
	// start event loop
	event_base_loop($base);
}

function testC(){
	$socket = stream_socket_server ('tcp://0.0.0.0:8008', $errno, $errstr,STREAM_SERVER_BIND | STREAM_SERVER_LISTEN );
	if ($socket == false) {
		echo 'start the tcp server fail ! errno: ',$errno,' message: ',$errstr,PHP_EOL;
		return;
	}
	echo "success start the tcp server (tcp://0.0.0.0:8008) \r\n";
	stream_set_blocking($socket, 0);

	$base = event_base_new();
	$event = event_new();
	echo "base event: $base,  event: $event \r\n";
	event_set($event, $socket, EV_READ | EV_WRITE | EV_PERSIST, 'ev_accept',$base);
	event_base_set($event, $base);
	event_add($event);
	event_base_loop($base);
}
// 监听客户端请求连接
function ev_accept($socket,$flag,$base)
{
	$param = func_get_args();
	echo 'call the method ev_accept ',print_r($param);

	echo "waiting for the client to connect \r\n";
    $connection = stream_socket_accept($socket);
    stream_set_blocking($connection, 0);

    $buffer = event_buffer_new($connection, 'ev_read', null, 'ev_error', $connection);
    echo "client connection: $connection,  event buffer: $buffer \r\n";

    event_buffer_base_set($buffer, $base);
    event_buffer_timeout_set($buffer, 30, 30);
    event_buffer_watermark_set($buffer, EV_READ | EV_WRITE, 0, 0xffffff);
    event_buffer_priority_set($buffer, 10);
    event_buffer_enable($buffer, EV_READ | EV_WRITE | EV_PERSIST);

    $GLOBALS['_'] = $buffer;  //这个buffer一定要赋给个全局的变量 貌似是传值过程中的bug 或者5.3.8的闭包还是有问题？
}
// 错误处理操作
function ev_error($buffer, $error, $connection)
{
    event_buffer_disable($buffer, EV_READ | EV_WRITE);
    event_buffer_free($buffer);
    fclose($connection);
}
// 读取缓冲区数据
function ev_read($buffer, $connection)
{
	$request = '';
    while ($read = event_buffer_read($buffer, 256)) {
    	if (stripos($read,"\r\n") < 0) {
    		$request .= $read;
    		continue;
    	}
    	echo 'receive the client message [',trim($request),']',PHP_EOL;
 		
	    $response = date('Y-m-d H:i:s')." success receive the client message \r\n";
	    echo $response;
	    fwrite($connection , "The message send from server! ");
    }	
    // ev_error($buffer , '' , $connection);
}
// 向缓冲区写入数据
function ev_write($buffer,$connection){
	$param = func_get_args();
	print_r($param);

	$response = date('Y-m-d H:i:s')." send to the client message \r\n";
	$ret = event_buffer_write($buffer, $response);
	echo 'run the ev_write method',PHP_EOL;
    //fwrite($connection , $response);
}

function testD()
{
	$server = 'tcp://0.0.0.0:8008';
	$errno = $errstr = '';
	$input = fgets(STDIN);
	echo "haha haha haha $input \r\n";

	$socket = stream_socket_client($server,$errno,$errstr,30,STREAM_CLIENT_PERSISTENT) or print_r ('connect to the server fail! errno: ',$errno,'	  errstr: ',$errstr,PHP_EOL);

	while (!empty($input)) {
		$in = fgets(STDIN);
		$input .= $in; 
		if ($in == "\r\n" || $in == "\r" || $in == "\n") {
			echo $input;
			$input .="\r\n\r\n";
			echo "send the messae to the server \r\n";

			$write = stream_socket_sendto($socket,$input);
			if ($write) {
				while ($response = stream_socket_recvfrom($socket,1024)) {
					echo $response,PHP_EOL;
					if (empty(trim($response))) {
						fclose($socket);
						break;
					}
				}
			}	
			$input = fgets(STDIN);
		}
	}

}

function start(){
	global $argv,$argc;
	$c = $argc;
	if ($c > 1) {
		$s = $argv[1];
		switch ($s) {
			case 'A':
				testA();
				break;
			case 'B':
				testB();
				break;
			case 'C':
				testC();
				break;
			case 'D':
				testD();
				break;
			default:
				echo "command line is error \r\n you can input php xxx.php A|B|C|D \r\n";
				break;
		}
	}
}

//start();
//

function UDPTest()
{
	$server = 'udp://10.100.100.126:6001';
	$errno  = $errstr = '';

	$socket = stream_socket_client($server,$errno,$errstr,30,STREAM_CLIENT_PERSISTENT) or print_r ('connect to the server fail! errno: ',$errno,'	  errstr: ',$errstr,PHP_EOL);
	$ping 	= 'ping';
	$add 	= 'add:{"server_port": 8001, "password":"7cd308cc059"}';
	$remove = 'remove:{"server_port": 8001}';
	$write  = fwrite($socket, $add);
	if($write){
    	$response = fread($socket, 256);
    	echo $response;
    	fclose($socket);
	}
	echo 'success';
}

function ssTest(){
	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	if ($socket) {
		$conn = socket_connect($socket, '10.100.100.126',6001);
		if ($conn) {
			//$package = 'ping';
			$package = 'add:{"server_port": 8001, "password":"7cd308cc059"}';
			//$package = 'remove:{"server_port": 8001}';
			$send = socket_send($socket, $package, strlen($package), 0);
    
		    if($response = socket_read($socket, 255)) {	        
		        echo $response, 'ok';
		    }
		    socket_close($socket);
		}
	}

	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);
	echo $errorcode,  $errormsg;
	echo 'success';
}

function getBytes($string) { 
    $bytes = array(); 
    for($i = 0; $i < strlen($string); $i++){ 
         $bytes[] = ord($string[$i]); 
    } 
    return $bytes; 
} 

 function bytesToStr($bytes) { 
    $str = ''; 
    foreach($bytes as $ch) { 
        $str .= chr($ch); 
    } 

       return $str; 
} 
ssTest();