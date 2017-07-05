<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/22
 * Time: 14:15
 * 用户参与表
 */


namespace Webadmin\Models;


class  PrizeSendLogModel  extends BaseModel{

    private $db_default;
    private $files=['prize_send_log_id','winning_id','shake_id','post_data','request_url','response_msg','requst_time','status'];
    public function __construct(){
        parent::__construct();
        $this->db_default = $this->db('default');

    }


    /**
     * 获取列表数据
     */

    public function getList($params)
    {

        $new_db = clone $this->db();
        $where = array();
        $query = $this->db()->select('*')
            ->from('prize_send_log');
        if (isset($params['where'])) {
            foreach ($params['where'] as $key => $row) {
                if (in_array($key, $this->files)) {
                    if(is_array($row)){
                        $where[] = $key . ' '.$row[0] .'' . $row[1];
                    }else{
                        if(!is_numeric($row)){
                            $row="'".$row."'";
                        }
                        $where[] = $key . ' = ' . $row;
                    }
                }
            }
            $query = $query->where($where);
        }

        if (isset($params['page']) && isset($params['limit'])) {
            $count_query = $new_db->select('count(*) as cot')
                ->from('prize_send_log');
            if ($where) {
                $count_query = $count_query->where($where);
            }

            $count = $count_query->row();

            $offset = ($params['page']-1) * $params['limit'];
            $query = $query->limit($params['limit'])
                ->offset($offset);

            $info['page']['per_page'] = $params['limit'];
            $info['page']['total_count'] = $count['cot'];
            $info['page']['current_page'] = $params['page'];
            $info['page']['total_page'] =ceil($count['cot']/$params['limit']);
        }
        $data = $query->query();


        $info['lists'] = $data;

        return $info;
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
        $where[]='prize_send_log_id='.$id;
        foreach ($data as $key=>$row){
            if(in_array($key,$this->files)){
                $save[$key] = $row;
            }
        }
        $result = $this->db_default->update('prize_send_log')->cols($save)->where($where)->query();
        return $result;
    }

}