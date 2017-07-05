<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/19
 * Time: 18:25
 */
namespace Webadmin\Models;

class TimeModel extends BaseModel
{

    private $RedisTaskAll = 'shake_task_all';
    /**
     * @param $key
     */


    /**
     * 获取所有配置的计划任务
     */
    public function getTaskAllList(){
        return  $this->RedisCache($this->RedisTaskPrefix)->getTask($this->RedisTaskAll);
    }

    /**
     *  添加配置的计划任务(所有的)
     */
    public function addTaskAllList($value){
        return  $this->RedisCache($this->RedisTaskPrefix)->addTask($this->RedisTaskAll,$value);
    }

    /**
     * 获取所有配置的计划任务
     */
    public function delTaskAllList($value){
        return  $this->RedisCache($this->RedisTaskPrefix)->remTask($this->RedisTaskAll,$value);
    }


}