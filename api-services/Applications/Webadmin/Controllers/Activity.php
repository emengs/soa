<?php

namespace Webadmin\Controllers;

use Webadmin\Controllers\Base;
use Webadmin\Models\ActivityModel;
use Webadmin\Models\StatisticsModel;
use Webadmin\Models\BaseModel;
use Webadmin\Models\UtilModel;
use Webadmin\Models\ExcelModel;
use Webadmin\Exception\ValidationException;

/**
 *  活动控制器
 * @author zhijiazou
 */
class Activity extends Base
{

    public function __construct($params)
    {
        $this->params = $params['params'];
        parent::__construct($this->params);
    }

    /**
     * 控制器入口，放列表或入口逻辑
     * @return type
     */
    public function index()
    {
        $action = isset($this->params['action']) ? $this->params['action'] : 'list';
        $data = [];
        switch ($action)
        {
            case 'list':  //查看活动记录
                $data = $this->getActivityList();
                break;
            case 'export':  //导出活动记录
                $data = $this->getActivityList(TRUE);
                break;
            case 'coupon':  //获取WKD卡券
                $data = $this->getCouponList();
                break;
            default :
                throw new ValidationException(40101);
        }
        return $data;
    }

    /**
     * 单条数据获取
     * @return type
     */
    public function view()
    {
        $model = new ActivityModel();
        $activity_id = isset($this->params['activity_id']) ? $this->params['activity_id'] : false;
        if (!$activity_id)
        {
            throw new ValidationException(40209);
        }
        $data = $model->get($activity_id);
        if (!empty($data))
        {
            $data['activity_prize'] = $model->getActivityPrizes($activity_id);
        }
        return $data;
    }

    /**
     * 数据创建
     * @return type
     */
    public function create()
    {
        $model_activity = new ActivityModel();
        $data = $model_activity->activityInsert($this->params);
        return $data;
    }

    /**
     * 数据更新
     */
    public function update()
    {
        $model_activity = new ActivityModel();
        $data = $model_activity->activityUpdate($this->params);
        return $data;
    }

    /**
     * 数据deleted状态修改
     */
    public function delete()
    {
        $model_activity = new ActivityModel;
        $data = $model_activity->updateDel($this->params);
        return $data;
    }

    /**
     * 查询
     */
    public function getActivityList($export = FALSE)
    {
        $page = isset($this->params['page']) ? $this->params['page'] : 1;
        $limit = isset($this->params['limit']) ? $this->params['limit'] : 5;
        $offset = ($page - 1) * $limit;
        $where = array('activity.agent_id = :agent_id', 'activity.deleted <> :deleted');
        $bind_values = array('agent_id' => $this->params['agent_id'], 'deleted' => ActivityModel::DELETED_DEL);
        if (isset($this->params['qrcode_id']))
        {
            $where[] = 'activity.qrcode_id = :qrcode_id';
            $bind_values['qrcode_id'] = $this->params['qrcode_id'];
        }
        else
        {
            throw new ValidationException(40208);
        }
        if (!empty($this->params['activity_name']))
        {
            $where[] = 'activity.activity_name LIKE :activity_name';
            $activity_name = $this->params['activity_name'];
            $bind_values['activity_name'] = "%$activity_name%";
        }
        if (!empty($this->params['activity_start']))
        {
            $activity_start = $this->params['activity_start'];
            $where[] = 'activity.activity_start >= :activity_start';
            $bind_values['activity_start'] = $activity_start;
        }
        if (!empty($this->params['activity_end']))
        {
            $activity_end = $this->params['activity_end'];
            $where[] = 'activity.activity_end <= :activity_end';
            $bind_values['activity_end'] = $activity_end;
        }
        if (!empty($this->params['activity_ids']))
        {
            $where[] = 'activity.activity_id IN (' . $this->params['activity_ids'] . ')';
        }
        $model_activity = new ActivityModel();
        $count = $model_activity->db()->select('count(activity_id) as count')
          ->from(ActivityModel::$_TABLE_NAME)
          ->where($where)
          ->bindValues($bind_values)
          ->row();

        if ($export == FALSE)
        {
            $model_activity->db()->limit($limit)->offset($offset);
        }
        $res = $model_activity->db()->select('activity.*,statistics_draw.subscribe_nums,'
            . 'statistics_draw.unsubscribe_nums,statistics_draw.subscribe_member_amount,'
            . 'statistics_draw.join_member_amount,statistics_draw.transform_amount')
          ->from(ActivityModel::$_TABLE_NAME)
          ->leftJoin('statistics_draw', 'ON statistics_draw.activity_id = activity.activity_id')
          ->where($where)
          ->bindValues($bind_values)
          ->orderByDESC(['activity.activity_id'])
          ->query();

        if ($export != FALSE)
        {
            $model_excel = new ExcelModel();
            $data = $model_excel->exportActivity($res, $this->params['agent_id']);
        }
        else
        {
            $data = array(
              'list' => $res,
              'page' => ['total_count' => $count['count'],
                'current_page' => $page,
                'page_size' => $limit,
                'total_page' => ceil($count['count'] / $limit)],
            );
        }
        return $data;
    }

    /**
     * 获取WKD卡券
     */
    public function getCouponList()
    {
        $model_activity = new ActivityModel();
        $data = $model_activity->getCouponList($this->params);
        return array(
          'list' => $data['data'],
          'page' => ['total_count' => $data['page']['total_count'],
            'current_page' => $data['page']['current_page'] + 1,
            'page_size' => $data['page']['per_page'],
            'total_page' => $data['page']['total_page']],
        );
    }
}