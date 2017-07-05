<?php
define('LOG_ROOT', __DIR__);
require_once 'Logger/Log4p.php';

function test()
{
    for ($i = 0; $i < 10; $i++)
    {
        Log4p::error(['type' => 'weixin_XXX', 'request' => ['id' => 1], 'respone' => ['errcode' => 1, 'errmsg' => 'XXXX']]);
        Log4p::info(['type' => 'weixin_order', 'request' => ['id' => 1], 'respone' => ['errcode' => 1, 'errmsg' => 'XXXX']]);
        Log4p::debug(['type' => 'weixin_order', 'request' => ['id' => 1], 'respone' => ['errcode' => 1, 'errmsg' => 'XXXX']]);
//        sleep(1);
    }
}
test();
