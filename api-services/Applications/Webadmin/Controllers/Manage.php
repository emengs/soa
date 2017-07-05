<?php

namespace Webadmin\Controllers;


use Webadmin\Models\ManageModel;

use Webadmin\Models\UtilModel;
use Webadmin\Exception\ValidationException;


/**
 *  管理员控制器
 * @author zhijiazou
 */
class Manage extends Base
{

    public function __construct($params)
    {
        $this->params = $params['params'];
        parent::__construct($this->params);
    }

    /**
     * 控制器入口，放列表或入口逻辑
     * @return type
     */
    public function index()
    {
        $action = isset($this->params['action']) ? $this->params['action'] : 'login';
        $data = [];
        switch ($action)
        {
            case 'login':  //登陆
                $data = $this->login();
                break;
            case 'logout':  //退出登录
                $data = $this->logout();
                break;
            case 'captcha':  //验证码
                $data = $this->getCaptcha();
                break;
            default :
                throw new ValidationException(40101);
        }
        return $data;
    }

    /**
     * 单条数据获取
     * @return type
     */
    public function view()
    {
        $model_users = new ManageModel();
        $data = $model_users->get($this->manage_id);
        unset($data['password']);
        return $data;
    }

    /**
     * 数据创建
     * @return type
     */
    public function create()
    {
        $model_users = new ManageModel();
        $data = $model_users->insert($this->params);
        return $data;
    }

    /**
     * 数据更新
     */
    public function update()
    {
        $model_users = new ManageModel();
        $data = $model_users->changePassword($this->params);
        return $data;
    }

    /**
     * 数据deleted状态修改
     */
    public function delete()
    {
        $model_users = new ManageModel();
        $data = $model_users->updateDeleted($this->params);
        return $data;
    }

    /**
     * 登陆
     */
    public function login()
    {

        $model_users = new ManageModel();
        $data = $model_users->login($this->params);
        $shop_id = $data['channel_id'];
        $manage_id = $data['manage_id'];

        $token = $this->createToken();
        $this->setSession($manage_id, $shop_id);
        return ['token' => $token];
    }

    /**
     * 退出登陆
     */
    public function logout()
    {
        $data = $this->clearSession();
        return $data;
    }

    /**
     * 验证码
     */
    public function getCaptcha()
    {
        $model_util = new UtilModel();
        return $model_util->createCaptcha();
    }
}