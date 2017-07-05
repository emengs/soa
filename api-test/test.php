<?php
$arra = [];
$count = 12;
for($i=100;$i<=200;$i++){
	$idx = $i % $count;
	$arra[$idx][] = $i;
	echo 'idx=',$idx,'  number=',$i,PHP_EOL;
}
sort($arra);
print_r($arra);