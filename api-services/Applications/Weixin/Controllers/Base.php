<?php

namespace Weixin\Controllers;

use Webadmin\Exception\LoginException;
use Weixin\Models\OauthModel;
use Weixin\Models\ThirdPartyModel;
require_once dirname(__DIR__) .'/Helpers/FunctionHelper.php';
/**
 *  基础控制器
 * @author guibinYu
 */
class Base
{
    protected $error;//错误
    protected $params;
    protected $user_info;
    protected $shop;
protected $shop_info;
    //不需要登录控制器方法
    public $filter = ['login', 'captcha','test'];

    public function __construct($params = array())
    {
        $this->params = isset($params['params'])?$params['params']:[];
        $this->shop_info = weixin_load_config('Config_'.ENV,'shopinfo');
        $this->error = weixin_load_config('Config_'.ENV);

        //判断是否登录  获取用户信息
        $action = isset($this->params['action']) ? $this->params['action'] : FALSE;

        if (!in_array($action, $this->filter))
        {
           $oauth= $this->Oauth();
            if(!$oauth){
                throw new LoginException(30101);
            }
        }

    }

    /**
     * 登录验证
     * @param type $qrcode_id
     */
    public function Oauth(){

       if(isset($this->params['token'])){
        $OauthModel= new OauthModel();
        $user_info=$OauthModel->verify($this->params['token']);
        if($user_info){
            $this->user_info=$user_info;
            return true;
        }
       }
            return false;

    }


    
    /**
     * 响应数据
     * @param type $result
     * @return type
     */
    protected function response($result)
    {
//        $code = isset($result['code']) ? $result['code'] : 1;
//        if($code == 0)
//        {
//            $return = array_merge($this->error[$code],$result);
//        }
//        else//详细的错误消息不暴露出去
//        {
            
//             $logData = array('params'=> $this->params ,'response'=>$result);
//            log_result('wx_response', $logData,'wxerror.log');
//            $return = $this->error[$code];
            
//        }
        return $result;
    }
    

}