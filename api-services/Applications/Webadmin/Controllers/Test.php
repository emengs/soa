<?php

namespace Webadmin\Controllers;

use Webadmin\Controllers\Base;
use Webadmin\Models\PagesModel;

/**
 *  测试控制器
 * @author guibinYu
 */
class Test extends Base
{

    public function __construct($params)
    {
        $this->params = $params['params'];
        parent::__construct($this->params);
    }


    public function log(){
        \Log4p::error(['type' => 'weixinapi', 'request' => ['id' => 1], 'response' => ['errcode' => 1, 'errmsg' => 'XXXX']]);
    }
    /**
     * 控制器入口，放列表或入口逻辑
     * @return type
     */
    public function index($params)
    {
//        return array('system'=>'webadmin','version'=>'v1.0','current'=>'test controller');
        $model = new \Webadmin\Models\TestModel();
        $data = $model->getList($params);
        $data['paltform'] = 'webadmin';
//        dump($data);
        return $data;
    }

    /**
     * 单条数据获取
     * @return type
     */
    public function view($params)
    {
        $model = new \Models\TestModel();
        $data = $model->getView($params);
        return $data;
    }

    /**
     * 数据创建
     * @return type
     */
    public function create($params)
    {
        $filed = array(
          'agent_name' => '商户名称必填',
          'logo' => '商户logo必填',
          'qrcode' => '二维码必填',
          'mobile' => '联系方式必填',
          'description' => '商户描述必填'
        );
        $data = need_params($params, $filed);
        if (!is_array($data))
        {
            return $data;
        }
        $model = new \Models\TestModel();
        $data = $model->opCreate($params);
        dump($data);
        return $data;
    }

    /**
     * 数据更新
     */
    public function update()
    {
        $model_pages = new PagesModel();
        $data = $model_pages->pagesUpdate($this->params);
        return $data;
    }

    /**
     * 数据删除，数据是软删除
     */
    public function delete($params)
    {

        if (!isset($params['agent_id']))
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