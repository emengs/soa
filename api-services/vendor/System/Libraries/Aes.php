<?php

namespace System\Libraries;

/**
 * 利用mcrypt做AES加密解密
 * @package app\common\helpers
 */
class Aes
{
    /**
     * 算法,另外还有192和256两种长度
     */
    const CIPHER = MCRYPT_RIJNDAEL_128;
    /**
     * 模式
     */
    const MODE = MCRYPT_MODE_ECB;
    /**
     * 密钥
     */
    const MIXKEY = 'asddfuiokmjdujdk';

    /**
     * 加密
     * @param string $key 密钥
     * @param string $str 需加密的字符串
     * @return string
     */
    public static function encode($str, $key = '')
    {
        $key = $key ? $key : self::MIXKEY;
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER, self::MODE), MCRYPT_RAND);
        return rtrim(strtr(base64_encode(mcrypt_encrypt(self::CIPHER, $key, $str, self::MODE, $iv)), '+/', '-_'), '=');
    }

    /**
     * 解密
     * @param string $key 密钥
     * @param string $str 需加密的字符串
     * @return string
     */
    public static function decode($str, $key = '')
    {
        $str = str_pad(strtr($str, '-_', '+/'), strlen($str) % 4, '=', STR_PAD_RIGHT);
        $key = $key ? $key : self::MIXKEY;
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER, self::MODE), MCRYPT_RAND);
        $decodeStr = mcrypt_decrypt(self::CIPHER, $key, base64_decode($str), self::MODE, $iv);
        return rtrim($decodeStr, "\0");
    }
}