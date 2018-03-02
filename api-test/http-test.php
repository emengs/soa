<?php


function ccb(){
	$postData = '<?xml version="1.0" encoding="GB2312" standalone="yes" ?><TX><REQUEST_SN>7559478611915066</REQUEST_SN><CUST_ID>P4301551#0C</CUST_ID><USER_ID>WLPT09</USER_ID><PASSWORD>111111</PASSWORD><TX_CODE>6W1503</TX_CODE><LANGUAGE>CN</LANGUAGE><TX_INFO><REQUEST_SN1>6705113395148437</REQUEST_SN1></TX_INFO></TX>'.PHP_EOL;
	$response = request('http://zyf.snsshop.net','post',$postData);
	echo $response;
}

ccb();

function test(){
	for ($i=0; $i < 100; $i++) { 
		$stime = microtime(true);
		$url = 'http://10.100.100.72:8899/?service=dkh.module.shake.http.webadmin&action=manage_getcaptcha&param={%22action%22:%22getcaptcha%22}';
		$response = request($url);
		$etime = microtime(true);
		$spend = ($etime - $stime) * 1000;
		$data = json_decode($response,true);
		$runlog = sprintf('start: %s   ,  end time: %s  , spend time: %s ms  , response: %s',$stime,$etime,$spend,$data['msg']).PHP_EOL;
		file_put_contents('./http_run.log', $runlog,FILE_APPEND);
		echo $runlog,PHP_EOL;
		usleep(100000);
	}
}



function request($url,$method='get',$options=null){
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
            //$postData = json_encode($options);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
            /*curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type:application/json',
              'Content-Length: ' . strlen($postData)
            ));*/
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
