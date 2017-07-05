<?php
/**
 * Created by PhpStorm.
 * User: jiazhengsheng
 * Date: 2017/6/8
 * Time: 16:59
 * 摇一摇配置信息
 */
namespace Weixin\Models;

class ShakeSettingModel extends BaseModel
{
    private $files=['setting_id','shop_id','setting_key','setting_value','created'];

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
     * 获取单个数据
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
            ->from('shake_setting')
            ->where($where)
            ->row();
        return $data;
    }


    /**
     * 添加
     */
    public function add(){

    }


    /**
     * 修改
     */
    public function Edit(){

    }


    /**
     * 删除
     */
    public function Del(){

    }

}