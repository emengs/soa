<?php

namespace Weixin\Models;

use Weixin\Models\BaseModel;
/**
 *  测试控制器
 * @author guibinYu
 */
class TestModel   extends \Weixin\Models\BaseModel
{
    /**
     * 获取列表数据，
     * @param type $params
     * @return type
     */
    public  function getList($params)
    {
       $this->db_default->beginTrans();
        $qrcode_id = isset($params['qrcode_id']) ? $params['qrcode_id'] : "";
        $data = array(
            'agent_id' => isset($params['agent_id']) ? $params['agent_id'] : "",
            'qrcode_id' => isset($params['qrcode_id']) ? $params['qrcode_id'] : "",
            'openid' => isset($params['openid']) ? $params['openid'] : "",
            'is_subscribe' => isset($params['is_subscribe']) ? $params['is_subscribe'] : "",
            'created' => time(),
            'modified' => time(),
            'deleted' => 1,
        );
        $sql  = " UPDATE qrcode SET `scan_amount`=`scan_amount`+1 WHERE qrcode_id={$qrcode_id} ";
        try {
            $this->xxx($params);
                    
             $result1 = $this->db_default->insert('scan_qrcode_record')->cols($data)->query();
             $result2 = $this->db_default->query($sql);
             if($result1 && $result2)
             {
                 $this->db_default->commitTrans();
                 return  $result1;
             }
             else
             {
                 $this->db_default->rollBackTrans();
                 return false;
             }
              
         } catch (Exception $ex) {
             return false;
         }
    }
    
     /**
     * 获取列表数据，
     * @param type $params
     * @return type
     */
    public  function xxx($params)
    {
        
//       $this->db_default->beginTrans();
        $qrcode_id = isset($params['qrcode_id']) ? $params['qrcode_id'] : "";
//        $data = array(
//            'agent_id' => isset($params['agent_id']) ? $params['agent_id'] : "",
//            'qrcode_id' => isset($params['qrcode_id']) ? $params['qrcode_id'] : "",
//            'openid' => isset($params['openid']) ? $params['openid'] : "",
//            'is_subscribe' => isset($params['is_subscribe']) ? $params['is_subscribe'] : "",
//            'created' => time(),
//            'modified' => time(),
//            'deleted' => 1,
//        );
        $sql  = " UPDATE qrcode SET `scan_amount`=`scan_amount`+1 WHERE qrcode_id={$qrcode_id} ";
//        try {
//       
//             $result1 = $this->db_default->insert('scan_qrcode_record')->cols($data)->query();
             $result2 = $this->db_default->query($sql);
//             if($result1 && $result2)
//             {
//                 $this->db_default->commitTrans();
//                 return  $result1;
//             }
//             else
//             {
//                 $this->db_default->rollBackTrans();
//                 return false;
//             }
//              
//         } catch (Exception $ex) {
//             return false;
//         }
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
        $data = $this->db_default->select('agent_id,agent_name')->from('agent')->where($where)->row();
         //echo $this->db_default->lastSQL();
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
             $result = $this->db_default->insert('agent')->cols($data)->query();
//              echo $this->db_default->lastSQL();
              return $result;
         } catch (Exception $ex) {
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
            $result = $this->db_default->update('agent')->cols($params)->where($where)->query();
//            echo $this->db_default->lastSQL();
          return $result;
        } catch (Exception $ex) {
            
            return '系统错误';
        }
    }
   /**
    * 软删除
    * @param type $params
    * @return string
    */
    public function opDelete($params)
    {
         $params['modified'] = time();
         $params['deleted'] = 3;
         $where = array("agent_id=".$params['agent_id']);
         unset($params['agent_id']);
           
        try {
            $result = $this->db_default->update('agent')->cols($params)->where($where)->query();
//            echo $this->db_default->lastSQL();
          return $result;
        } catch (Exception $ex) {
            
            return '系统错误';
        }
    }

}