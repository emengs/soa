<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/19
 * Time: 18:25
 */
namespace Console\Models;

class TimeModel extends BaseModel
{

    private $Redis_queue_name = 'shake_queue';
    private $RedisTaskCurrent = 'shake_task_current';
    private $RedisTaskAll = 'shake_task_all';
    private $RedisIPAll = 'shake_ip_all';
    /**
     * @param $key
     */
    public function getShakeWinningList(){
        return $this->RedisCache($this->Redis_queue_name)->getWinnerQueueList();
    }

    /**
     * 获取任务的集合 当前正在执行的
     */
    public function getTaskList(){
      return  $this->RedisCache($this->RedisTaskPrefix)->getTask($this->RedisTaskCurrent);
    }

    /**
     * 添加任务的集合
     */
    public function addTaskList($value){
        return $this->RedisCache($this->RedisTaskPrefix)->addTask($this->RedisTaskCurrent,$value);
    }

    /**
     * 删除任务的集合元素
     */
    public function delTaskList($value){
        return  $this->RedisCache($this->RedisTaskPrefix)->remTask($this->RedisTaskCurrent,$value);
    }

    /**
     * 获取所有配置的计划任务
     */
    public function getTaskAllList(){
        return  $this->RedisCache($this->RedisTaskPrefix)->getTask($this->RedisTaskAll);
    }

    /**
     * 添加任务的集合
     */
    public function setTaskAllList($value){
        return $this->RedisCache($this->RedisTaskPrefix)->addTask($this->RedisTaskAll,$value);
    }

    /**
     * 获取未执行的队列
     */
    public function diffTask(){
        $key1 = $this->RedisTaskAll;
        $key2= $this->RedisTaskCurrent;
        return  $this->RedisCache($this->RedisTaskPrefix)->diffTask($key1,$key2);
    }

    /**
     * 保存Ip 地址
     */
    public function setIPSetting($ip){
        $key = $this->RedisIPAll;
        return $this->RedisCache($this->RedisPrefixSetting)->setIPSetting($key,$ip);
    }

    /**
     * 获取ip地址的排序
     */
    public function getIPSettingRank($ip){
        $key = $this->RedisIPAll;
        return $this->RedisCache($this->RedisPrefixSetting)->getIPRank($key,$ip);
    }

    /**
     * 删除ip地址
     */
    public function DelIPSetting($ip){
        $key = $this->RedisIPAll;
        return $this->RedisCache($this->RedisPrefixSetting)->delIPSetting($key,$ip);
    }




}