<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/7
 * Time: 16:03
 * 摇一摇奖品
 */

namespace Weixin\Models;

use Exception;

class ShakeWinningModel extends BaseModel
{


    private $db_default;
    private $files=['winning_id','shake_id','prize_id','play_record_id','channel_id','user_id','user_name','level','prize_type','prize_name','prize_send_status','prize_send_result','create_time','update_time','requests_num'];
    private $RedisDrawKeyName ='shake_winner_queue';
    public function __construct()
    {
        parent::__construct();
        $this->db_default = $this->db('default');

    }

    /**
     * 获取获奖列表
     */
    public function getList($where)
    {

        $query = $this->db_default->select('*')->from('shake_winning');
        if ($where) {
            $new_where = [];
            foreach ($where as $key => $row) {
                if (in_array($key, $this->files)) {
                    if (!is_numeric($row)) {
                        $row = "'" . $row . "'";
                    }
                    $new_where[] = $key . ' = ' . $row;
                }
            }
            $query = $query->where($new_where);
        }
        try {
            $data = $query->query();
        } catch (Exception $ex) {
            return $ex;
        }

        return $data;

    }

    /**
     * 新增获奖记录
     */
    public function add($params)
    {

        $this->db_default->beginTrans();
        $winning_data = array(
            'shop_id' => isset($params['shop_id']) ? $params['shop_id'] : 0,
            'shake_id' => isset($params['shake_id']) ? $params['shake_id'] : "",
            'prize_id' => isset($params['prize_id']) ? $params['prize_id'] : "",
            'play_record_id' => isset($params['play_record_id']) ? $params['play_record_id'] : 0,
            'nick' => isset($params['nick']) ? $params['nick'] : "",
            'openid' => isset($params['openid']) ? $params['openid'] : "",
            'level' => isset($params['level']) ? $params['level'] : "",
            'prize_type' => isset($params['prize_type']) ? $params['prize_type'] : "",
            'prize_title' => isset($params['prize_title']) ? $params['prize_title'] : "",
            'created' => time(),
        );

        $prize_sql = " UPDATE shake_prize SET `issued_nums`=`issued_nums`+ 1 WHERE prize_id={$params['prize_id']} ";

        try {
            $result = $this->db_default->insert('shake_winning')->cols($winning_data)->query();

            if (!$result) {
                throw new Exception('新增失败');
            }

            $this->db_default->query($prize_sql);

            $this->db_default->commitTrans();
            return true;

        } catch (Exception $e) {

            $this->db_default->rollBackTrans();
            throw $e;
        }
    }

    /**
     * 查询数量
     */

    public function getSum($where)
    {

        $query = $this->db_default->select('count(*) as count')->from('shake_winning');
        if ($where) {
            $new_where = [];
            foreach ($where as $key => $row) {
                if (is_array($row)) {

                    if (!is_numeric($row[1])) {
                        $row[1] = "'" . $row[1] . "'";
                    }
                    $new_where[] = $key . ' ' . $row[0] . ' ' . $row[1];
                } else {
                    if (!is_numeric($row)) {
                        $row = "'" . $row . "'";
                    }
                    $new_where[] = $key . ' = ' . $row;
                }
            }

            $query = $query->where($new_where);
        }
        $data = $query->row();
        return $data['count'];

    }


    /**
     * 插入队列
     */
    public function AddCacheList($params)
    {

        $prizeModel = new ShakePrizeModel();
        $shake_id 	= $params['shake_id'];
        $prize_id 	= $params['prize_id'];
        $shop_id 	= $params['channel_id'];
        $open_id 	= $params['openid'];
        $uid 		= $params['uid'];
        $nick 		= $params['nick'];
        $prize_type = $params['prize_type'];
        $business_prize_id = $params['prize_out_id'];
        $params['create_time'] = time();
        $uuid 		= $this->getUUID();
		try{
	        //增加用户抽奖次数
	       $UserDraw_rs= $this->incrUserDrawNum($shake_id, $open_id);
            \Log4p::info(['type' => 'shakewinningmodel/addcachelist', 'request' => ['shake_id' => $shake_id,'open_id'=>$open_id], 'response' => ['result' =>$UserDraw_rs]]);
	        //保存参与类型记录
	        $UserPlayRecordModel = new UserPlayRecordModel();
	        $typekey = $shop_id.'_'.$uid;
	        $type_num = $UserPlayRecordModel->getUserPrizeAll($shake_id,$typekey,$prize_type);
	        $UserPlayRecordModel->setUserPrizeAll($shake_id,$typekey,$prize_type,1+intval($type_num));
	
	        //如果抽到是“未中奖”
	        if ($prize_type == 3) {
	            $UserPlayRecordModel = new UserPlayRecordModel();
	            $data = [
	                'shake_id' 	=> $shake_id,
	                'channel_id' 	=> $shop_id,
	                'user_id' 	=> $open_id,
	                'user_name' 		=> $nick,
	                'prize_id' 	=> $prize_id
	            ];
	            // 保存用户参与记录到缓存
	         $UserPlayRecord_rs=   $UserPlayRecordModel->setPlayRecord($data);
                \Log4p::info(['type' => 'shakewinningmodel/addcachelist', 'request' => ['data' => $data], 'response' => ['result' =>$UserPlayRecord_rs]]);
	        } else {
	            //减少奖品库存
	            $prizeModel->decrPrizeNum($shake_id, $prize_id);
	            //减少总库存
	            $prizeModel->decrCountNum($shake_id);
	            //存入奖品发放队列
	            $list_data = [
	                'uid' 		=> $uid,
	                'channel_id' 	=> $shop_id,
	                'shake_id' 	=> $shake_id,
	                'uuid' 		=> $uuid,
	                'prize_out_id' => $business_prize_id
	            ];
	            //存入发奖队列
	           $WinnerQueueList_rs= $this->RedisCache($this->RdisQueuePrefix)->setWinnerQueueList($list_data);
                \Log4p::info(['type' => 'WinnerQueueList', 'request' => ['data' => $list_data], 'response' => ['result' =>$WinnerQueueList_rs]]);
	            //存入中奖记录表
	          $UserDrawdata= $this->RedisCache($this->Redis_prefix_name . $shake_id)->setUserDrawdata($shop_id . '_' . $uid, $uuid, $params);
                \Log4p::info(['type' => 'ShakeUserPlayRecord', 'request' => ['key'=>$shop_id . '_' . $uid], 'response' => ['result' =>$UserDrawdata]]);
	
	        }
	        return true;
		}catch (\Exception $e){
			// 写错误日志
			return false;
		}
    }

    private function getUUID()
    {
        $mictime = intval(microtime() * 1000000);

        $strlen = strlen($mictime);

        if (strlen($mictime) < 7) {
            $mictime = $mictime * pow(10, 7 - $strlen);
        } else {
            $mictime = substr($mictime, 0, 7);
        }

        return time() . $mictime;
    }

    /**
     * 查询用户所有中奖记录(缓存)
     */
    public function getUserWinnerCache($shake_id,$shop_id,$uid){
       return  $this->RedisCache($this->Redis_prefix_name . $shake_id)->getUserDrawdata($shop_id . '_' . $uid);
    }


    /**
     * 增加用户抽奖次数
     */
    public function incrUserDrawNum($shake_id, $open_id)
    {
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->incrUserDrawNums($shake_id, 1, $open_id);
    }

    /**
     * 获取用户抽奖次数
     */
    public function getUserDrawNum($shake_id, $open_id)
    {
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->getUserDrawNums($shake_id, $open_id);
    }


    /**
     * 获取设置的总访问次数
     */
    public function getVisitsMax()
    {
        return $this->RedisCache($this->RedisPrefixSetting)->getCutNum('visits_max');
    }

    /**
     * 获取访问总数
     * @param $shake_id
     * @return mixed
     */
    public function incrVisitsTotal($shake_id){
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->IncrCutNum('visits_total');
    }


    /**
     * 获取当前访问人数
     * @param $shake_id
     * @return mixed
     */
    public function getVisitsCurrent($shake_id){
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->getCutNum('visits_current');
    }

    /**
     * 增加当前访问人数
     * @param $shake_id
     * @return mixed
     */
    public function incrVisitsCurrent($shake_id){
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->IncrCutNum('visits_current');
    }

    /**
     * 减少当前访问人数
     * @param $shake_id
     * @return mixed
     */
    public function decrVisitsCurrent($shake_id){
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->DecrCutNum('visits_current');
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
        return $this->RedisCache($this->RedisPrefixSetting)->IncrCutNum('queue_max',$num);
    }

    /**
     * 设置入口伐值
     */
    public function setInMax($num)
    {
        return $this->RedisCache($this->RedisPrefixSetting)->IncrCutNum('visits_max',$num);
    }

    /**
     * 获取队列长度
     */
    public function getQueueSize(){
        return $this->RedisCache($this->RdisQueuePrefix)->getQueueSize($this->RedisDrawKeyName);
    }



}