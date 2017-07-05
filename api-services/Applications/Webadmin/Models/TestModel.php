<?php

namespace Webadmin\Models;

use Webadmin\Models\BaseModel;
/**
 *  测试控制器
 * @author guibinYu
 */
class TestModel   extends \Webadmin\Models\BaseModel
{
    /**
     * 获取列表数据，
     * @param type $params
     * @return type
     */
    public  function getList($params)
    {
        
        $result = $this->RedisCache()->set('sys_h', 'webadmin');
       return  array('model'=>'xxx');
       
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 5;
        $offset = ($page-1)* $limit;
        $where = array();
        if(!empty($params['agent_name'])){
            $where[] = 'email like "'.$params['email'].'%"';
        }
         $count = $this->db()->select('count(agent_id) as cot')
                ->from('agent')
                ->where($where)
                ->row();
        
        $data = $this->db()->select('agent_id,agent_name')
                ->from('agent')
                ->where($where)
                ->limit($limit)
                ->offset($offset)
                ->query();
        
        //echo $this->db()->lastSQL();
       
        $info = array(
            'lists'=>$data,
            'count' => $count['cot'],
        );
        return $info;
    }
    /**
     * 获取单个显示信息
     * @param type $params
     * @return string
     */
   public function getView($params)
    {
        if(empty($params['agent_id']))
        {
            return '';
        }
        $where = "agent_id={$params['agent_id']}";
        $data = $this->db()->select('agent_id,agent_name')->from('agent')->where($where)->row();
         //echo $this->db()->lastSQL();
        return $data;
    }
    /**
     * 新增信息
     * @param type $params
     * @return string
     */
   public function opCreate($params)
    { 
        $data = array(
             'agent_name' => isset($params['agent_name']) ? $params['agent_name'] : '',
             'logo' => isset($params['logo']) ? $params['logo'] : '',
             'qrcode' => isset($params['qrcode']) ? $params['qrcode'] : '',
             'mobile' => isset($params['mobile']) ? $params['mobile'] : '',
             'description' => isset($params['description']) ? $params['description'] : '',
            'created' => time(),
            'modified' => time(),
            'deleted' => 1,
        );
         try {
             $result = $this->db()->insert('agent')->cols($data)->query();
//              echo $this->db()->lastSQL();
              return $result;
         } catch (\Exception $ex) {
             return '系统错误';
         }
    }
    /**
     * 更新信息
     * @param array $params
     * @return string
     */
    public function opUpdate($params)
    {
    
         $params['modified'] = time();
         $where = array("agent_id=".$params['agent_id']);
         unset($params['agent_id']);
          
        try {
            $result = $this->db()->update('agent')->cols($params)->where($where)->query();
//            echo $this->db()->lastSQL();
          return $result;
        } catch (\Exception $ex) {
            
            return '系统错误';
        }
    }
   /**
    * 软删除
    * @param type $params
    * @return string
    */
    function opDelete($params)
    {
         $params['modified'] = time();
         $params['deleted'] = 3;
         $where = array("agent_id=".$params['agent_id']);
         unset($params['agent_id']);
           
        try {
            $result = $this->db()->update('agent')->cols($params)->where($where)->query();
//            echo $this->db()->lastSQL();
          return $result;
        } catch (\Exception $ex) {
            
            return '系统错误';
        }
    }

}