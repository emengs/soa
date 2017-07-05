<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/8
 * Time: 16:59
 * 摇一摇中奖记录
 */
namespace Console\Models;

class ShakeWinningModel extends BaseModel
{
    private $files=['winning_id','shake_id','prize_id','play_record_id','channel_id','user_id','user_name','level','prize_type','prize_name','prize_send_status','prize_send_result','create_time','update_time','requests_num'];

    /**
     * 获取列表数据
     */
    public function getList($params)
    {

        $new_db = clone $this->db();
        $where = array();
        $query = $this->db()->select('*')
            ->from('shake_winning');
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
        $query->orderByDESC(['winning_id']);
        if (isset($params['page']) && isset($params['limit'])) {
            $count_query = $new_db->select('count(*) as cot')
                ->from('shake_winning');
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
     * 新增获奖记录
     */
    public function add($params){

        $this->db()->beginTrans();
        $winning_data = array(
            'channel_id' => isset($params['channel_id']) ? $params['channel_id'] : 0,
            'shake_id' => isset($params['shake_id']) ? $params['shake_id'] : "",
            'prize_id' => isset($params['prize_id']) ? $params['prize_id'] : "",
            'user_name' => isset($params['user_name']) ? $params['user_name'] : "",
            'user_id' => isset($params['user_id']) ? $params['user_id'] : "",
            'level' => isset($params['level']) ? $params['level'] : "",
            'prize_type' => isset($params['prize_type']) ? $params['prize_type'] : "",
            'prize_name' => isset($params['prize_name']) ? $params['prize_name'] : "",
            'create_time' =>time(),
            'update_time'=>time()
        );

        $prize_sql  = " UPDATE shake_prize SET `prize_used_nums`=`prize_used_nums`+ 1 WHERE prize_id={$params['prize_id']} ";

        try{
            $result = $this->db()->insert('shake_winning')->cols($winning_data)->query();

            if(!$result){
                throw new Exception('aaaa') ;
            }

            $this->db()->query($prize_sql);

            $this->db()->commitTrans();
            return $result;

        }catch (Exception $e) {

            $this->db()->rollBackTrans();
            throw $e;
        }
    }
    /**
     * 修改
     */
    public function Edit($savedata,$where){
        //修改活动
        $new_where=[];
        foreach ($where as $key => $row) {
            if (in_array($key, $this->files)) {
                if(!is_numeric($row)){
                    $row="'".$row."'";
                }
                $new_where[] = $key . ' = ' . $row;
            }
        }

        $rs=  $this->db()->update('shake_winning')->cols($savedata)->where($new_where)->query();
        return $rs;
    }

    /**
     * 获取中奖缓存
     */
    public function getUserDrawdata($shake_id,$key,$filed){
       return $this->RedisCache($this->Redis_prefix_name.$shake_id)->getUserDrawdata($key,$filed);
    }


    /**
     * 清除中奖缓存
     */
    public function DelUserDrawdata($shake_id,$key,$filed){
        return  $this->RedisCache($this->Redis_prefix_name.$shake_id)->delUserDrawdata($key,$filed);
    }


    /**
     * 获取设置的总访问次数
     */
    public function getVisitsMax()
    {
        return $this->RedisCache($this->RedisPrefixSetting)->getCutNum('visits_max');
    }


    /**
     * 设置的总访问次数
     */
    public function setVisitsMax($num)
    {
        return $this->RedisCache($this->RedisPrefixSetting)->SetCutNum('visits_max',intval($num));
    }


    /**
     * 获取摇奖排队伐值
     */
    public function getQueue()
    {
        return $this->RedisCache($this->RedisPrefixSetting)->getCutNum('queue_max');
    }


    /**
     * 设置摇奖排队伐值
     */
    public function setQueueMax($num)
    {
        return $this->RedisCache($this->RedisPrefixSetting)->SetCutNum('queue_max',intval($num));
    }
}