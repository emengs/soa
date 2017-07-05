<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/7
 * Time: 16:03
 * 摇一摇奖品
 */

namespace Weixin\Models;

class ShakePrizeModel extends BaseModel{

	const PRIZE_TYPE_REDBOX = 2;
    private $db_default;
    private $files=['prize_id','shop_id','shake_id','level','prize_type','title','nums','issued_nums','winning_rate','pic','created','is_del'];
    public function __construct(){
        parent::__construct();
        $this->db_default = $this->db('default');

    }

    /**
     * 获取奖品列表
     */
    public function getList($where){
        $query = $this->db_default->select('*')->from('shake_prize');
        if($where){
            $new_where=[];
            foreach ($where as $key=>$row){
                if(in_array($key,$this->files)){
                    if(is_array($row)){
                        $new_where[]=$key.' '.$row[0].' '.$row[1];
                    }else{
                        $new_where[]=$key.' = '.$row;
                    }

                }
            }

            $query=  $query->where($new_where);
        }
        $data=$query->query();

        return $data;

    }

    /**
     * 获取单个奖品信息
     */
    public function getOne($params){
        $where = array();
        if (isset($params['where'])) {
            foreach ($params['where'] as $key => $row) {
                if (in_array($key, $this->files)) {
                    if(is_array($row)){
                        $where[]=$key.' '.$row[0].' '.$row[1];
                    }else{
                        $where[]=$key.' = '.$row;
                    }
                }
            }
        }
        $data = $this->db()->select('*')
            ->from('shake_prize')
            ->where($where)
            ->row();
        return $data;
    }

    /**
     * 从缓存获取所有奖品数量
     */
    public function getPrizesNum($shake_id){
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->getPrizeNum($shake_id);
    }
    /**
     * 减少商品库存
     */
    public function decrPrizeNum($shake_id,$prize_id){
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->incrPrizeNum($shake_id,-1,$prize_id);
    }

    /**
     * 获取总库存
     */
    public function getCountNum($shake_id){
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->getPrizeCountNum($shake_id);
    }

    /**
     * 减少总库存
     */
    public function decrCountNum($shake_id){

        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->decrPrizeCountNum($shake_id);
    }

    /**
     * 获取缓存奖品详细信息
     */
    public function getPrizeInfo($shake_id,$files=''){
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->getPrizeData($shake_id,$files);
    }


    /**
     * 从队列中拉取一个奖品
     */
    public function PrizePullList($shake_id,$type){
        $key = $shake_id.'_'.$type;
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->PrizePullList($key);
    }


    /**
     * 获取奖品队列长度
     */
    public function PrizePullListLen($shake_id,$type){
        $key = $shake_id.'_'.$type;
        return $this->RedisCache($this->Redis_prefix_name.$shake_id)->PrizePullListLen($key);
    }


}