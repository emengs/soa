<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/22
 * Time: 14:15
 * 用户参与表
 */


namespace Webadmin\Models;


class  UserPlayRecordModel  extends BaseModel{

    private $db_default;
    private $files=['play_record_id','shake_id','channel_id','user_id','user_name','is_win','create_time','update_time'];
    public function __construct(){
        parent::__construct();
        $this->db_default = $this->db('default');

    }



    /**
     * 添加
     */
    public function add($params){

        $winning_data = array(
            'shake_id' => isset($params['shake_id']) ? $params['shake_id'] : 0,
            'channel_id' => isset($params['channel_id']) ? $params['channel_id'] : 0,
            'user_id' => isset($params['user_id']) ? $params['user_id'] :'',
            'user_name' => isset($params['user_name']) ? $params['user_name'] :0,
            'is_win' => isset($params['is_win']) ? $params['is_win'] :0,
            'create_time' => time(),
            'update_time' =>0,
        );

        $result = $this->db_default->insert('user_play_record')->cols($winning_data)->query();

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
        $save['update_time']=time();
        $result = $this->db_default->update('user_play_record')->cols($save)->where($where)->query();
        return $result;
    }

    /**
     * 参与记录取出队列
     */
    public function pullUserPlayLog(){
        $key = 'user_play_log';
      return  $this->RedisCache($this->RdisQueuePrefix)->pullRecordData($key);
    }


}