<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/9
 * Time: 11:36
 * 商圈接口
 */
namespace Webadmin\Models;


class ThirdPartyModel extends BaseModel
{

    private $SETTING_KAJUAN = 1;  //获取奖劵接口
    private $SETTING_RED = 2;  //获取红包接口
    private $GIVE_SETTING_KAJUAN = 3;  //获取奖劵接口
    private $GIVE_SETTING_RED = 4;  //获取红包接口
    private $SETTING_TOKEN = 5;  //获取接口token
    private $token;
    private $ShakeSettingModel;
    public $shopinfo;


    public function __construct($shopinfo)
    {
        parent::__construct();
        $this->ShakeSettingModel = new ShakeSettingModel();
        $this->shopinfo = $shopinfo;
        $this->getToken();
    }

    /**
     * 获取优惠卷
     */
    public function getCoupon($data)
    {
        $data['page']= isset($data['page'])?$data['page']:1;
        $data['count']= isset($data['count'])?$data['count']:10;
        $data['page']=$data['page']-1;
        $setting_where['where'] = ['shop_id' => $this->shopinfo['shop_id'], 'setting_key' => $this->SETTING_KAJUAN];
        $ShakeSettingModel = new ShakeSettingModel();
        $setting = $ShakeSettingModel->getOne($setting_where);
        $url = $setting['setting_value'] . '?access_token=' . $this->token.'&page=' . $data['page'].'&count=' . $data['count'];
        $rs = $this->getHttp($url);
        if (!$rs) {
            return false;
        }
        return $rs;
    }

    /**
     *  获取现金红包接口
     */
    public function getRed($data)
    {
        $data['page']= isset($data['page'])?$data['page']:1;
        $data['count']= isset($data['count'])?$data['count']:10;
        $data['page']=$data['page']-1;
        $setting_where['where'] = ['shop_id' => $this->shopinfo['shop_id'], 'setting_key' => $this->SETTING_RED];
        $ShakeSettingModel = new ShakeSettingModel();
        $setting = $ShakeSettingModel->getOne($setting_where);
        $url = $setting['setting_value'] . '?access_token=' . $this->token.'&page=' . $data['page'].'&count=' . $data['count'];
        $rs = $this->getHttp($url);
        if (!$rs) {
            return false;
        }
        $rs['errmsg']['page']['current_page'] +=1;
        return $rs;
    }

    /**
     * 发放优惠卷
     */
    public function giveCoupon($params)
    {
        $givedata=[
            'card_type_id' =>$params['card_type_id'],
            'to_user_ids' => $params['to_user_ids']
        ];

        $setting_where['where'] = ['shop_id' => $this->shopinfo['shop_id'], 'setting_key' => $this->GIVE_SETTING_KAJUAN];
        $ShakeSettingModel = new ShakeSettingModel();
        $setting = $ShakeSettingModel->getOne($setting_where);
        $url = $setting['setting_value'] . '?access_token=' . $this->token;
        /**测试**/
       // $data = $this->getHttp($url, $givedata);
        $data =['errcode'=>0,'msg'=>'成功'];

        $error['winning_id'] = $params['winning_id'];
        $error['shake_id'] = $params['shake_id'];
        $error['post_data'] = json_encode($params);
        $error['request_url'] = $url;
        $error['response_msg'] = json_encode($data);
        if($data['errcode']==0){
            $error['status'] = 1;
        }
        $this->addSendLog($error);
        return $data;
    }

    /**
     * 发放红包
     */
    public function giveRed($params)
    {

        $giveData=  [
            'id' =>$params['id'],
            'uid' => $params['uid'],
            'source' => 2
        ];
        $setting_where['where'] = ['shop_id' => $this->shopinfo['shop_id'], 'setting_key' => $this->GIVE_SETTING_RED];
        $ShakeSettingModel = new ShakeSettingModel();
        $setting = $ShakeSettingModel->getOne($setting_where);
        $url = $setting['setting_value'] . '?access_token=' . $this->token;
        /**测试**/
     //   $data = $this->getHttp($url, $giveData);
        $data =['errcode'=>0,'msg'=>'成功'];

        $error['winning_id'] = $params['winning_id'];
        $error['shake_id'] = $params['shake_id'];
        $error['post_data'] = json_encode($params);
        $error['request_url'] = $url;
        $error['response_msg'] = json_encode($data);
        if($data['errcode']==0){
            $error['status'] = 1;
        }
        $this->addSendLog($error);
        return $data;
    }

    /**
     * 获取token
     */
    public function getToken()
    {

        $token = $this->RedisCache($this->RedisOpenAccessTokenPrefix)->getOpenAccessToken($this->shopinfo['shop_id']);
        if ($token) {
            $this->token = $token;
            return true;
        }
        //从数据库拿取配置信息
        $setting_where['where'] = ['shop_id' => $this->shopinfo['shop_id'], 'setting_key' => $this->SETTING_TOKEN];

        $ShakeSettingModel = new ShakeSettingModel();
        $setting = $ShakeSettingModel->getOne($setting_where);
        if (!$setting) {
            throw new \Exception('配置信息不存在') ;
        }
        $url = $setting['setting_value'];
        $params = [
            'app_id' => $this->shopinfo['app_id'],
            'app_secret' => $this->shopinfo['app_secret'],
            'shop_id' => $this->shopinfo['shop_id'],
        ];
        $data = $this->getHttp($url, $params);
        //存入缓存
        if (isset($data['data']['access_token'])) {
            $this->RedisCache($this->RedisOpenAccessTokenPrefix)->setOpenAccessToken($this->shopinfo['shop_id'], $data['data']['access_token']);
            $this->token = $data['data']['access_token'];

        }

        $error['post_data'] = json_encode($params);
        $error['request_url'] = $url;
        $error['response_msg'] = json_encode($data);
        if($data['errcode']==0){
            $error['status'] = 1;
        }
        $this->addSendLog($error);
        return false;
    }


    public function addSendLog($error)
    {

        $PrizeSendLogModel= new PrizeSendLogModel();
        $PrizeSendLogModel->add($error);
    }


    /**
     * @param $url
     * @param $param
     * @return bool|mixed
     */
    public function getHttp($url, $param = [])
    {

        $post_data = $param;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT,60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

        $output = curl_exec($ch);
        curl_close($ch);
        if (!is_array($output)) {
            $output = json_decode($output, true);
        }
        //打印获得的数据
        return $output;

    }
}