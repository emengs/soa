<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/8
 * Time: 11:16
 * 摇一摇活动
 */
namespace Webadmin\Controllers;

use Webadmin\Exception\ValidationException;
use Webadmin\Models\ExcelModel;
use Webadmin\Models\ShakePrizeModel;
use Webadmin\Models\ShakeWinningModel;
use Webadmin\Models\ThirdPartyModel;
use Webadmin\Models\ShakeModel;
use System\Helpers\QRcode;
use Webadmin\Models\TimeModel;

class Shake extends Base
{


    private $ShakeModel;
    public function __construct($params)
    {

        $this->params = $params['params'];
        parent::__construct($this->params);
        $this->ShakeModel = new ShakeModel();

    }

    /**
     * 获取活动列表
     */
    public function getList(){
        if(!isset($this->params['limit']) || !isset($this->params['page'])){
            throw new ValidationException(40233);
        }
        $params['page']=$this->params['page'];
        $params['limit']=$this->params['limit'];
        unset($this->params['limit']);
        unset($this->params['page']);
        $params['where']=$this->params;
        $params['where']['channel_id']=$this->shop_info['shop_id'];
        $params['where']['is_del']=0;
        $data = $this->ShakeModel->getList($params);
        return $data;

    }


    /**
     * 获取活动详情
     */
    public function getOne(){
        $params['where']=$this->params;
        $params['where']['channel_id']=$this->shop_info['shop_id'];
        $data = $this->ShakeModel->getOne($params);
        $ShakePrizeModel= new ShakePrizeModel();
        $prize=$params;
        $data['prize']=$ShakePrizeModel->getList($prize);
        return $data;
    }
    /**
     * 添加活动
     */
    public function Add(){

        $params=$this->params;
        unset($params['p_token']);
        $params['channel_id']=$this->shop_info['shop_id'];
        $data = $this->ShakeModel->Add($params);
        if($data){
            return '添加成功';
        }
		return '操作失败';
    }

    /**
     * 修改活动
     */
    public function Edit(){
        $params=$this->params;
        unset($params['p_token']);
        if(!$params['shake_id']){
            throw new ValidationException(40233);
        }
        $time = time();
       $shake= $this->ShakeModel->getOne(['where'=>['shake_id'=>$params['shake_id']]]);
        if($shake['start_time'] < $time){
            throw new ValidationException('活动已经开始，不能做修改');
        }
        $params['channel_id']=$this->shop_info['shop_id'];
        $params['start_time']=strtotime($params['start_time']);
        $params['end_time']=strtotime($params['end_time']);
        $data = $this->ShakeModel->Edit($params);



        return $data;
    }



    /**
     * 开启/关闭活动
     */
    public function Open(){
        if(!isset($this->params['shake_id']) || !isset($this->params['activity_status'])){
            throw new ValidationException(40233);
        }

        $new_parmas=[
            'shake_id'=>$this->params['shake_id'],
            'activity_status'=>$this->params['activity_status']
        ];
        $data = $this->ShakeModel->Open($new_parmas);
        return $data;
    }

    /**
     * 删除活动
     */
    public function Del(){
        if(!isset($this->params['shake_id'])){
            throw new ValidationException(40233);
        }
        $new_parmas=[
          'shake_id'=>$this->params['shake_id'],
          'is_del'=>1
        ];
        $data = $this->ShakeModel->Edit($new_parmas);
        return $data;
    }

    /**
     * 获取二维码
     */
    public function Qrcode(){
       $url= $this->shop_info['login_url'];
        $filename = 'wx.png';  //  生成的文件名
        $errorCorrectionLevel = 'L';  // 纠错级别：L、M、Q、H
        $matrixPointSize = 4; // 点的大小：1到10
        QRcode::png($url, $filename, $errorCorrectionLevel, $matrixPointSize, 2);

        $basecode = base64_encode(file_get_contents($filename));
        unlink($filename);
        return ['base_64'=>$basecode];
    }

    /**
     * 获取优惠卷列表
     */
    public function getCoupon(){
        $params=$this->params;
        $ThirdPartyModel = new ThirdPartyModel($this->shop_info);
        $data=$ThirdPartyModel->getCoupon($params);
        return $data;

    }

    /**
     * 获取现金红包接口
     */
    public function getRed(){
        $params=$this->params;
        $ThirdPartyModel = new ThirdPartyModel($this->shop_info);
        $data=$ThirdPartyModel->getRed($params);
        return $data;
    }

    /**
     * 获取中奖信息
     */
    public function getShakeWinning(){
        if(!isset($this->params['shake_id']) ){
            throw new ValidationException(40233);
        }
        if(!isset($this->params['limit']) || !isset($this->params['page'])){
            throw new ValidationException(40233);

        }
        $params['page']=$this->params['page'];
        $params['limit']=$this->params['limit'];
        unset($this->params['limit']);
        unset($this->params['page']);


        $params['where']=$this->params;
        foreach ($params['where'] as $key=>$row){
            if($row==''){
                unset($params['where'][$key]);
            }
        }
        $params['where']['channel_id']=$this->shop_info['shop_id'];

        $ShakeWinningModel = new ShakeWinningModel();
       $data=$ShakeWinningModel->getList($params);

        return $data;
    }


    /**
     * 中奖信息导出
     */

    public function Export(){
        $ExcelModel= new ExcelModel();
        if(!isset($this->params['shake_id'])){
            return '缺少参数shake_id';
        }
        $params['where']=$this->params;
        $params['where']['channel_id']=$this->shop_info['shop_id'];
        $params['where']['is_del']=0;
        $ShakeWinningModel = new ShakeWinningModel();
        $data=$ShakeWinningModel->getList($params);
        $url= $ExcelModel->exportShakeWinning($data['lists'],$this->params['shake_id']);
        return $url;
    }

    public function DelCache(){
       $params= $this->params;
       $ThirdPartyModel = ThirdPartyModel();
        $ThirdPartyModel->delCache();
    }


    /**
     * 缓存伐值显示
     */
    public function getSettingMax(){
        $ShakeWinningModel = new ShakeWinningModel();
        $data=[];
        $data['visits_max']=$ShakeWinningModel->getVisitsMax()?$ShakeWinningModel->getVisitsMax():0;
        $data['queue_max']=$ShakeWinningModel->getQueue()?$ShakeWinningModel->getQueue():0;
        return $data;
    }


    /**
     * 缓存伐值修改
     */
    public function setSettingMax(){
        if(!isset($this->params['visits_max']) || !isset($this->params['queue_max'])){
            return '缺少参数';
        }
        $ShakeWinningModel = new ShakeWinningModel();
        $queue_rs=$ShakeWinningModel->setQueueMax($this->params['queue_max']);
        if(!$queue_rs)
        {
            return '队列伐值保存出错';
        }
        $visits_rs=$ShakeWinningModel->setVisitsMax($this->params['visits_max']);
        if(!$visits_rs)
        {
            return '入口伐值保存失败';
        }
    }

    /**
     * 获取任务列表
     */
    public function getTaskSettingList(){
        $TimeModel =new TimeModel();
       $data= $TimeModel->getTaskAllList();
        return $data;
    }

    /**
     * 添加任务任务活动
     */

    public function addTaskSettingList(){
        $TimeModel =new TimeModel();
        $value = $this->params['value'];
        $rs= $TimeModel->addTaskAllList($value);
        return $rs;
    }

    /**
     * 删除任务
     */
    public function semTaskSettingList(){
        $TimeModel =new TimeModel();
        $value = $this->params['value'];
       $rs= $TimeModel->delTaskAllList($value);
        return $rs;
    }

    /**
     * 查询活动统计信息
     */
    public function ShakeStaticList(){
        if(!isset($this->params['limit']) || !isset($this->params['page'])){
            throw new ValidationException(40233);
        }
        $params['page']=$this->params['page'];
        $params['limit']=$this->params['limit'];
        unset($this->params['limit']);
        unset($this->params['page']);
        $params['where']=$this->params;
        $params['where']['channel_id']=$this->shop_info['shop_id'];
        $params['where']['is_del']=0;
        $data = $this->ShakeModel->getList($params);
        //查询奖品剩余情况
        if(!empty($data['lists'])){
            $ShakePrizeModel = new ShakePrizeModel();
            foreach ($data['lists'] as &$row){
                $row['visits_current'] =$this->ShakeModel->getvisitscurrent($row['shake_id']);
                $row['visits_total'] =$this->ShakeModel->getvisitstotal($row['shake_id']) ;
                    $row['prize_remain_1'] =$ShakePrizeModel->PrizePullListLen($row['shake_id'],1) ;
                $row['prize_remain_2'] =$ShakePrizeModel->PrizePullListLen($row['shake_id'],2) ;

            }

        }
        return $data;
    }

    /**
     *  查询未处理队列长度
     */
    public function getUntreatedList(){
        $ShakeWinningModel= new ShakeWinningModel();
        if(!isset($this->params['limit']) || !isset($this->params['page'])){
            throw new ValidationException(40233);
        }
        $params['page']=$this->params['page'];
        $params['limit']=$this->params['limit'];
       $info['lists']= $ShakeWinningModel->getDrawList($params);
        foreach ($info['lists'] as &$row){
            $row['draw_info'] = $ShakeWinningModel->getDrawInfo($row);

        }
        $info['page']['per_page'] = $params['limit'];
        $info['page']['total_count'] =  $ShakeWinningModel->getDrawListLen();
        $info['page']['current_page'] = $params['page'];
        $info['page']['total_page'] = ceil($info['page']['total_count'] / $params['limit']);
        return $info;
    }



}