<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/22
 * Time: 14:15
 * 用户参与表
 */


namespace Weixin\Models;


class  PrizeSendLogModel  extends BaseModel{

    private $db_default;
    private $files=['prize_send_log_id','winning_id','shake_id','post_data','request_url','response_msg','requst_time'];
    public function __construct(){
        parent::__construct();
        $this->db_default = $this->db('default');

    }



    /**
     * 添加
     */
    public function add($params){
        $winning_data = array(
            'winning_id' => isset($params['winning_id']) ? $params['winning_id'] : 0,
            'shake_id' => isset($params['shake_id']) ? $params['shake_id'] :0,
            'post_data' => isset($params['post_data']) ? $params['post_data'] :' ',
            'request_url' => isset($params['request_url']) ? $params['request_url'] :' ',
            'response_msg' => isset($params['response_msg']) ? $params['response_msg'] :' ',
            'requst_time' => time(),

        );

        $result = $this->db_default->insert('prize_send_log')->cols($winning_data)->query();

        return $result;
    }

    /**
     * 修改
     */
    public function edit($data,$id){
        $save=[];
        $where['prize_send_log_id']=$id;
        foreach ($data as $key=>$row){
            if(in_array($key,$this->files)){
                $save[$key] = $row;
            }
        }
        $result = $this->db_default->update('agent')->cols($save)->where($where)->query();
        return $result;
    }

}