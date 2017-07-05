<?php

@date_default_timezone_set ( 'PRC' ); /* 设置服务器的时间为北京时间 */


require_once dirname(dirname(__DIR__)). '/vendor/system/Core/App.php';


$qrcode_id = 62;
$openid = "o6byawFKJCQQXkSo57t5JTynRD4A";
$uid = "13765532";
$activity_id = 22;
$agent_id = 1;
$url = array(
    '扫码记录接口' => array(
            'class' => "weixin",
            'method' => "qrcode_index",
            'param_array' => json_encode(array('action'=>'record','agent_id'=>$agent_id,'qrcode_id'=>$qrcode_id,'openid'=>$openid,'is_subscribe'=>1))
    ),
   '授权登陆接口' => array(
            'class' => "weixin",
            'method' => "member_view",
            'param_array' => json_encode(array('action'=>'login','qrcode_id'=>$qrcode_id,'back'=>"http://weixin.mingzhi.dkh.snsshop.net/default/qrcode_id=".$qrcode_id))
        ),
    '是否有抽奖权限接口' => array(
            'class' => "weixin",
            'method' => "member_view",
            'param_array' => json_encode(array('action'=>'right','qrcode_id'=>$qrcode_id,'openid'=>$openid,'uid'=>"13765532"))
    ),
    '获取活动基本数据接口' => array(
            'class' => "weixin",
            'method' => "activity_index",
            'param_array' => json_encode(array('action'=>'activity','qrcode_id'=>$qrcode_id))
    ),
    '获取活动历史记录接口' => array(
            'class' => "weixin",
            'method' => "activity_index",
            'param_array' => json_encode(array('action'=>'history','qrcode_id'=>$qrcode_id,'openid'=>$openid,'uid'=>"13765532"))
    ),
     '获取商户信息接口' => array(
            'class' => "weixin",
            'method' => "agent_view",
            'param_array' => json_encode(array('action'=>'agent','agent_id'=>$agent_id))
    ),
    '获取用户信息' => array(
            'class' => "weixin",
            'method' => "member_view",
            'param_array' => json_encode(array('action'=>'member','openid'=>$openid))
    ),
   
    '抽奖接口接口' => array(
            'class' => "weixin",
            'method' => "prize_index",
            'param_array' => json_encode(array('action'=>'draw','qrcode_id'=>$qrcode_id,'openid'=>$openid,'activity_id'=>$activity_id))
    ),
    '新增收货地址接口' => array(
            'class' => "weixin",
            'method' => "prize_index",
            'param_array' => json_encode(array('action'=>'address','win_id'=>1229,'receive_name'=>'张三','receive_tel'=> '13512345678','province'=>'广东省','city'=>'深圳市','area'=>'xx区','address'=>'xx区xx栋','openid'=>$openid))
    ),
    
    '页面数据获取接口' => array(
            'class' => "weixin",
            'method' => "pages_index",
            'param_array' => json_encode(array('action'=>'pages','qrcode_id'=>$qrcode_id,'page_type'=>1,'win_id'=>367))
    ),
     '用户中奖记录' => array(
            'class' => "weixin",
            'method' => "member_view",
            'param_array' => json_encode(array('action'=>'winRecord','openid'=>$openid,'page'=>1,'limit'=>15))
    ),
     'jsAPI配置' => array(
            'class' => "weixin",
            'method' => "agent_view",
            'param_array' => json_encode(array('action'=>'jsapi','qrcode_id'=>$qrcode_id,'url'=>'http://www.baidu.com'))
    ),
      '扫码抽奖权限' => array(
            'class' => "weixin",
            'method' => "qrcode_index",
            'param_array' => json_encode(array('action'=>'permiss','openid'=>$openid,'qrcode_id'=>$qrcode_id,'opt'=>'set'))
    ),
);
 echo "<a href='index.php'>[返回主页]</a><br></br>";
 
$xxxurl = "http://mingzhi.admin.dkh.dev.com/views/system/login.html";
 echo "<a href='{$xxxurl}' target=_blank>[访问开发]</a><br></br>";
 
$host = "http://mingzhi.com/applications/weixin/index.php?";
$develop_host = "http://10.100.100.73:8281?";
echo "开发域名：".$develop_host."</br>";

$test_host = "http://gateway.bsp.dkh.snsshop.net?";
echo "测试域名：".$test_host."</br>";

foreach ($url as $key=>$val)
{
    $query = http_build_query($val);
    
    $dps = http_build_query(array(
        'service' => $val['class'],
        'action' => $val['method'],
        'param' => $val['param_array'],
    ));
     
     echo "接口名称： {$key}<br>";
    echo "<a href='{$host}{$query}'>[访问本地]</a>";
    
    $develop =  "{$develop_host}service=minzhi.weixin&action={$val['method']}".'&'."param={$val['param_array']}";
    echo "<a href='{$develop}'>---[访问开发]</a>";
    
    $test =  "{$test_host}service=minzhi.weixin&action={$val['method']}".'&'."param={$val['param_array']}";
    echo "<a href='{$test}'>---[访问测试]</a><br></br>";
}

