<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/7
 * Time: 10:52
 * 错误信息保存
 */

namespace Weixin\Models;


class  ShakePrizeErrorModel  extends BaseModel{

    private $db_default;
    private $files=['id','shop_id','parameter','url','error','create'];
    public function __construct(){
        parent::__construct();
        $this->db_default = $this->db('default');

    }



    /**
     * 添加
     */
    public function add($params){
        $winning_data = array(
            'parameter' => isset($params['parameter']) ? $params['parameter'] : " ",
            'url' => isset($params['url']) ? $params['url'] : " ",
            'error' => isset($params['error']) ? $params['error'] : " ",
            'shop_id' => isset($params['shop_id']) ? $params['shop_id'] :0,
            'create' => time(),

        );

        $result = $this->db_default->insert('shake_prize_error')->cols($winning_data)->query();

        return $result;
    }





}