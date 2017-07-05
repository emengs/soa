<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/22
 * Time: 14:15
 * 用户参与表
 */


namespace Weixin\Models;


class  UserPlayRecordModel  extends BaseModel{

    private $db_default;
    private $files=['play_record_id','shake_id','shop_id','open_id','nick','is_win','create_time','update_time'];
    public function __construct(){
        parent::__construct();
        $this->db_default = $this->db('default');

    }



    /**
     * 添加
     */
    public function add($params){
        $winning_data = array(
            'shake_id' => isset($params['shake_id']) ? $params['shake_id'] : " ",
            'shop_id' => isset($params['shop_id']) ? $params['shop_id'] : " ",
            'open_id' => isset($params['open_id']) ? $params['open_id'] :0,
            'nick' => isset($params['nick']) ? $params['nick'] :0,
            'is_win' => isset($params['is_win']) ? $params['is_win'] :0,
            'create_time' => time(),
            'update_time' =>0,
        );

        $result = $this->db_default->insert('user_play_record')->cols($winning_data)->query();

        return $result;
    }


    /**
     * 未中奖进入参与记录队列
     */
    public function setPlayRecord($value){
        $key = 'user_play_log';
        return $this->RedisCache($this->RdisQueuePrefix)->pushPlayRecordData($key,$value);

    }

    /**
     * 将所有抽奖的类型加入(hash)
     */
    public function setUserPrizeAll($shake_id,$key,$files,$value){
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->setUserPrizeAll($key,$files,$value);
    }

    /**
     * 获取奖品类型的数量(hash)
     */
    public function getUserPrizeAll($shake_id,$key,$files){
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->getUserPrizeAll($key,$files);
    }

    /**
     *  判断奖品类型是否存在集合中(hash)
     */
    public function isInUserPrizeAll($shake_id,$key,$files){
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->isInUserPrizeAll($key,$files);
    }



}