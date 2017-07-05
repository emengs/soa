<?php

namespace Console\Controllers;

use Webadmin\Exception\ValidationException;
use Console\Models\ShakeWinningModel;
use Console\Models\TimeModel;
use Console\Models\ThirdPartyModel;
use Console\Models\PrizeSendLogModel;
use Console\Models\UserPlayRecordModel;
require_once dirname(__DIR__) . '/Helpers/FunctionHelper.php';

/**
 *  定时控制器控制器
 * @author zhijiazou
 */
class Time
{

    private $TaskListAll = ['ErrorCallback','SendWinning','UserJoinLog'];

    public function __construct($params=[])
    {

    }

    /**
     * 计划任务分配
     * @return type
     */
    public function TaskAllocation()
    {
        $Time = new TimeModel();
       //读取缓存中正在执行的计划任务名称
        $UnexecutedTask = $Time->pushTask();
        if(empty($UnexecutedTask)){
            return '没有空闲任务';
        }
       //取出第一个任务
        $Taskname = $UnexecutedTask;
        //将任务加入
        $set_rs= $Time->addTaskList($Taskname);
        if(!$set_rs){
            return '任务执行中';
        }
         //执行方法
            echo $Taskname;
           $this->$Taskname();
         //将任务剔除集合
        $del_rs= $Time->delTaskList($Taskname);

        return '任务执行完成!';

    }


    /**
     * 定时重新补发发送奖品
     */
    public function ErrorCallback()
    {
        $TimeModel = new TimeModel();
        //获取ip地址
       $ip= $this->getServerIp();
        //获取排名
        $row=0;
      $row=$TimeModel->getIPSettingRank($ip);

        $PrizeSendLogModel = new PrizeSendLogModel();

        $ShakeWinningModel = new ShakeWinningModel();
        $shop_info = $this->getBusinessConfig();
        $ThirdPartyModel =new ThirdPartyModel($shop_info);
        $errorWhere['where'] = ['prize_send_status' => 2, 'requests_num' => [' < ',3]];
        $errorWhere['page'] = $row+1;
        $errorWhere['limit'] = 10;
        //查询为发送成功的记录
        $error_data = $ShakeWinningModel->getList($errorWhere);
        if ($error_data['lists']) {
            $lists = $error_data['lists'];
            foreach ($lists as $row) {
                //根据winning_id查询发送失败的信息
                $SendLogWhere['where']=['winning_id'=>$row['winning_id']];
                $PrizeSendLog = $PrizeSendLogModel->getOne($SendLogWhere);

                if($PrizeSendLog['status']==1){
                    break;
                }
                $save=[];
                //重新补发
                $rs = $ThirdPartyModel->Reissue($PrizeSendLog['winning_id'],$PrizeSendLog['shake_id'],$PrizeSendLog['request_url'],json_decode($PrizeSendLog['post_data'], true));
                if ($rs['errcode'] == 0) {
                    //改变状态
                    $save['prize_send_status']=1;
                } else {
                    //更新次数
                    $save['requests_num'] =$row['requests_num'] + 1;
                }
                $save['prize_send_result']=json_encode($rs);
                 $save['update_time'] =time();
                $ShakeWinningModel->Edit($save, ['winning_id' => $row['winning_id']]);
            }
            return '处理完成';
        }

        return '没有数据';

    }

    /**
     * 从reids队列中取出中奖信息
     */
    public function SendWinning()
    {
        $TimeModel = new TimeModel();
        //取出队列
        $data = $TimeModel->getShakeWinningList();

        if (!$data) {
            return '没有数据';
        }
        \Log4p::info(['type' => 'DrawListData', 'request' => ['data' =>1], 'response' => ['msg' =>'队列发送中奖信息']]);

        $ShakeWinningModel = new ShakeWinningModel();
        $UserPlayRecordModel = new UserPlayRecordModel();
        //取出用户中奖信息
        $key = $data['channel_id'] . '_' . $data['uid'];
        $user_draw_data = $ShakeWinningModel->getUserDrawdata($data['shake_id'], $key, $data['uuid']);
        if (!$user_draw_data) {
            return '用户信息不存在';
        }
        \Log4p::info(['type' => 'DrawListUserData', 'request' => ['data' =>$user_draw_data,'line'=>(string)__LINE__,'files'=>__FILE__], 'response' => ['msg' =>'time']]);
        //存入参与记录表
        $user_draw_data['is_win'] = 1;
        $user_draw_data['user_name'] = $user_draw_data['nick'];
        $user_draw_data['user_id'] = $user_draw_data['openid'];
        $UserPlayRecordModel->add($user_draw_data);
        //存入中奖记录表
        $shake_winning_id = $ShakeWinningModel->add($user_draw_data);
        //清除redis用户中奖记录缓存
        $ShakeWinningModel->DelUserDrawdata($data['shake_id'],$key ,$data['uuid']);
        //获取配置信息
        $shop_info = $this->getBusinessConfig();
        $ThirdPartyModel = new ThirdPartyModel($shop_info);
        //发送卡卷红包
        $result=[];
        if ($user_draw_data['prize_type'] == 1) {
            //发放卡卷
            $coupon = [
                'card_type_id' => $data['prize_out_id'],
                'to_user_ids' => [$data['uid']],
                'winning_id' => $shake_winning_id,
                'shake_id' => $data['shake_id'],
            ];
            $result = $ThirdPartyModel->giveCoupon($coupon);
        }
        if ($user_draw_data['prize_type'] == 2) {
            //发放红包
            $red = [
                'id' => $data['prize_out_id'],
                'uid' => $data['uid'],
                'source' => 2,
                'winning_id' => $shake_winning_id,
                'shake_id' => $data['shake_id'],
            ];
            $result = $ThirdPartyModel->giveRed($red);
        };
        //如果发送成功
        if ($result['errcode'] == 0 || $result['errcode']==10002 ) {
            //改变中奖记录表状态 为已发送
            $ShakeWinningData['prize_send_status'] = 1;
        }
        else {
            //改变中奖记录表状态 为失败 记录错误
            $ShakeWinningData['prize_send_status'] = 2;
        }
        $ShakeWinningData['prize_send_result'] = json_encode($result);
        $ShakeWinningModel->Edit($ShakeWinningData, ['winning_id' => $shake_winning_id]);
        return '成功';
    }

    /**
     * 将未中奖记录加入数据库
     */
    public function UserJoinLog(){

        $UserPlayRecordModel = new UserPlayRecordModel();
//        //取出队列
        $data = $UserPlayRecordModel->pullUserPlayLog();

        if (!$data) {
            return '没有数据';
        }

        //加入数据库
        $UserPlayRecordModel->add($data);
        return '成功';

    }

    public function Test(){
        echo '<br>';
        echo 123;
    }




    /**
     * 加载配置
     */
    public function getBusinessConfig()
    {

        $config = console_load_config('config_' . ENV, 'shopinfo');
        return $config;

    }

    /**
     * 获取服务器ip地址
     */
    public function getServerIp(){
        $ss = exec('/sbin/ifconfig eth0 | sed -n \'s/^ *.*addr:\\([0-9.]\\{7,\\}\\) .*$/\\1/p\'',$arr);
        $ret = isset($arr[0])?$arr[0]:0;
        return $ret;
    }
}