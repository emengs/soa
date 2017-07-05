<?php
namespace System\Libraries;

use System\Core\StandardRedis;

/**
 *  摇一摇
 * @author guibinYu
 */
class ShakeRedis extends \System\Core\StandardRedis
{

    private $ShakeAdminToken = 'shake_admin_token_';
    private $randomKey = "randomKey_";
    private $ShakeSdata = "shake_data_";
    private $PrizeData = "prize_info_";
    private $PrizeNum = "prize_num_";
    private $ShakewebToken = "shake_web_token_";
    private $PrizeCountNum = "shake_prize_total_num_";
    private $CutNum = "shake_";
    private $UserDrawNums = "user_draw_nums_";
    private $UserDrawData = "winner_";
    private $WinnerQueue = "shake_winner_queue";
    private $access_token = "open_";
    private $PlayRecordData = "user_play_log_";
    private $UserPrizeTypeAll = "user_prize_all_";
    private $ShakeActivedData = "shake_actived_data";
    private $PrizeTypeeList = "shake_prize_type_list_";


    public function __construct($config)
    {

        parent::__construct($config);
    }


    /**
     * 开启事务
     */
    public function Shakemulti(){
       return $this->multi();
    }

    /**
     * 开始执行
     */
    public function Shakeexec(){
        return $this->exec();
    }

    /**
     * 执行
     */

    /**
     * 存放admin_token
     */
    public function setWebAdminToken($key, $value)
    {
        $key = $this->ShakeAdminToken . $key;
        $result = $this->setex($key, 7200, $value);
        return $result;
    }

    /**
     * 获取admin_token
     */
    public function getWebAdminToken($key)
    {
        $key = $this->ShakeAdminToken . $key;
        $result = $this->get($key);
        return $result;
    }

    /**
     * 设置图形验证码
     * @param string $key
     * @param type $value
     * @return type
     */
    public function setRandom($key, $value)
    {
        $key = $this->randomKey . $key;
        $result = $this->setex($key, 30, $value);
        return $result;
    }

    /**
     *  获取图形验证码
     * @param string $key
     * @return type
     */
    public function getRandom($key)
    {
        $key = $this->randomKey . $key;
        $result = $this->get($key);
        return $result;
    }

    /**
     * 删除管理后台token
     * @param string $key
     * @return type
     */
    public function delWebadminToken($key)
    {
        $key = $this->ShakeAdminToken . $key;
        $result = $this->delStr($key);
        return $result;
    }


    /**
     * 保存活动信息
     * @param $key
     * @param $field
     * @param int $value
     * @return mixed
     */
    public function setShakeData($key, $field, $value = 0)
    {
        $key = $this->ShakeSdata . $key;
        if ($value) {
            $result = $this->hSet($key, $field, $value);


        } else {
            $result = $this->hMset($key, $field);
        }
        return $result;

    }

    /**
     * 获取活动信息
     * @param $key
     * @param string $field
     * @return array
     */
    public function getShakeData($key, $field = '')
    {
        $key = $this->ShakeSdata . $key;
        $result = array();
        if ($field) {
            $result = $this->hGet($key, $field);
        } else {

            $result = $this->hGetAll($key);
        }
        return $result;

    }

    /**
     * 将奖品录入队列
     */
    public function PrizePushList($key,$value){
        $key = $this->PrizeTypeeList.$key;
        $result = $this->rPush($key, $value);
        return $result;
    }

    /**
     * 将奖品取出队列
     */
    public function PrizePullList($key){

        $key = $this->PrizeTypeeList.$key;
        $result = $this->lPop($key);
        return $result;

    }

    /**
     * 删除奖品队列
     */
    public function delPrizeTypeList($key){
        $key = $this->PrizeTypeeList.$key;
        $result = $this->del($key);
        return $result;
    }


    /**
     * 获取奖品队列长度
     */

    public function PrizePullListLen($key){
         $key = $this->PrizeTypeeList.$key;
           return $this->lLen($key);
}


    /**
     * 保存奖品信息
     * @param $key
     * @param $field
     * @param int $value
     * @return mixed
     */
    public function setPrizeData($key, $field, $value = 0)
    {
        $key = $this->PrizeData . $key;
        if ($value) {
            $result = $this->hSet($key, $field, $value);
        } else {
            $result = $this->hMset($key, $field);
        }
        return $result;
    }
    /**
     * 清空奖品信息
     * @param $key
     * @param $field
     * @param int $value
     * @return mixed
     */
    public function delPrizeData($key)
    {
        $key = $this->PrizeData . $key;

            $result = $this->del($key);

        return $result;
    }

    /**
     * 获取活动单个奖品信息
     * @param $key
     * @param string $field
     * @return array
     */
    public function getPrizeData($key, $field = '')
    {
        $key = $this->PrizeData . $key;
        $result = array();
        if ($field) {

            $result = $this->hGet($key, $field);
        } else {
            $result = $this->hGetAll($key);
        }
        return $result;
    }

    /**
     * 保存活动单个奖品数量
     * @param $key
     * @param $field
     * @param int $value
     * @return mixed
     */
    public function incrPrizeNum($key, $sore = 0, $value)
    {
        $key = $this->PrizeNum . $key;
        $value = intval($value);
        $result = $this->zIncrBy($key, $sore, $value);
        return $result;
    }

    /**
     * 获取活动奖品数量
     * @param $key
     * @param string $field
     * @return array
     */
    public function getPrizeNum($key, $sore = '')
    {
        $key = $this->PrizeNum . $key;
        if ($sore == '') {
            //返回所有
            $result = $this->zRange($key, 0, -1);
        } else {
            $result = $this->zScore($key, $sore);
        }

        return $result;
    }

    /**
     * 删除活动商品key
     * @param $key
     * @param $field
     * @param int $value
     * @return mixed
     */
    public function delPrizeNum($key)
    {
        $key = $this->PrizeNum . $key;

        $result = $this->del($key);
        return $result;
    }



    /**
     * 删除所有活动商品key
     * @param $key
     * @param $field
     * @param int $value
     * @return mixed
     */
    public function delPrizeCountNum($key)
    {
        $key = $this->PrizeCountNum . $key;

        $result = $this->del($key);
        return $result;
    }

    /**
     * 增加活动所有商品数量（负数为减少）
     * @param $key
     * @param $field
     * @param int $value
     * @return mixed
     */
    public function incrPrizeCountNum($key, $value = 0)
    {
        $key = $this->PrizeCountNum . $key;
        $value = intval($value);
        if($value >=0){
            $result = $this->incrBy($key, $value);
        }else{
            $result =$this->decrBy($key, $value);
        }

        return $result;
    }

    /**
     * 获取活动所有商品数量
     */
    public function getPrizeCountNum($key)
    {
        $key = $this->PrizeCountNum . $key;
        $result = $this->get($key);
        return $result;
    }

    /**
     * 减少总共奖品数量
     */
    public function decrPrizeCountNum($key)
    {
        $key = $this->PrizeCountNum . $key;
        $result = $this->decr($key);
        return $result;
    }

    /**
     * 保存前端用户信息
     */
    public function setWebToken($key, $value)
    {
        $key = $this->ShakewebToken . $key;
        $result = $this->setex($key, 7200, $value);
        return $result;
    }

    /**
     * 保存前端用户信息
     */
    public function getWebToken($key)
    {
        $key = $this->ShakewebToken . $key;
        $result = $this->get($key);
        return $result;
    }

    /**
     * 设置伐值
     */
    public function SetCutNum($key, $nums = 1)
    {
        $key = $this->CutNum . $key;
        $result = $this->set($key, $nums);
        return $result;
    }


    /**
     * 增加伐值
     */
    public function incrCutNum($key, $nums = 1)
    {
        $key = $this->CutNum . $key;
        $result = $this->incrBy($key, $nums);
        return $result;
    }

    /**
     * 获取伐值
     */
    public function getCutNum($key)
    {
        $key = $this->CutNum . $key;
        $result = $this->get($key);
        return $result;
    }

    /**
     * 减去伐值
     */
    public function decrCutNum($key)
    {
        $key = $this->CutNum . $key;
        $result = $this->decr($key);
        return $result;
    }

    /**
     * 增加用户抽奖次数
     */
    public function incrUserDrawNums($key, $num, $value)
    {
        $key = $this->UserDrawNums . $key;
        $result = $this->zIncrBy($key, $num, $value);
        return $result;
    }


    /**
     * 获取用户抽奖次数
     */
    public function getUserDrawNums($key, $value)
    {
        $key = $this->UserDrawNums . $key;
        $result = $this->zScore($key, $value);
        return $result;
    }

    /**
     * 存入用户中奖记录
     */
    public function setUserDrawdata($key, $field, $value)
    {
        $key = $this->UserDrawData . $key;
        $result = $this->hSet($key, $field, $value);
        return $result;
    }

    /**
     * 取出用户中奖记录
     */
    public function getUserDrawdata($key, $field = '')
    {
        $key = $this->UserDrawData . $key;
        $result = array();
        if ($field) {
            $result = $this->hGet($key, $field);
        } else {
            $result = $this->hGetAll($key);

        }
        return $result;

    }

    /**
     * 存入用户参与记录(未中奖)(队列)
     */
    public function pushPlayRecordData($key, $value)
    {
        $result = $this->rPush($key, $value);
        return $result;
    }

    /**
     * 取出用户参与记录(未中奖)(队列)
     */
    public function pullRecordData($key)
    {
        $result = $this->lPop($key);
        return $result;
    }

    /**
     * 删除用户中奖记录(单个)
     */
    public function DelUserDrawdata($key, $field)
    {
        $key = $this->UserDrawData . $key;
        $result = $this->hDel($key, $field);
        return $result;
    }

    /**
     * 存入发奖队列
     */
    public function setWinnerQueueList($value)
    {
        $key = $this->WinnerQueue;
        $result = $this->rPush($key, $value);
        return $result;
    }

    /**
     * 取出发奖队列
     */
    public function getWinnerQueueList()
    {
        $key = $this->WinnerQueue;
        $result = $this->lPop($key);
        return $result;
    }

    /**
     * 设置商圈token
     * @param string $key
     * @return type
     */
    public function setOpenAccessToken($key, $value)
    {
        $key = $this->access_token . $key;
        $result = $this->setex($key, 7200, $value);
        return $result;
    }


    /**
     * 获取商圈token
     * @param string $key
     * @return type
     */
    public function getOpenAccessToken($key)
    {
        $key = $this->access_token . $key;
        $result = $this->get($key);
        return $result;
    }

    /**
     * 将购买奖品类型加入缓存
     */
    public function setUserPrizeAll($key, $files, $value)
    {
        $key = $this->UserPrizeTypeAll . $key;
        $result = $this->hSet($key, $files, $value);
        return $result;
    }

    /**
     * 判断该类型奖品是否购买
     */
    public function isInUserPrizeAll($key, $files)
    {
        $key = $this->UserPrizeTypeAll . $key;
        $result = $this->hExists($key, $files);
        return $result;
    }

    /**
     * 获取奖品类型的数量
     */
    public function getUserPrizeAll($key, $files)
    {
        $key = $this->UserPrizeTypeAll . $key;
        $result = array();
        if ($files) {
            $result = $this->hGet($key, $files);
        } else {
            $result = $this->hGetAll($key);
        }
        return $result;
    }

    /**
     * 获取中奖队列长度
     */
    public function getQueueSize($key)
    {
        return $this->lLen($key);
    }

    /**
     * 获取中奖队列的信息
     */
    public function getQueueList($key,$start,$end){
       $result = $this->lRange($key,$start,$end);
        return $result;
    }

    /**
     * 设置当前开启的活动详情
     */
    public function setShakeActivedData($field, $value = '')
    {
        $key = $this->ShakeActivedData;
        if ($value) {
            $result = $this->hSet($key, $field, $value);


        } else {
            $result = $this->hMset($key, $field);
        }
        return $result;
    }

    /**
     * 获取当前开启的活动详情
     */
    public function getShakeActivedData($field = '')
    {
        $key = $this->ShakeActivedData;
        $result = array();
        if ($field) {
            $result = $this->hGet($key, $field);
        } else {

            $result = $this->hGetAll($key);
        }
        return $result;

    }

    /**
     * 删除当前已开启的活动key
     */
    public function ClearShakeActivedData()
    {
        $key = $this->ShakeActivedData;
        $result = $this->del($key);
        return $result;
    }


    /**
     * 添加任务集合
     */

    public function addTask($key, $value)
    {
        $result = $this->sadd($key,$value);
        return $result;
    }


    /**
     * 获取任务集合(所有)
     */

    public function getTask($key)
    {
        $result = $this->smembers($key);
        return $result;
    }

    /**
     * 删除任务集合
     */

    public function remTask($key, $value)
    {
        $result = $this->sRem($key, $value);
        return $result;
    }

    /**
     * 取出任务结合的差集
     */
    public function diffTask($key1,$key2){
        $result = $this->sDiff($key1, $key2);
        return $result;
    }

    /**
     * 添加服务器Ip到缓存
     */
    public function setIPSetting($key,$member,$score=1){
       $result = $this->zAdd($key,$score,$member);
        return $result;
    }

    /**
     * 获取服务器IP的排序
     */
    public function getIPRank($key,$member){
        $result = $this->zRank($key,$member);
        return $result;
    }
    /**
     * 删除服务器IP设置
     */
    public function delIPSetting($key,$member){
        $result = $this->zRem($key,$member);
        return $result;
    }


}