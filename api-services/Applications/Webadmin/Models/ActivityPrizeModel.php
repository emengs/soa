<?php

namespace Webadmin\Models;

use Webadmin\Models\BaseModel;

/**
 *  活动奖项model
 * @author zouzhijia
 */
class ActivityPrizeModel extends BaseModel
{
    Const PRIZETYPE_NULL = '1';
    Const PRIZETYPE_GOODS = '2';
    Const PRIZETYPE_COUPON = '3';
    Const PRIZETYPE_SCORE = '4';
    Const PRIZETYPE_ENUM_ARRAY = array(self::PRIZETYPE_NULL => '未中奖',
      self::PRIZETYPE_GOODS => '实物',
      self::PRIZETYPE_COUPON => '优惠券',
      self::PRIZETYPE_SCORE => '积分');

    public static $_TABLE_NAME = 'activity_prize';
    public static $_ATTRIBUTES = array(
      'prize_id' => array('desc' => '活动奖项编号', 'column' => 'prize_id', 'type' => self::INTEGER, 'length' => 10, 'null' => false, 'AI' => true),
      'activity_id' => array('desc' => '活动id', 'column' => 'activity_id', 'type' => self::INTEGER, 'length' => 10, 'null' => false),
      'wk_prize_id' => array('desc' => '对应的微客多的奖项id', 'column' => 'wk_prize_id', 'type' => self::INTEGER, 'length' => 10, 'null' => true, 'default' => 0),
      'levels' => array('desc' => '奖项级别', 'column' => 'levels', 'type' => self::INTEGER, 'length' => 3, 'null' => false),
      'prize_type' => array('desc' => '奖项类型', 'column' => 'prize_type', 'type' => self::ENUM, 'null' => false, 'default' => self::PRIZETYPE_NULL, 'options' => self::PRIZETYPE_ENUM_ARRAY),
      'prize_name' => array('desc' => '奖项名称', 'column' => 'prize_name', 'type' => self::STRING, 'length' => 50, 'null' => false),
      'prize_image' => array('desc' => '奖品图片', 'column' => 'prize_image', 'type' => self::STRING, 'length' => 255, 'null' => true, 'default' => ''),
      'win_quantity' => array('desc' => '已中奖品数量', 'column' => 'win_quantity', 'type' => self::INTEGER, 'length' => 10, 'default' => 0),
      'quantity' => array('desc' => '数量', 'column' => 'quantity', 'type' => self::INTEGER, 'length' => 10, 'null' => false),
      'win_rate' => array('desc' => '中奖概率', 'column' => 'win_rate', 'type' => self::FLOAT, 'length' => 10, 'null' => false),
      'created' => array('desc' => '创建时间', 'column' => 'created', 'type' => self::TIMESTAMP, 'null' => false, 'default' => 'now'),
      'modified' => array('desc' => '最后修改时间', 'column' => 'modified', 'type' => self::TIMESTAMP, 'null' => false, 'timestamp' => true),
      'deleted' => array('desc' => '状态', 'column' => 'deleted', 'type' => self::ENUM, 'null' => false, 'default' => self::DELETED_OPEN, 'options' => self::DELETED_ENUM_ARRAY),
    );

}