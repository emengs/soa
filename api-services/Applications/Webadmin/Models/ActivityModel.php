<?php

namespace Webadmin\Models;

use Webadmin\Models\BaseModel;
use Webadmin\Models\ActivityPrizeModel;
use Webadmin\Models\AgentModel;
use Webadmin\Exception\ValidationException;
use System\Libraries\Weikeduo;
use System\Phpexcel\Excel;
use Common\HttpRequest;

/**
 *  活动model
 * @author zouzhijia
 */
class ActivityModel extends BaseModel
{
    Const ACTIVITYTYPE_TURNTABLE = '1';
    Const ACTIVITYTYPE_GOLDENEGGS = '2';
    Const ACTIVITYTYPE_ENUM_ARRAY = array(self::ACTIVITYTYPE_TURNTABLE => '大转盘',
      self::ACTIVITYTYPE_GOLDENEGGS => '砸金蛋');
    Const CONDITION_UNLIMITED = '1';
    Const CONDITION_SUBSCRIBL = '2';
    Const CONDITION_ENUM_ARRAY = array(self::CONDITION_UNLIMITED => '不限',
      self::CONDITION_SUBSCRIBL => '关注公众号');

    public static $_TABLE_NAME = 'activity';
    public static $_ATTRIBUTES = array(
      'activity_id' => array('desc' => '活动编号', 'column' => 'activity_id', 'type' => self::INTEGER, 'length' => 11, 'null' => false, 'AI' => true),
      'agent_id' => array('desc' => '商户id', 'column' => 'agent_id', 'type' => self::INTEGER, 'length' => 11, 'null' => false),
      'qrcode_id' => array('desc' => '二维码id', 'column' => 'qrcode_id', 'type' => self::INTEGER, 'length' => 11, 'null' => false),
      'activity_name' => array('desc' => '活动名称', 'column' => 'activity_name', 'type' => self::STRING, 'length' => 50, 'null' => false),
      'activity_type' => array('desc' => '状态', 'column' => 'activity_type', 'type' => self::ENUM, 'null' => false, 'default' => self::ACTIVITYTYPE_TURNTABLE, 'options' => self::ACTIVITYTYPE_ENUM_ARRAY),
      'activity_start' => array('desc' => '活动开始时间', 'column' => 'activity_start', 'type' => self::TIMESTAMP, 'null' => false),
      'activity_end' => array('desc' => '活动开始时间', 'column' => 'activity_end', 'type' => self::TIMESTAMP, 'null' => false),
      'cash_end' => array('desc' => '兑奖截止时间', 'column' => 'cash_end', 'type' => self::TIMESTAMP, 'null' => false),
      'condition' => array('desc' => '参与条件', 'column' => 'condition', 'type' => self::ENUM, 'null' => false, 'default' => self::CONDITION_UNLIMITED, 'options' => self::CONDITION_ENUM_ARRAY),
      'total_limit' => array('desc' => '总参与次数限制', 'column' => 'total_limit', 'type' => self::INTEGER, 'length' => 11, 'null' => false),
      'subscribe_limit' => array('desc' => '已关注用户数限制', 'column' => 'subscribe_limit', 'type' => self::INTEGER, 'length' => 11, 'null' => false),
      'unsubscribe_limit' => array('desc' => '未关注用户数限制', 'column' => 'unsubscribe_limit', 'type' => self::INTEGER, 'length' => 11, 'null' => false),
      'prize_explain' => array('desc' => '奖项说明', 'column' => 'prize_explain', 'type' => self::STRING, 'null' => false, 'default' => ''),
      'activity_rules' => array('desc' => '活动规则', 'column' => 'activity_rules', 'type' => self::STRING, 'null' => false, 'default' => ''),
      'created' => array('desc' => '创建时间', 'column' => 'created', 'type' => self::TIMESTAMP, 'null' => false, 'default' => 'now'),
      'modified' => array('desc' => '最后修改时间', 'column' => 'modified', 'type' => self::TIMESTAMP, 'null' => false, 'timestamp' => true),
      'deleted' => array('desc' => '状态', 'column' => 'deleted', 'type' => self::ENUM, 'null' => false, 'default' => self::DELETED_OPEN, 'options' => self::DELETED_ENUM_ARRAY),
    );

    /**
     * 添加活动
     * @param $params 参数数组
     * @return 
     */
    public function activityInsert($params)
    {
        $this->checkParams($params);
        $params['deleted'] = ActivityModel::DELETED_CLOSE;  //创建默认关闭
        try
        {
            $this->db()->beginTrans();

            $activity_id = $this->insert($params);    //增加活动记录

            $activity_prize_array = $params['activity_prize'];
            $model_prize = new ActivityPrizeModel();
            foreach ($activity_prize_array as $activity_prize)
            {
                $activity_prize['activity_id'] = $activity_id;
                $model_prize->insert($activity_prize); //增加活动奖项
            }

            $this->db()->commitTrans();
        }
        catch (\Exception $e)
        {
            $this->db()->rollBackTrans();
            throw $e;
        }
    }

    /**
     * 修改活动
     * @param $params 参数数组
     * @return 
     */
    public function activityUpdate($params)
    {
        $this->checkParams($params);
        $activity_id = $params['activity_id'];
        $activity = $this->get($activity_id);
        if ((!empty($activity)) && ($activity['deleted'] == self::DELETED_OPEN))
        {
            throw new ValidationException(40229); //编辑活动，必须关闭该活动
        }
        try
        {
            $this->db()->beginTrans();

            $data = $this->update($params);    //修改活动记录

            $activity_prize_array = $params['activity_prize'];
            $model_prize = new ActivityPrizeModel();
            foreach ($activity_prize_array as $activity_prize)
            {
                $activity_prize['activity_id'] = $activity_id;
                if (isset($activity_prize['prize_id']))
                {
                    $model_prize->update($activity_prize); //修改活动奖项
                }
                else
                {
                    $model_prize->insert($activity_prize); //增加活动奖项
                }
            }

            //检查中奖设置
            $where = array("activity_id = :activity_id", "deleted = :deleted");
            $bind_values = array('activity_id' => $activity_id, 'deleted' => BaseModel::DELETED_OPEN);
            $res = $this->db()
              ->select('count(prize_id) count,sum(win_rate) sum')
              ->from(ActivityPrizeModel::$_TABLE_NAME)
              ->where($where)
              ->bindValues($bind_values)
              ->row();
            $count_p = $res['count'];
            $rate_amount = $res['sum'];
            if ($count_p < 4) //还有个未中奖的数据
            {
                throw new ValidationException(40205); //至少3个奖项
            }
            elseif ($count_p > 6)
            {
                throw new ValidationException(40206); //至多设置5个奖项
            }
            if ($rate_amount > 100)
            {
                throw new ValidationException(40207); //中奖概率和至多100
            }

            //更新缓存
            $prize = $this->getActivityPrizes($activity_id);     //奖项设置数据
            $data['prizeInfo'] = $prize;
            $this->RedisCache()->hMsetActivity($activity_id, $data);
            $this->db()->commitTrans();
        }
        catch (\Exception $e)
        {
            $this->db()->rollBackTrans();
            throw $e;
        }
    }

    /**
     * 请求参数校验
     * @param $params 参数数组
     * @return 
     */
    private function checkParams($params)
    {
        bcscale(2);
        $activity_start = isset($params['activity_start']) ? $params['activity_start'] : 0;
        $activity_end = isset($params['activity_end']) ? $params['activity_end'] : 0;
        if (bccomp($activity_start, $activity_end) >= 0)
        {
            throw new ValidationException(40203);
        }

        //奖项设置验证 至多设置5个奖项，至少3个奖项
        $activity_prize_array = isset($params['activity_prize']) ? $params['activity_prize'] : [];
        if (empty($activity_prize_array))
        {
            throw new ValidationException(40204);
        }

        $count_p = count($activity_prize_array);
        $rate_amount = 0;
        foreach ($activity_prize_array as $activity_prize)
        {
            if ((isset($activity_prize['prize_id'])) && (isset($activity_prize['deleted'])) && ($activity_prize['deleted'] == BaseModel::DELETED_DEL))
            {
                $count_p--; //删除的活动
            }
            else
            {
                $rate_amount = bcadd($rate_amount, $activity_prize['win_rate']);
            }
        }
        if ($count_p < 4)    //还有个未中奖的数据
        {
            throw new ValidationException(40205); //至少3个奖项
        }
        elseif ($count_p > 6)
        {
            throw new ValidationException(40206); //至多设置5个奖项
        }
        if ($rate_amount > 100)
        {
            throw new ValidationException(40207); //中奖概率和至多100
        }
    }

    /**
     * 修改删除状态
     * @param $params 请求参数
     * @return 
     */
    public function updateDel($params)
    {
        $deleted = isset($params[self::DELETED]) ? $params[self::DELETED] : false;
        $activity_id = isset($params['activity_id']) ? $params['activity_id'] : false;
        if (!$deleted)
        {
            throw new ValidationException(40218);
        }

        $activity = $this->get($activity_id);
        if (empty($activity))
        {
            throw new ValidationException(40220);
        }
        $qrcode_id = $activity['qrcode_id'];

        if ($deleted == BaseModel::DELETED_OPEN)
        {
            //检查是否有其他活动是开启的
            $where = array("qrcode_id = :qrcode_id", "deleted = :deleted");
            $bind_values = array('qrcode_id' => $qrcode_id, 'deleted' => BaseModel::DELETED_OPEN);
            $res_open = $this->db()
              ->select('activity_id')
              ->from(ActivityModel::$_TABLE_NAME)
              ->where($where)
              ->bindValues($bind_values)
              ->query();
            if (!empty($res_open))
            {
                throw new ValidationException(40219);  //开活动前先停掉其他活动
            }

            $res = $this->updateDeleted($params);
            if (!empty($res))
            {
                //调整二维码有效活动的缓存
                $this->RedisCache()->setEnableQrcode($qrcode_id, $activity_id . '_' . $params['agent_id']);
            }
        }
        else
        {
            $res = $this->updateDeleted($params);
            if (!empty($res))
            {
                //清楚缓存
                $this->RedisCache()->delEnableQrcode($qrcode_id);
            }
        }
        return $res;
    }

    /**
     * 通过二维码ID获取正活跃的活动  默认从缓存取
     * @param $qrcode_id 二维码id
     * @return 
     */
    public function getActivityByQrcode($qrcode_id)
    {
        $res = [];
        //获取缓存数据
        $enable_activity = $this->RedisCache()->getEnableQrcode($qrcode_id);
        if (!$enable_activity)  //缓存没数据
        {
            $where = array("qrcode_id = :qrcode_id", "deleted = :deleted");
            $bind_values = array('qrcode_id' => $qrcode_id, 'deleted' => BaseModel::DELETED_OPEN);
            $res = $this->db()
              ->select('*')
              ->from(self::$_TABLE_NAME)
              ->where($where)
              ->bindValues($bind_values)
              ->row();
            if (!empty($res))
            {
                //奖项设置数据
                $res_prize = $this->getActivityPrizes($res['activity_id']);
                $res ['prizeInfo'] = $res_prize;
                //存缓存
                $this->RedisCache()->setEnableQrcode($qrcode_id, $res['activity_id'] . '_' . $res['agent_id']);
                $this->RedisCache()->hMsetActivity($res['activity_id'], $res);
            }
        }
        else
        {
            $enable_activity_array = explode('_', $enable_activity);
            $activity_id = $enable_activity_array[0];
            $res = $this->RedisCache()->hMgetActivity($activity_id); //从缓存获取
            if (empty($res))
            {
                $res = $this->get($activity_id);
                if (!empty($res))
                {
                    //奖项设置数据
                    $res_prize = $this->getActivityPrizes($activity_id);
                    $res ['prizeInfo'] = $res_prize;
                    $this->RedisCache()->hMsetActivity($activity_id, $res);
                }
            }
        }

        return $res;
    }

    /**
     * 通过活动id获取活动奖项设置
     * @param $activity_id 活动id
     * @return 
     */
    public function getActivityPrizes($activity_id)
    {
        $where = array("activity_id = :activity_id", "deleted = :deleted");
        $bind_values = array('activity_id' => $activity_id, 'deleted' => BaseModel::DELETED_OPEN);
        $res = $this->db()
          ->select('*')
          ->from(ActivityPrizeModel::$_TABLE_NAME)
          ->where($where)
          ->bindValues($bind_values)
          ->query();
        return $res;
    }

    /**
     * 获取WKD卡券
     * @param array $param
     * @return string
     */
    public function getCouponList($param)
    {
        $model_agent = new AgentModel();
        $agent_id = $param['agent_id'];
        $agentConfig = $model_agent->get($agent_id);
        if (empty($agentConfig))
        {
            throw new ValidationException(40216);
        }

        $access_token = $model_agent->getAccessToken($agent_id, $agentConfig);
        if (!$access_token)
        {
            throw new ValidationException(40217);
        }
        //获取WKD卡券
        $weikeduo = new Weikeduo($agentConfig);
        $param['page'] = isset($param['page']) ? ($param['page'] - 1) : 0;
        $param['count'] = isset($param['limit']) ? $param['limit'] : 100;
        $wkd_res_coupons = $weikeduo->card_coupon($access_token, $param);
        $wkd_coupons = array();
        if (isset($wkd_res_coupons['errcode']) && $wkd_res_coupons['errcode'] == 0)
        {
            $wkd_coupons = $wkd_res_coupons['errmsg'];
        }
        return $wkd_coupons;
    }
}