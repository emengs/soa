<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/9
 * Time: 19:35
 * 用户信息模块
 */
namespace Weixin\Models;


class OauthModel extends BaseModel{

    /**
     * 登录获取用户信息
     */

    public function verify($token){

        $users= $this->RedisCache($this->RdisUserTokenPrefix)->getWebToken($token);
        if($users){
            $this->RedisCache($this->RdisUserTokenPrefix)->expireAt($token,7200);
            return $users;
        }
        return false;

    }
    /**
     * 登录
     */
    public function Login($pramas){

        $md5=['openid'=>$pramas['open_id'],'uid'=>$pramas['uid']];
        $token = md5(json_encode($md5));
        $this->RedisCache($this->RdisUserTokenPrefix)->setWebToken($token,$pramas);
        return $token;

    }


}