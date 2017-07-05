<?php

namespace Webadmin\Controllers;

use Webadmin\Models\BaseModel;
use Webadmin\Exception\LoginException;
use Webadmin\Models\ThirdPartyModel;


require_once dirname(__DIR__) . '/Helpers/FunctionHelper.php';


/**
 *  基础控制器
 * @author guibinYu
 */
class Base
{
    private $Redis_token_name ='admin_token';
    public $platform_token = false; //平台token
    public $manage_id = false; //用户id
    public $params;
    public $shop_info;
    //不需要登录控制器方法
    public $filter = ['login', 'getcaptcha'];

    public function __construct($params)
    {

        $this->shop_info = webadmin_load_config('config_'.ENV,'shopinfo');
        $action = isset($this->params['action']) ? $this->params['action'] : FALSE;
        $this->platform_token = isset($params['p_token']) ? $params['p_token'] : '';
        if (!in_array($action, $this->filter))
        {
     $this->auth();
        }

    }

    /**
     * 生成token
     * @return string
     */
    public function createToken()
    {
        $token = md5(uniqid(md5(microtime(true)), true));
        $this->platform_token = $token;
        return $token;
    }

    /**
     * 设置token缓存
     * @return string
     */
    public function setSession($manage_id, $shop_id)
    {
        if (!$this->platform_token)
        {
            return;
        }
        $model_base = new BaseModel();
        $model_base->RedisCache($this->Redis_token_name)->setWebAdminToken($this->platform_token, $manage_id . '_' . $shop_id);
    }
    
    /**
     * 清除token缓存
     * @return string
     */
    public function clearSession()
    {
        if (!$this->platform_token)
        {
            return;
        }
        $model_base = new BaseModel();
        return $model_base->RedisCache($this->Redis_token_name)->delWebadminToken($this->platform_token);
    }

    /**
     * 获取token缓存值
     * @return string
     */
    public function getSession()
    {
        if (!$this->platform_token)
        {
            return FALSE;
        }
        $model_base = new BaseModel();
        $v = $model_base->RedisCache($this->Redis_token_name)->getWebAdminToken($this->platform_token);
        return $v;
    }

    /**
     * 身份认证
     * @return string
     */
    public function auth()
    {
        if (!$this->platform_token)
        {
            throw new LoginException(30101);
        }
        $admin_session = $this->getSession();
        if (!$admin_session)
        {
            throw new LoginException(30101);
        }
        $admin_session_arr = explode('_', $admin_session);

        $this->manage_id = $admin_session_arr[0];
        $this->setSession($admin_session_arr[0], $admin_session_arr[1]);
    }

    /**
     * 加载配置
     */
    public function getBusinessConfig(){

        $this->shop_info=require(FILES_PATH.'/Applications/Webadmin/Config/Business_config_'.ENV.'.php');

    }


}