<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/6
 * Time: 18:10
 * 摇一摇插件控制器
 */

namespace Weixin\Controllers;


use Weixin\Models\OauthModel;
use Weixin\Models\ShakePrizeModel;
use Weixin\Models\ShakeWinningModel;
use Weixin\Models\UserPlayRecordModel;

class  Shake extends Base
{
    private $ShakeModel;

    public function __construct($params)
    {
		parent::__construct ( $params );
		
		$this->ShakeModel = new \Weixin\Models\ShakeModel ();
	}
	
	/**
	 * 活动首页
	 */
	public function index() {
		$time = time ();

		// 获取当前已开启活动详情
		$rs = $this->ShakeModel->getShakeActivedData();
		$shake_id = empty ( $rs ['shake_id'] ) ? 0 : intval ( $rs ['shake_id'] );
		$WinningModel = new ShakeWinningModel ();
		if (! $rs || $time <= $rs ['start_time']) {
			// 添加总共访问次数
			$shake_id > 0 ? $WinningModel->incrVisitsTotal ( $shake_id ) : false;
			return array (
					'code' => 6,
					'msg'  => '活动未开启' 
			);
		}
		if ($rs ['end_time'] <= $time) {
			// 添加总共访问次数
			$WinningModel->incrVisitsTotal ( $shake_id );
			return array (
					'code' => 6,
					'msg'  => '活动已结束' 
			);
		}

		$WinningModel = new ShakeWinningModel ();
		// 获取活动阀值 （活动同时访问最大用户数量）
		$visitsMax = $WinningModel->getVisitsMax ();
		// 获取当前用户访问数量
		$visitsCurrent = $WinningModel->getVisitsCurrent ( $rs ['shake_id'] );
		if ($visitsCurrent+1 >= $visitsMax) {
            return array('code' => 5, 'msg' => '参加人数过多,请稍后再试');
        }

     
        return array('code' => 0, 'msg' => 'ok', 'data' => $rs);
    }

    /**
     * 加入缓存抽奖流程
     */
    public function draw()
    {

        if (!isset($this->params['shake_id'])) {
            return array('code' => 4, 'msg' => '服务器繁忙，请稍后再试');
        }
        $WinningModel = new ShakeWinningModel();
        //判断访问次数是否超出
        $shake_id = $this->params['shake_id'];
        $openid = $this->user_info['open_id'];
        $channel_id = $this->shop_info['shop_id'];
        $uid=  $this->user_info['uid'];
        $visitsMax=$WinningModel->getVisitsMax();
        $visitsCurrent = $WinningModel->getVisitsCurrent($shake_id);
        if ($visitsCurrent >= $visitsMax) {
            return array('code' => 5, 'msg' => '参加人数过多,请稍后再试');
        }

        //添加当前访问次数
        $WinningModel->incrVisitsCurrent($shake_id);
        //添加总共访问次数
        $WinningModel->incrVisitsTotal($shake_id);

        //查询当前活动缓存信息
        $rs = $this->ShakeModel->getShakeActivedData();
        $time = time();
		$stime = empty($rs['start_time']) ? 0 : intval( $rs['start_time']);
		$etime = empty($rs['end_time']) ? 0 :intval( $rs['end_time']);
        if (!$rs || $rs['shake_id'] <> $shake_id || $time <= $stime) {
            $WinningModel->decrVisitsCurrent($shake_id);
            return array('code' => 6, 'msg' => '活动未开启');
        }
        if ($etime <= $time) {
            $WinningModel->decrVisitsCurrent($shake_id);
            return array('code' => 6, 'msg' => '活动已结束');
        }
        //获取用户所在地址
        $user_addr = $this->getAddr();
        if (!$user_addr) {
            $WinningModel->decrVisitsCurrent($shake_id);
            return array('code' => 4, 'msg' => '获取定位失败,请稍后再试');
        }
        $juli = $this->getdistance($user_addr['x'], $user_addr['y'], $rs['longitude'], $rs['latitude']);
        \Log4p::info(['type' => 'shake/draw'.$shake_id.'_'.$uid, 'request' => ['file' =>__FILE__,'line'=>(string)__LINE__], 'response' => ['shake_valid_distance' =>(string)$rs['valid_distance'],'user_juli'=>(string)$juli]]);
        if($juli > intval($rs['valid_distance']) ){
           $WinningModel->decrVisitsCurrent($shake_id);
           return array('code'=>4,'msg'=>'已超出摇奖范围');
        }
        $prizeModel = new ShakePrizeModel();



        // 获取用户当前活动抽奖次数
        $user_num = $WinningModel->getUserDrawNum($shake_id, $openid);
        if ($user_num >= intval($rs['more_num'])) {
            $WinningModel->decrVisitsCurrent($shake_id);
            return array('code' => 4, 'msg' => '已经没有参与次数');
        }

        //查询活动奖品总库存
        $prizeCountNum = $prizeModel->getCountNum($shake_id);
		if ($prizeCountNum <= 0) {
            $WinningModel->incrUserDrawNum($shake_id,$openid);
			$WinningModel->decrVisitsCurrent ($shake_id);
			return array (
					'code' => 7,
					'msg' => '奖品已派完' 
			);
		}
		// 获取抽奖用户数量阀值
        $lock_max = $WinningModel->getQueue();
        $lock  =  $WinningModel->getQueueSize();
        //如果抽奖达到伐值
        if ($lock >= $lock_max) {
            //直接进去参与记录表 返回未中奖
            $WinningModel->decrVisitsCurrent($shake_id);
            return array('code' => 5, 'msg' => '参加人数过多,请稍后再试');
        }

        //获取剩余活动奖品的数量
        $prize = $prizeModel->getPrizesNum($shake_id);
        $_prize = [];

        foreach ($prize as $key => $row) {
            if ($row > 0) {
                $_prize[] = ['prize_id' => $key, 'nums' => $row];
            }
        }
      \Log4p::info(['type' => 'shake/draw'.$shake_id.'_'.$uid, 'request' => ['file' =>__FILE__,'line'=>(string)__LINE__], 'response' =>  []]);
        if (empty($_prize)) {
            $WinningModel->incrUserDrawNum($shake_id,$openid);
            $WinningModel->decrVisitsCurrent($shake_id);
            return array('code' => 7, 'msg' => '奖品已派完');
        }

       //获取到未中奖的商品详细信息
        $NoDrawData = $this->getNoDraw($shake_id);
        $UserPlayRecordModel = new UserPlayRecordModel();
        $typekey = $channel_id.'_'.$uid;
        //开始随机抽奖

        //随机抽奖数组
        $new_prize = [];
        // 用户可中奖奖品列表
        $user_draw_prize_info = [];
        //所有奖品
        $type_all_prize=[];
        //红包奖品
        $type_2_prize=[];
        //卡卷奖品
        $type_1_prize=[];

        //循环过滤出卡卷和红包
        foreach ($_prize as $row) {

            $prize_info = $prizeModel->getPrizeInfo($shake_id, $row['prize_id']);
            if ($prize_info) {
                //取几率大于0的
                if($prize_info['winning_rate'] >0){
                    //将有的奖品设置的概率加入抽奖数组
                    $type_all_prize[$row['prize_id']] = floatval($prize_info['winning_rate']);
                    //分类所有奖品数据
                    switch ($prize_info['prize_type']){
                        case 1:  //卡卷
                            $type_1_prize[$row['prize_id']] = floatval($prize_info['winning_rate']);
                            break;
                        case 2:  //红包
                            $type_2_prize[$row['prize_id']] = floatval($prize_info['winning_rate']);
                            break;
                    }
                }
            }
        }

        //判断是否中过红包  中过红包则直接未中奖
        if ($UserPlayRecordModel->isInUserPrizeAll($shake_id,$typekey, ShakePrizeModel::PRIZE_TYPE_REDBOX)) {
            //如果中过红包  则第二次抽卡卷和未中奖
               $new_prize=$type_1_prize;
            if($NoDrawData){
                $new_prize[$NoDrawData['prize_id']] = floatval($NoDrawData['winning_rate']);
            }
        } else {

            //判断是否加入未中奖概率（最后一次不加入）
            if (intval($rs['more_num']) - $user_num > 1) {
                //如果不是最后一次  加入未中奖的概率   随机抽取
                $new_prize=$type_all_prize;
                if($NoDrawData){
                    $new_prize[$NoDrawData['prize_id']] = floatval($NoDrawData['winning_rate']);
                }
            }else{

                //如果没有红包则抽卡卷
                if(empty($type_2_prize)){
                    $new_prize=$type_1_prize;
                }else{
                    $new_prize=$type_2_prize;
                }

            }
        }
        //如果奖品数量为空   如果是最后一次 则提示卡卷已经被抽完  否则则提示红包已抽完
        if(empty($new_prize)){
            if(intval($rs['more_num']) - $user_num > 1){
                $WinningModel->incrUserDrawNum($shake_id,$openid);
                $WinningModel->decrVisitsCurrent($shake_id);
                return array('code' => 7, 'msg' => '红包奖品已经派完');
            }else{
                $WinningModel->incrUserDrawNum($shake_id,$openid);
                $WinningModel->decrVisitsCurrent($shake_id);
                return array('code' => 7, 'msg' => '卡卷奖品已派完');
            }
        }else{
            $draw_prize_id = $this->getRand($new_prize); //根据概率获取奖项id
            $user_draw_prize_info = $prizeModel->getPrizeInfo($shake_id, $draw_prize_id);
        }

        //根据抽奖的类型从不同的奖品队列中去获取奖品  如果没有则显示已经派完
        $prize_type_list = $prizeModel->PrizePullList($shake_id,$user_draw_prize_info['prize_type']);
        if(!$prize_type_list){
           switch ($user_draw_prize_info['prize_type']){
               case 1:
                   $WinningModel->incrUserDrawNum($shake_id,$openid);
                   $WinningModel->decrVisitsCurrent($shake_id);
                   return array('code' => 7, 'msg' => '卡卷奖品已派完');
               case 2:
                   $WinningModel->incrUserDrawNum($shake_id,$openid);
                   $WinningModel->decrVisitsCurrent($shake_id);
                   return array('code' => 7, 'msg' => '红包奖品已经派完');
           }
        }


        \Log4p::info(['type' => 'draw_prize', 'request' => ['shake_id' => $shake_id,'open_id'=>$openid], 'response' => ['prize' =>$user_draw_prize_info]]);
        $user_draw_prize_info['nick'] = $this->user_info['nickname'];
        $user_draw_prize_info['openid'] = $this->user_info['open_id'];
        $user_draw_prize_info['uid'] = $this->user_info['uid'];
        //保存中奖信息加入队列
        $WinningModel->AddCacheList($user_draw_prize_info);

        //已抽奖次数
        $new_user_num = $WinningModel->getUserDrawNum($shake_id, $openid);
        $drawData = [];
        $drawData['prize_name'] = $user_draw_prize_info['prize_name'];
        $drawData['prize_type'] = $user_draw_prize_info['prize_type'];
        $drawData['level'] 		= $user_draw_prize_info['level'];
        $drawData['pic'] 		= $user_draw_prize_info['prize_logo'];
        $drawData['num'] 		= $rs['more_num'] - $new_user_num;

        //删除当前访问次数
        $WinningModel->decrVisitsCurrent($shake_id);
        return array('code' => 0, 'msg' => 'ok', 'data' => $drawData);
    }


    /**
     * 查看个人中奖记录
     */
    public function myprize()
    {
        if (!isset($this->params['shake_id'])) {
            return array('code' => 4, 'msg' => '服务器繁忙，请稍后再试');
        }
        
        $shake_id = $this->params['shake_id'];
        $openid = $this->user_info['open_id'];
        $uid = $this->user_info['uid'];
        $channel_id = $this->user_info['shop_id'];
        $WinningModel = new ShakeWinningModel();
        //查询缓存已经中奖的记录
        $user_winning_cache = $WinningModel->getUserWinnerCache($shake_id,$channel_id,$uid);
        $where=['shake_id'=>$shake_id,'user_id'=>$openid];
        $user_winning_sql = $WinningModel->getList($where);

       foreach($user_winning_cache as $key => &$row){
           $row['prize_send_status']=0;
           $user_winning_sql[]=$row;
       }
        return array('code' => 0, 'msg' => 'ok', 'data' => $user_winning_sql);

    }

    /**
     * 登录
     */
    public function login()
    {

        if (isset($this->params['uid']) && isset($this->params['nickname']) && isset($this->params['headimgurl']) && isset($this->params['open_id']) && isset($this->params['shop_id']) && isset($this->params['latitude']) && isset($this->params['longitude'])) {
            $params = [
                'uid' => $this->params['uid'],
                'nickname' => $this->params['nickname'],
                'headimgurl' => $this->params['headimgurl'],
                'open_id' => $this->params['open_id'],
                'shop_id' => $this->params['shop_id'],
                'latitude' => $this->params['latitude'],
                'longitude' => $this->params['longitude']
            ];

            $OauthModel = new OauthModel();
            $toke = $OauthModel->Login($params);
            return array('code' => 0, 'msg' => 'ok', 'data' => ['token' => $toke]);

        }
        return array('code' => 0, 'msg' => '登录失败', 'data' => []);
    }


    /**
     * 抽奖算法
     * @param $proArr
     * @return int|string
     */
    function getRand($proArr)
    {
        $data = '';
        $proSum = array_sum($proArr); //概率数组的总概率精度
		 asort($proArr);
        foreach ($proArr as $k => $v) { //概率数组循环
            $randNum = mt_rand(0, $proSum);

            if ($randNum <= $v) {
                $data = $k;
                break;
            } else {
                $proSum -= $v;
            }
        }
        unset($proArr);
        return $data;
    }

    /**
     * 获取地址
     */
    private function getAddr()
    {
        return ['x' => $this->user_info['longitude'], 'y' => $this->user_info['latitude']];
    }


    /**
     * 获取抽奖次数
     */
    public function getShakeNum()
    {
        if (!isset($this->params['shake_id'])) {
            return '服务器繁忙，请稍后再试';
        }

        $shake_id = $this->params['shake_id'];
        $openid = $this->user_info['open_id'];
        $rs = $this->ShakeModel->getShakeData($shake_id);


//        // 查询用户获奖次数
        $WinningModel = new ShakeWinningModel();

        $user_sum_winning = $WinningModel->getUserDrawNum($shake_id,$openid);
        $many = $rs['more_num'] - $user_sum_winning;
        return array('code' => 0, 'msg' => 'ok', 'data' => ['num' => $many]);
    }

    /**
     * 求两个已知经纬度之间的距离,单位为米
     *
     * @param lng1 $ ,lng2 经度
     * @param lat1 $ ,lat2 纬度
     * @return float 距离，单位米
     * @author www.Alixixi.com
     */
    public function getdistance($lng1, $lat1, $lng2, $lat2)
    {
        // 将角度转为狐度
        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        return $s;
    }

    /**
     * 根据活动id获取未中奖的奖品信息
     * @param $shake_id
     */
    public function getNoDraw($shake_id)
    {
        $ShakePrizeModel = new  ShakePrizeModel();
        $ShakePrizes = $ShakePrizeModel->getPrizeInfo($shake_id);
        foreach ($ShakePrizes as $row) {
            if ($row['prize_type'] == 3 && $row['winning_rate'] >0 ) {
                return $row;
            }
        }
        return false;

    }


    public function Test(){

            $ShakeWinningModel = new ShakeWinningModel();
            $ShakeWinningModel->setQueueMax(50);
            $ShakeWinningModel->setInMax(100);
            return '配置成功';

        //随机生成登录用户
        $param= '{"uid":15049810,"nickname":"dark","headimgurl":"http://wx.qlogo.cn/mmopen/qyePwN2s3vhxGoW9mhTNlLpHp7xZskGUccKuicSEhEXnibia1HG8MvYvYmu2kg3WSMTPK0woBxLqzyE2rOCaaHNkTDNrdQRiccDh/0","open_id":"oaoxXwxcTM6EQrhG3Vp0mOgxLw64","shop_id":108089,"latitude":22.54444,"longitude":113.9279,"action":"login"}';
        //执行登录
        $param_arr=json_decode($param,true);
        $OauthModel = new OauthModel();
        $param_arr['uid']=rand(100000000,999999999);
        $token = $OauthModel->Login($param_arr);
        //执行抽奖
        $this->params['shake_id']=30;               //设置抽奖id
        $str='{"uid":647613525,"nickname":"dark","headimgurl":"http:\/\/wx.qlogo.cn\/mmopen\/qyePwN2s3vhxGoW9mhTNlLpHp7xZskGUccKuicSEhEXnibia1HG8MvYvYmu2kg3WSMTPK0woBxLqzyE2rOCaaHNkTDNrdQRiccDh\/0","open_id":"oaoxXwxcTM6EQrhG3Vp0mOgxLw64","shop_id":108089,"latitude":22.54444,"longitude":113.9279,"action":"login"}';
        $num=rand(100000000,999999999);
        $this->user_info=json_decode($str,true);
        $this->user_info['open_id']=md5($num);
        $this->user_info['uid']=$num;
        for ($i=0;$i<2;$i++){
           var_dump($this->draw());
        }
    }



}