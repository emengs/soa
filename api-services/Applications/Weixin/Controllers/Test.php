<?php

namespace Weixin\Controllers;


use Weixin\Controllers\Base;

use System\Curl;
use System\Libraries\Weikeduo;
use Weixin\Models\AgentModel;
use Weixin\Models\TestModel;
/**
 *  测试控制器
 * @author guibinYu
 */
class Test   extends \Weixin\Controllers\Base
{
    
   private $params;
   public function __construct($params)
    {
        parent::__construct();
        $this->params = $params['params'];
         $this->agentModel = new \Weixin\Models\AgentModel();
        
    }
    /**
     * 控制器入口，放列表或入口逻辑
     * @return type
     */
    public  function index()
    {

        $model = new \Weixin\Models\TestModel();
        $params = array('action'=>'record','agent_id'=>1,'qrcode_id'=>'1','openid'=> 'o6byawOggBP7yj3lKlPT-wDpMyQM','is_subscribe'=>1);
        $data = $model->getList($params);
        dump($data);
    }
    /**
     * 单条数据获取
     * @return type
     */
    public  function view($params)
    {
        $model = new \Models\TestModel();
        $data = $model->getView($params);
         return $data;
    }
     /**
     * 数据创建
     * @return type
     */
     public  function create($params)
    {
        $filed = array(
             'agent_name'=>'商户名称必填',
             'logo'=>'商户logo必填',
             'qrcode'=>'二维码必填',
             'mobile'=>'联系方式必填',
             'description'=>'商户描述必填'
            );
         $data = need_params($params,$filed);
         if(!is_array($data))
         {
             return $data;
         }
         $model = new \Models\TestModel();
        $data = $model->opCreate($params);
        
         return $data; 
    }
    /**
     * 数据更新
     */
    public  function update($params)
    {
         if(!isset($params['agent_id']))
         {
             return '参数错误';
         }
         $model = new \Models\TestModel();
        $data = $model->opUpdate($params);
//        dump($data);
         return $data; 
    }
    /**
     * 数据删除，数据是软删除
     */
    public  function delete($params)
    {
        if(!isset($params['agent_id']))
         {
             return '参数错误';
         }
         $model = new \Models\TestModel();
        $data = $model->opDelete($params);
//        dump($data);
         return $data;   
    }
//index, view, create, update, delete, options
}