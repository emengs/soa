<?php

namespace Webadmin\Models;

use Webadmin\Models\BaseModel;
use Webadmin\Exception\ValidationException;
use Webadmin\Exception\LoginException;

/**
 *  管理员model
 * @author zouzhijia
 */
class ManageModel extends BaseModel
{




    public static $_TABLE_NAME = 'manages';
    public static $_ATTRIBUTES = array(
        'manage_id' => array('desc' => '管理员编号', 'column' => 'manage_id', 'type' => self::INTEGER, 'length' => 10, 'null' => false, 'AI' => true),
        'channel_id' => array('desc' => '商户id', 'column' => 'shop_id', 'type' => self::INTEGER, 'length' => 10, 'null' => false),
        'user_name' => array('desc' => '用户名称(登陆名)', 'column' => 'user_name', 'type' => self::STRING, 'length' => 30, 'null' => false),
        'password' => array('desc' => '密码', 'column' => 'password', 'type' => self::STRING, 'length' => 50, 'null' => false),
        'created' => array('desc' => '创建时间', 'column' => 'created', 'type' => self::TIMESTAMP, 'null' => false, 'default' => 'now'),
        'modified' => array('desc' => '最后修改时间', 'column' => 'modified', 'type' => self::TIMESTAMP, 'null' => false, 'timestamp' => true),
        'deleted' => array('desc' => '状态', 'column' => 'deleted', 'type' => self::ENUM, 'null' => false, 'default' => self::DELETED_OPEN),
    );

    /**
     * 修改密码
     * @param $params 参数数组
     * @return
     */
    public function changePassword($params)
    {
        $manage_id = isset($params['manage_id']) ? $params['manage_id'] : false;
        $password = isset($params['password']) ? $params['password'] : false;
        $old_password = isset($params['old_password']) ? $params['old_password'] : false;
        if (!$manage_id)
        {
            throw new ValidationException('40221');
        }
        $users = $this->get($manage_id);
        if (empty($users))
        {
            throw new ValidationException('40222');
        }
        if (md5($old_password) != $users['password'])
        {
            throw new ValidationException('40231');
        }
        $users['password'] = md5($password);
        $data = $this->update($users);
        return $data;
    }

    /**
     * 登陆
     * @param $params 参数数组
     * @return
     */
    public function login($params)
    {
        $user_name = isset($params['user_name']) ? $params['user_name'] : false;
        $password = isset($params['password']) ? $params['password'] : false;
        $captcha_token = isset($params['captcha_token']) ? $params['captcha_token'] : '';
        $captcha = isset($params['captcha']) ? $params['captcha'] : false;
        if ((!$user_name) || (!$password))
        {
            throw new ValidationException('40223');
        }

        if (!$captcha)
        {
            throw new ValidationException('40224');
        }
        $captcha_token_cache = $this->RedisCache($this->RedisCaptcahPrefix)->getRandom($captcha_token);
        if ($captcha_token_cache != $captcha)
        {
            throw new ValidationException('40225');
        }
        $password_md5 = md5($password);
      
        $where = array('user_name = :user_name', 'password = :password', 'deleted = :deleted');
        $bind_values = array('user_name' => $user_name, 'password' => $password_md5, 'deleted' => BaseModel::DELETED_OPEN);
        $res = $this->db()
            ->select('*')
            ->from(self::$_TABLE_NAME)
            ->where($where)
            ->bindValues($bind_values)
            ->row();

        if (empty($res))
        {
            throw new ValidationException(40226);
        }
        return $res;
    }
}