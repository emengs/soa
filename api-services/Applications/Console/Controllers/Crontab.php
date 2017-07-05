<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/25
 * Time: 17:02
 */

namespace Console\Controllers;
use Console\Models\TimeModel;
use Workerman\Lib\Timer;
require_once dirname(__DIR__) . '/Helpers/FunctionHelper.php';

class Crontab
{

    public function log(){
        //日志测试
        \Log4p::info(['type' => 'shake_draw', 'request' => [], 'response' => ['num'=>1]]);
    }


    public function Initialize(){

        $this->setIp();
        $this->TaskStart();
    }


    /**
     * 开启定时任务
     */
    public function TaskStart(){

        $arr=['params'=>[]];
        $time = new Time($arr);

        Timer::add(5,[$time,'ErrorCallback'],[],true);

        Timer::add(5,[$time,'SendWinning'],[],true);

        Timer::add(5,[$time,'UserJoinLog'],[],true);
    }

    /**
     * 清空所有定时任务
     */
    public function TaskEnd()
    {

        Timer::delAll();
    }

    /**
     * 获取ip地址 存入redis 缓存
     */
    public function setIp()
    {
        $ip = $this->getServerIp();
        $TimeModel = new TimeModel();
        $TimeModel->setIPSetting($ip);
        return $ip;
    }

    public function delIP()
    { $ip = $this->getServerIp();
        $TimeModel = new TimeModel();
        $TimeModel->DelIPSetting($ip);
        $this->TaskEnd();
    }

    public function reloadIp()
    {
        $ip = $this->getServerIp();
        $TimeModel = new TimeModel();
        $TimeModel->DelIPSetting($ip);
        $TimeModel->setIPSetting($ip);
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