<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/7
 * Time: 10:52
 * 摇一摇活动
 */

namespace Weixin\Models;


class  ShakeModel  extends BaseModel{

    private $db_default;
    private $files=['shake_id','shop_id','title','start_time','end_time','more_type','more_num','drawn_type','valid_distance','addr','longitude','latitude','created','description','rule','status'];
    public function __construct(){
        parent::__construct();
        $this->db_default = $this->db('default');

    }


    /**
     * 获取活动列表
     */
    public function getList(){

        $data = $this->db_default->select('*')->from('shake')->where()->query();
        return $data;
    }

    /**
     * 获取活动详情
     */
    public function getOne($where){

       $query = $this->db_default->select('*')->from('shake');
        if($where){
            $new_where=[];
            foreach ($where as $key=>$row){
                if(in_array($key,$this->files)){
                    $new_where[]=$key.' = '.$row;
                }
            }
           
            $query=  $query->where($new_where);
	    
        }
        $data=$query->row();

        return $data;

    }

    /**
     * 获取活动详情缓存
     */
    public function getShakeData($shake_id,$files=''){
       return $this->RedisCache($this->Redis_prefix_name.$shake_id)->getShakeData($shake_id,$files);
    }


    /**
     * 获取当前开启的活动详情
     */
    public function getShakeActivedData(){
        return $this->RedisCache($this->RedisPrefixSetting)->getShakeActivedData();
    }




}