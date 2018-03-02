<?php
function testA(){
	$arra = [];
	$count = 12;
	for($i=100;$i<=200;$i++){
		$idx = $i % $count;
		$arra[$idx][] = $i;
		echo 'idx=',$idx,'  number=',$i,PHP_EOL;
	}
	sort($arra);
	print_r($arra);
}


function testB(){
	$start = memory_get_usage();  
    $a = array_fill(0, 10000, 1);  
    $mid = memory_get_usage(); //10k elements array;  
    echo 'argv:', ($mid - $start )/10000,'byte' , '<br>';  
    $b = array_fill(0, 10000, 1);  
    $end = memory_get_usage(); //10k elements array;  
    echo 'argv:', ($end - $mid)/10000 ,'byte' , '<br>';  
}

function testC(){
	echo memory_get_usage() , "\r\n";  
    $start = memory_get_usage();  
    $a = Array();  
    for ($i=0; $i<10000; $i++) {  
    	for($k=0;$k<10;$k++){
    		$vv = implode( array_rand(array('a','b','c','d','e','f','g','h','j','k','m','o','p','q','r','s','t','u','v','w','x','y','z'),10) , '');
    		$a[$i][$k] = substr($vv, 1,10);
    	}	  
    }  
    $mid =  memory_get_usage();  
    echo memory_get_usage() , "\r\n";  
    for ($i=10000; $i<50000; $i++) {  
    	for($k=0;$k<10;$k++){
    		$vv = implode( array_rand(array('a','b','c','d','e','f','g','h','j','k','m','o','p','q','r','s','t','u','v','w','x','y','z'),10) , '');
    		$a[$i][$k] = substr($vv, 1,10);
    	}	
    }  
    $end =  memory_get_usage();  
    echo memory_get_usage() , "\r\n";  
    echo 'argv:', ($mid - $start)/1024/1024 ,'Mb' , "\r\n";  
    echo 'argv:',($end - $mid)/1024/1024 ,'Mb' , "\r\n";  
    // $test = array_slice($a, 10);
    //print_r($test);
}

testC();
//
//echo 134217728 / 1024 /1024 .' M';