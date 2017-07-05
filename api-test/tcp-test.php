<?php

for ($i=0; $i < 2000; $i++) { 
	$stime = microtime(true);
	$url = 'http://10.100.100.73:8899/?service=dkh.module.shake.tcp.webadmin&action=manage_getcaptcha&param={%22action%22:%22getcaptcha%22}';
	$response = request($url);
	$etime = microtime(true);
	$spend = ($etime - $stime) * 1000;
	$data = json_decode($response,true);
	$runlog =PHP_EOL.sprintf('start: %s   ,  end time: %s  , spend time: %s ms  , response: %s',$stime,$etime,$spend,$data['msg']).PHP_EOL;
	file_put_contents('./tcp_run.log', $runlog,FILE_APPEND);
	echo $runlog,PHP_EOL;
	usleep(100000);
}

function request($url,$method='get'){
	$html = '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $postData = array();
        if (strtolower($method) == 'post' && !empty($options))
        {
            $postData = json_encode($options);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type:application/json',
              'Content-Length: ' . strlen($postData)
            ));
        }

        try
        {
            $html = curl_exec($ch);
            if ($html === false)
            {
                echo json_encode(array('type' => 'Consul', 'request' => array('action'=>'request','file'=>__FILE__,'line'=>__LINE__,'data'=>$postData), 'response' => $ch));
                return false;
            }
        }
        catch (\Exception $e)
        {
            echo json_encode(array('type' => 'Consul', 'request' => $postData, 'response' => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage())));
            return false;
        }
        curl_close($ch);
        return $html;
}
