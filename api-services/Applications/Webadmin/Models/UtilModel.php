<?php

namespace Webadmin\Models;

use Webadmin\Models\BaseModel;
use Webadmin\Exception\ValidationException;
use System\Helpers\CaptchaLib;
use System\Helpers\QRcode;

/**
 *  工具 model
 * @author zouzhijia
 */
class UtilModel extends BaseModel
{

    /**
     * 生产图形验证码
     */
    public function createCaptcha()
    {
        $captcha_lib = new CaptchaLib();
        $captcha_lib->createImage();
        $code = $captcha_lib->getCode();
        $base64 = $captcha_lib->getBaseCode();
        $token = md5(uniqid(md5(microtime(true)), true));
        $this->RedisCache($this->RedisCaptcahPrefix)->setRandom($token, $code);
        //存缓存
        return array('captcha_token' => $token, 'base64_code' => $base64);
    }

//    /**
//     * 生产图形验证码
//     */
//    public function createQrcode($param)
//    {
//        $url = webadmin_load_config('config', 'weixin_url') . '/init/';
//        return array('basecode64' => QRcode::base64Encode($url . $param['qrcode_id'], $param['size']));
//    }
}