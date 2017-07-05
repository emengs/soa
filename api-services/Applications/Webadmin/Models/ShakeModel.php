<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/8
 * Time: 11:29
 * 摇一摇活动
 */
namespace Webadmin\Models;

use Webadmin\Exception\ValidationException;
use Webadmin\Models\ShakePrizeModel;

class ShakeModel extends BaseModel
{

    private $files = ['shake_id', 'channel_id', 'title', 'start_time', 'end_time', 'more_type', 'more_num', 'drawn_type', 'valid_distance', 'address', 'longitude', 'latitude', 'description', 'activity_rule','activity_status', 'create_time','update_time','is_del'];

    /**
     * 获取列表数据
     */
    public function getList($params)
    {

        $new_db = clone $this->db();
        $where = array();
        $query = $this->db()->select('*')
            ->from('shake');
        if (isset($params['where'])) {
            foreach ($params['where'] as $key => $row) {
                if (in_array($key, $this->files)) {
                    if (!is_numeric($row)) {
                        $row = "'" . $row . "'";
                    }
                    $where[] = $key . ' = ' . $row;
                }
            }
            $query = $query->where($where);
        }
        $query->orderByDESC(['shake_id']);
        if (isset($params['page']) && isset($params['limit'])) {
            $count_query = $new_db->select('count(*) as cot')
                ->from('shake');
            if ($where) {
                $count_query = $count_query->where($where);
            }
            $count = $count_query->row();
            $offset = ($params['page'] - 1) * $params['limit'];
            $query = $query->limit($params['limit'])
                ->offset($offset);
            $info['page']['per_page'] = $params['limit'];
            $info['page']['total_count'] = $count['cot'];
            $info['page']['current_page'] = $params['page'];
            $info['page']['total_page'] = ceil($count['cot'] / $params['limit']);
        }
        $data = $query->query();
        echo date('Y-m-d H:i:s'), $query->lastSQL(), PHP_EOL;

        $info['lists'] = $data;

        return $info;
    }

    /**
     * 获取单个活动详情
     */
    public function getOne($params)
    {
        $where = array();
        if (isset($params['where'])) {
            foreach ($params['where'] as $key => $row) {
                if (in_array($key, $this->files)) {
                    if (!is_numeric($row)) {
                        $row = "'" . $row . "'";
                    }
                    $where[] = $key . ' = ' . $row;
                }
            }
        }
        $data = $this->db()->select('*')
            ->from('shake')
            ->where($where)
            ->row();
        return $data;
    }


    /**
     * 添加活动
     */
    public function Add($params)
    {
        $data = array(
            'channel_id' => isset($params['channel_id']) ? $params['channel_id'] : 0,
            'title' => isset($params['title']) ? $params['title'] : '',
            'start_time' => isset($params['start_time']) ? $params['start_time'] : 0,
            'end_time' => isset($params['end_time']) ? $params['end_time'] : 0,
            'more_type' => isset($params['more_type']) ? $params['more_type'] : 1,
            'more_num' => isset($params['more_num']) ? $params['more_num'] : 0,
            'drawn_type' => isset($params['drawn_type']) ? $params['drawn_type'] : 2,
            'valid_distance' => isset($params['valid_distance']) ? $params['valid_distance'] : 0,
            'address' => isset($params['address']) ? $params['address'] : ' ',
            'longitude' => isset($params['longitude']) ? $params['longitude'] : 0,
            'latitude' => isset($params['latitude']) ? $params['latitude'] : 0,
            'description' => isset($params['description']) ? $params['description'] : '',
            'activity_rule' => isset($params['activity_rule']) ? $params['activity_rule'] : '',
            'activity_status' => 0,
            'create_time' => time(),
            'is_del' => 0
        );

        if ($data['start_time']) {
            $data['start_time'] = strtotime($data['start_time']);
        }
        if ($data['end_time']) {
            $data['end_time'] = strtotime($data['end_time']);
        }

        $this->db()->beginTrans();

        $prizeModel = new ShakePrizeModel();

        try {
            $shakeId = $this->db()->insert('shake')->cols($data)->query();
            // 批量添加奖品

            if (isset ($params ['prize']) && $params ['prize']) {
                $prizeModel->batchAdd($params ['prize'], $shakeId, $params ['channel_id']);
            }
            // 将活动信息录入缓存
            $this->setShakeDataCache($shakeId);
            $this->db()->commitTrans();
            return true;
        } catch (\Exception $ex) {
            $this->db()->rollBackTrans();
            throw $ex;
        }
    }

    /**
     * 存入缓存
     */
    public function setShakeDataCache($shake_id)
    {
        $params['where'] = ['shake_id' => $shake_id];
        $data = $this->getOne($params);
        //将活动信息录入缓存
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->setShakeData($shake_id, $data);
    }


    /**
     * 修改活动
     */
    public function Edit($params)
    {

        if (!isset($params['shake_id'])) {
            return false;
        };
        $prize = 0;
        $shake_id = $params['shake_id'];
        unset($params['shake_id']);
        $where = array("shake_id=" . $shake_id);
        if (isset($params['prize'])) {
            $prize = $params['prize'];
            unset($params['prize']);
        }
        $new_data = [];
        foreach ($params as $key => $row) {
            if (in_array($key, $this->files)) {
                $new_data[$key] = $row;
            }
        }
        $new_data['update_time']=time();
        $this->db()->beginTrans();
        $prizeModel = new ShakePrizeModel();

        try {

            //修改活动
            $rs = $this->db()->update('shake')->cols($new_data)->where($where)->query();
            //批量删除奖品
            if ($prize) {
                $prizeModel->Del(['shake_id' => $shake_id]);
                //批量删除key
                $rs= $prizeModel->batchdelTypeList($prize, $shake_id);
                //批量添加
                $prizeModel->batchAdd($prize, $shake_id, $params['channel_id']);

            }
//            if ($prize) {
//                // 修改奖品
//                foreach ($prize as $row) {
//                    $row['shake_id'] = $shake_id;
//                    $row['channel_id'] = $params['channel_id'];
//                    if (isset($row['prize_id'])) {
//                        $prizeModel->Edit($row);
//                    } else {
//                        $prizeModel->Add($row);
//                    }
//                }
//            }
            $this->db()->commitTrans();
            //将活动信息录入缓存
            $this->setShakeDataCache($shake_id);
            return true;
            }catch (\Exception $ex) {
            $this->db()->rollBackTrans();

            throw $ex;
        }

    }

    /**
     * 打开关闭活动
     */
    public function Open($params)
    {
        $shake_id = $params['shake_id'];
        unset($params['shake_id']);
        $where = array("shake_id=" . $shake_id);

        $new_data = [];
        foreach ($params as $key => $row) {
            if (in_array($key, $this->files)) {
                $new_data[$key] = $row;
            }
        }
        $new_data['update_time']=time();
        $this->db()->beginTrans();
        try {
            $rs = $this->db()->update('shake')->cols($new_data)->where($where)->query();
            if ($params['activity_status'] == 1) {
                $where['where'] = ['activity_status' => 1];
                $shake = $this->getList($where);
                if (count($shake['lists']) >= 2) {
                    throw new ValidationException(40232);
                }
            }

            if ($params['activity_status'] == 1) {
                // 将活动信息加入到已开启活动中缓存
                $this->setShakeActivedData($shake_id);
                $this->setShakeDataCache($shake_id);
            } else {
                //清除已开启活动
                $this->ClearShakeActivedData();
                $this->setShakeDataCache($shake_id);
            }
            $this->db()->commitTrans();
            return $rs;
        } catch (\Exception $e) {
            $this->db()->rollBackTrans();
            throw $e;
        }
    }


    /**
     *  将活动信息加入到已开启活动中缓存
     * @param $shake_id
     */
    public function setShakeActivedData($shake_id)
    {
        //查询活动详情
        $params['where'] = ['shake_id' => $shake_id];
        $data = $this->getOne($params);
        $this->RedisCache($this->RedisPrefixSetting)->setShakeActivedData($data);
    }

    /**
     * 清除已开启活动中的缓存
     */
    public function ClearShakeActivedData()
    {
        $this->RedisCache($this->RedisPrefixSetting)->ClearShakeActivedData();
    }

    /**
     *  获取当前访问人数
     */
    public function getvisitscurrent($shake_id){
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->getCutNum('visits_current');
    }

    /**
     *  获取当前访问人数
     */
    public function getvisitstotal($shake_id){
        return $this->RedisCache($this->Redis_prefix_name . $shake_id)->getCutNum('visits_total');
    }



}