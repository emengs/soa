<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/8
 * Time: 14:31
 * 摇一摇奖品
 */
namespace Webadmin\Models;

class ShakePrizeModel extends BaseModel
{

    private $files=['prize_id','shake_id','channel_id','prize_out_id','prize_name','level','prize_type','prize_nums','prize_used_nums','prize_logo','prize_ext_id','prize_ext_info','winning_rate','is_del','create_time'];
    /**
     * 获取列表数据
     */
    public function getList($params)
    {
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 5;
        $offset = ($page - 1) * $limit;

        $where = array();
        if ($params['where']) {
            foreach ($params['where'] as $key => $row) {
                if (in_array($key, $this->files)) {
                    $where[] = $key . ' = ' . $row;
                }
            }
        }

        $query = $this->db()->select('*')
            ->from('shake_prize')
            ->where($where);
        if (isset($params['page']) && isset($params['limit'])) {
            $count = $this->db()->select('count(*) as cot')
                ->from('shake_prize')
                ->where($where)
                ->row();
            $query = $query->limit($limit)
                ->offset($offset);
            $info['count'] = $count['cot'];
        }
        $data = $query->query();

        //echo $this->db()->lastSQL();
        $info['lists'] = $data;

        return $info;
    }

    /**
     * 查询数量
     */
    public function getCount($params){

        $where = array();
        if ($params['where']) {
            foreach ($params['where'] as $key => $row) {
                if (in_array($key, $this->files)) {
                    $where[] = $key . ' = ' . $row;
                }
            }
        }
        $count = $this->db()->select('count(*) as cot')
            ->from('shake_prize')
            ->where($where)
            ->row();
        return $count['cot'];
    }

    /**
     * 批量添加
     */
    public function batchAdd($params,$shake_id,$shop_id){

        foreach ($params as $row){
            $row['shake_id']=$shake_id;
            $row['channel_id']=$shop_id;
            $this->Add($row);
        }

    }

    /**
     * 奖品删除
     */
    public function Del($params){
        $where = array();
        if ($params) {
            foreach ($params as $key => $row) {
                if (in_array($key, $this->files)) {
                    if(!is_numeric($row)){
                        $row="'".$row."'";
                    }
                    $where[] = $key . ' = ' . $row;
                }
            }
        }
        $query = $this->db()->delete('shake_prize')->where($where)->query();
        //删除奖品记录
        $this->RedisCache ( $this->Redis_prefix_name . $params ['shake_id'] )->delPrizeData ( $params ['shake_id']);
        //删除奖品数量
        $this->RedisCache ( $this->Redis_prefix_name . $params ['shake_id'] )->delPrizeNum( $params ['shake_id']);
        //清空所有奖品数量
        $this->RedisCache ( $this->Redis_prefix_name . $params ['shake_id'] )->delPrizeCountNum( $params ['shake_id']);

        return true;
    }

    public function batchdelTypeList($params,$shake_id){
        // 删除奖品队列
        foreach ($params as $row){
            $this->RedisCache ( $this->Redis_prefix_name . $shake_id )->delPrizeTypeList($shake_id.'_'.$row['prize_type']);
        }
     return true;
    }

    /**
     * 奖品修改
     */
    public function Edit($params){
        if(!isset($params['prize_id'])){
            throw new \Exception('参数错误');
        }
        if($params['prize_type']==3){
            $params['prize_nums']= 0;
        }
        $where=['prize_id = '.$params['prize_id']];
        $save=$params;
        unset($save['prize_id']);
        //查询原来的奖品
        $prizeInfo = $this->getOne(['where'=>['prize_id'=>$params['prize_id']]]);
        $params['update_time']=time();
        $result = $this->db()->update('shake_prize')->cols($save)->where($where)->query();
        // 保存奖品详情
        $this->RedisCache ( $this->Redis_prefix_name . $params ['shake_id'] )->setPrizeData ( $params ['shake_id'], $params['prize_id'], $params );
        //获取添加后的单个产品修改的数量
       $remaining = $params['prize_nums'] - $prizeInfo['prize_nums'];
        // 增加删除奖品数量
        $this->RedisCache ( $this->Redis_prefix_name . $params ['shake_id'] )->incrPrizeNum ( $params ['shake_id'], intval ( $remaining ), $params['prize_id'] );
        // 增加删除奖品总数量
        $this->RedisCache ( $this->Redis_prefix_name . $params ['shake_id'] )->incrPrizeCountNum ( $params ['shake_id'], intval ( $remaining ) );

        //增加到奖品队列数量
        $this->RedisCache( $this->Redis_prefix_name . $params ['shake_id'] )->Shakemulti();
        for ($i=0;$i<$remaining;$i++){
            $this->RedisCache( $this->Redis_prefix_name . $params ['shake_id'] )->PrizePushList ( $params ['shake_id'].'_'.$params['prize_type'], 1);
        }
        $this->RedisCache( $this->Redis_prefix_name . $params ['shake_id'] )->Shakeexec();

            return true;
    }

    /**
     * 奖品添加
     */
    public function Add($params){

        $data=[
            'channel_id' 	=> isset($params['channel_id']) ? $params['channel_id'] : 0,
            'shake_id' 	=> isset($params['shake_id']) ? $params['shake_id'] : 0,
            'level' 	=> isset($params['level']) ? $params['level'] : 0,
            'prize_type' => isset($params['prize_type']) ? $params['prize_type'] : 0,
            'prize_name'=> isset($params['prize_name']) ? $params['prize_name'] : '',
            'prize_nums' 		=> isset($params['prize_nums']) ? $params['prize_nums'] : 0,
            'prize_used_nums' => isset($params['prize_used_nums']) ? $params['prize_used_nums'] : 0,
            'winning_rate' => isset($params['winning_rate']) ? $params['winning_rate'] : 0,
            'prize_logo' 		=> isset($params['prize_logo']) ? $params['prize_logo'] : '',
            'create_time' 	=> time(),
            'is_del' 	=> 0,
            'prize_out_id' => isset($params['prize_out_id']) ? intval($params['prize_out_id']) : 0
        ];
        //如果是未中奖
        if($data['prize_type']==3){
            $data['prize_nums']= 0;
        }
		$result = $this->db ()->insert ( 'shake_prize' )->cols ( $data )->query ();
		$data ['prize_id'] = $result;
		// 保存奖品详情
		$this->RedisCache ( $this->Redis_prefix_name . $data ['shake_id'] )->setPrizeData ( $data ['shake_id'], intval ( $result ), $data );
		// 保存奖品数量
		$this->RedisCache ( $this->Redis_prefix_name . $data ['shake_id'] )->incrPrizeNum ( $data ['shake_id'], intval ( $data ['prize_nums'] ), intval ( $result ) );
		// 保存奖品所有数量
		$this->RedisCache ( $this->Redis_prefix_name . $data ['shake_id'] )->incrPrizeCountNum ( $data ['shake_id'], intval ( $data ['prize_nums'] ) );
		//将奖品加入队列

        $this->RedisCache( $this->Redis_prefix_name . $data ['shake_id'] )->Shakemulti();
        for ($i=0;$i<$data['prize_nums'];$i++){
            $this->RedisCache( $this->Redis_prefix_name . $data ['shake_id'] )->PrizePushList ( $data ['shake_id'].'_'.$data['prize_type'], 1);
                }
        $this->RedisCache( $this->Redis_prefix_name . $data ['shake_id'] )->Shakeexec();
		return $result;
    }


    /**
     * 获取单个奖品信息
     */
    public function getOne($params){
        $where = array();
        if (isset($params['where'])) {
            foreach ($params['where'] as $key => $row) {
                if (in_array($key, $this->files)) {
                    $where[] = $key . ' = ' . $row;
                }
            }
        }
        $data = $this->db()->select('*')
            ->from('shake_prize')
            ->where($where)
            ->row();
        return $data;
    }

    /**
     * 获取奖品队列
     */
    public function PrizePullListLen($shake_id,$type){
       return $this->RedisCache( $this->Redis_prefix_name . $shake_id )->PrizePullListLen ( $shake_id.'_'.$type, 1);
    }
    
}