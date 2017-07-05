<?php

//Redis 缓存
$config  = array(
//    'api_url' => "http://openapi.nexto2o.com", //地址
    'api_url' => "http://testopenapi.snsshop.net", //测试地址
    'api_name' => array(
        'tokens' => '/v3/tokens',//获取token接口
        'shop_get' => '/v3/shop/get',//商家信息获取接口
        'shop_links' => '/v3/shop/get-shop-links',//链接内容获取接口
        'auth' => '/h3/auth/index',//用户授权登陆接口
        'users' => '/v3/users/',//单个用户信息('/v3/users/{uid}')
        'batch_users' => '/v3/users',//批量用户信息
        'card_coupon' => '/v3/card-coupon/find',//卡券列表
        'hand_send_card' => '/v3/card-coupon/hand-send-card',//手动派发卡券
        'intergal_edit' => '/v3/member/integral-edit',//会员积分调整
        'message_send' => '/v3/message/send',//消息发送
        'access_tokens' => '/v3/wx-api/get-access-token',//消息发送
        
    )
);

// 公众号
//微鱼客栈
//账号：1049703520@qq.com  
//密码：admin3520
//appid：wx49144f8416c40f5f  
//appsecret：14bdd09f54be71eb4ea1baeccb5c9a44   
//支付商号：1343865301  
//支付秘钥：fe9b028a577e5e90239dc5ab5c589b27 
 
//http://testwkd.snsshop.net/data-center/workbench
//账号、密码：20170503 / 123456 


//使用AppID与AppSecret进行微客多接口的调用
//AppID  kj56ccH0UCYY1FBSpC
//AppSecret 574eec01d2c1c7c04eaa4df79a3b8f31


//商品发货通知（模板ID）：　eWJFP6o_YIXx3hDFURHKW9Ln77T4nNOpHsVDdl-RKl8
//开奖结果通知（模板ID）：　xrmv9TbLv9EZeWhPTTnMwb2lyzETMnhm0Inr5dyhPig

//商品发货通知：OPENTM400262692 
//开奖结果通知：OPENTM206854010
?>