<?php

namespace Console\Models;

use System\Core\Connection;
use System\Libraries\ShakeRedis;
use Webadmin\Exception\ValidationException;


/**
 *  基础Model
 * @author zhijiazou
 */
class BaseModel
{

    private $_db_config;
    private $_redis_config;
    protected static $dbo;
    protected $Redis_prefix_name='shake_';
    protected $RdisQueuePrefix = 'shake_queue';
    protected $RedisOpenAccessTokenPrefix = 'open_access_token';
    protected $RedisCaptcahPrefix='shake_captcah';
    protected $RedisPrefixSetting='shake_setting';
    protected $RedisTaskPrefix = 'shake_task';

    public function __construct()
    {
        $this->_db_config = console_load_config('Config_'.ENV,'database');
        $this->_redis_config = console_load_config('Config_'.ENV, 'redis');

//        $this->_db_config = webadmin_load_config('config','database');
//        $this->_redis_config = webadmin_load_config('config','redis');
    }

    /**
     * 载入默认的db
     * @param type $db
     * @return type
     */
    public function db($db = 'default')
    {
        $p_dbo = self::$dbo;
        //数据库连接对象
        if (empty($p_dbo))
        {
            self::$dbo = Connection::getInstance($this->_db_config[$db]);
        }

        return self::$dbo;
    }

    /**
     * 载入默认的db
     * @param type $db
     * @return type
     */
    public function RedisCache($expire)
    {
        $config=$this->_redis_config;
        $config['prefix']=$this->_redis_config['prefix'].$expire;
        return new ShakeRedis($config);
    }

    /**
     * 请求参数绑定
     * @param $request 参数数组
     * @param $attr_defines 字段定义
     * @return type
     */
    public function bindRequest($request)
    {
        $ret = array();
        $attr_defines = $this::$_ATTRIBUTES;
        foreach ($attr_defines as $attr_name => $attr_define)
        {
            $column = $attr_define[self::COLUMN];
            if (!isset($request[$column]))
            {
                continue;
            }

            $value = $request[$column];
            if ($value === null || $value === "")
            {
                $value = null;
            }
            switch ($attr_define[self::TYPE])
            {
                case self::MAP:
                    break;
                case self::SET:
                    break;
                case self::OBJECT:
                    break;
                case self::TIMESTAMP:
                    $ret[$column] = (is_numeric($value)) ? $value : strtotime($value);
                    break;
                case self::DATE:
                    $ret[$column] = (is_numeric($value)) ? date('Y-m-d', $value) : $value;
                    break;
                case self::DATETIME:
                    $ret[$column] = (is_numeric($value)) ? date('Y-m-d H:i:s', $value) : $value;
                    break;
                case self::INTEGER:
                    $ret[$column] = intval($value);
                    break;
                case self::FLOAT:
                    $ret[$column] = floatval($value);
                    break;
                default:
                    $ret[$column] = $value;
                    break;
            }
        }
        return $ret;
    }

    /**
     * 请求参数校验
     * @param $params 参数数组
     * @param $attr_defines 字段定义
     * @param $new 数据是否是新增
     * @return type
     */
    public function validator($params, $attr_defines = [], $new = false)
    {
        $primary_key_name = false;
        foreach ($attr_defines as $attr_name => $attr_define)
        {
            //自增主键
            if (isset($attr_define[self::AI]) && $attr_define[self::AI] === true)
            {
                $primary_key_name = $attr_name;
                continue;
            }

            //是否能为null，默认是 允许
            $can_be_null = true;
            if (isset($attr_define[self::NUL]) && $attr_define[self::NUL] == false)
            {
                $can_be_null = false;
            }

            $value = isset($params[$attr_name]) ? $params[$attr_name] : null;
            switch ($attr_define[self::TYPE])
            {
                case self::ENUM:
                    //如果类型是枚举，必须有默认值
                    if (!isset($attr_define[self::DFT]))
                    {
                        throw new ValidationException("ENUM column [$attr_name] default not defined");
                    }
                    elseif ($value == null)
                    {
                        $value = $attr_define[self::DFT];
                    }

                    $enum_array = $attr_define[self::OPTIONS];
                    //检查枚举属性的值，是否在enum_array里面
                    if (!isset($enum_array[$value]))
                    {
                        throw new ValidationException("ENUM column [$attr_name] value {$value} not in enum_array");
                    }
                    break;
                case self::DATE:
                case self::DATETIME:
                    if ($value === null)
                    {
                        if ($can_be_null === false)
                        {
                            throw new ValidationException("column [$attr_name] can't be null");
                        }
                    }
                    else
                    {
                        //判断日期格式是否正确
                        $value_datetime = new \DateTime($value);
                        if ($value_datetime === FALSE)
                        {
                            throw new ValidationException("column [$attr_name] value {$value} is not {$attr_define[self::TYPE]}");
                        }
                    }
                    break;
                case self::TIMESTAMP:
                    //如果是 时间戳 字段，不做任何检查了(created modified跳过验证)
                    if ((isset($attr_define[self::TIMESTAMP]) && $attr_define[self::TIMESTAMP] == true) || (isset($attr_define[self::DFT])))
                    {
                        break;
                    }
                    //是否允许为空的判断
                    if ($value === null)
                    {
                        if ($can_be_null === false)
                        {
                            throw new ValidationException("column [$attr_name] can't be null");
                        }
                    }
                    else
                    {
                        //判断日期格式是否正确
                        if (!is_numeric($value))
                        {
                            throw new ValidationException("column [$attr_name] value {$value} is not {$attr_define[self::TYPE]}");
                        }
                    }
                    break;
                case self::STRING:
                    //是否允许为空的判断
                    if ($value === null && $can_be_null === false)
                    {
                        throw new ValidationException("column [$attr_name] can't be null");
                    }
                    if ($value !== null && isset($attr_define[self::LENGTH]))
                    {
                        //判断字符串长度
                        $max_length = $attr_define[self::LENGTH];
                        if ($max_length > 0)
                        {
                            if (mb_strlen($value) > $max_length)
                            {
                                throw new ValidationException("column [$attr_name] value length is over {$max_length}");
                            }
                        }
                    }
                    break;
                case self::INTEGER:
                    //是否允许为空的判断
                    if ($value === null && $can_be_null === false)
                    {
                        throw new ValidationException("column [$attr_name] can't be null");
                    }
                    break;
                case self::FLOAT:
                    //是否允许为空的判断
                    if ($value === null && $can_be_null === false)
                    {
                        throw new ValidationException("column [$attr_name] can't be null");
                    }
                    break;
                case self::OBJECT:
                case self::MAP:
                case self::SET:
                    break;
                default:
                    throw new ValidationException("ENUM column [$attr_name] type {$attr_define[self::TYPE]} not supported");
            }
        }

        if ($new === true)
        {
            //如果是自增长主键，插入数据之前，属性必须为null
            if ((isset($params[$primary_key_name])) && ($params[$primary_key_name] !== null))
            {
                throw new ValidationException("AI column [$primary_key_name] must be null");
            }
        }
        else
        {
            //Update 必须有主键
            if ((!isset($params[$primary_key_name])) || ($params[$primary_key_name] === null))
            {
                throw new ValidationException("Primary Key column [$primary_key_name] can't be null");
            }
        }
        return true;
    }

    /**
     * 新增信息
     * @param $params 参数数组
     * @return string
     */
    public function insert($params)
    {
        $attr_defines = $this::$_ATTRIBUTES;
        try
        {
            $fields = $this->bindRequest($params);
            //先检查合法性
            $this->validator($fields, $attr_defines, true);
            foreach ($attr_defines as $attr_name => $attr_define)
            {
                $attr_value = isset($fields[$attr_define[self::COLUMN]]) ? $fields[$attr_define[self::COLUMN]] : null;
                switch ($attr_define[self::TYPE])
                {
                    case self::ENUM:
                        //如果类型是枚举，必须有默认值
                        if ($attr_value == null)
                        {
                            $fields[$attr_name] = $attr_define[self::DFT];
                        }
                        break;
                    case self::DATE:
                        if (isset($attr_define[self::DFT]) && $attr_define[self::DFT] === self::NOW && ( $attr_value === null || $attr_value === self::NOW ))
                        {
                            $now_value = date('Y-m-d');
                            $fields[$attr_name] = $now_value;
                        }
                        break;
                    case self::DATETIME:
                        if (isset($attr_define[self::DFT]) && $attr_define[self::DFT] === self::NOW && ( $attr_value === null || $attr_value === self::NOW ))
                        {
                            $now_value = date('Y-m-d H:i:s');
                            $fields[$attr_name] = $now_value;
                        }
                        break;
                    case self::TIMESTAMP:
                        //如果类型是时间戳，而且是全表的时间戳字段，插入之前默认为当前系统时间戳(created modified)
                        if (( isset($attr_define[self::TIMESTAMP]) && $attr_define[self::TIMESTAMP] === true ) || ( isset($attr_define[self::DFT]) && $attr_define[self::DFT] === self::NOW && ( $attr_value === null || $attr_value === self::NOW ) ))
                        {
                            $now_value = time();
                            $fields[$attr_name] = $now_value;
                        }
                        break;
                    case self::STRING:
                    case self::INTEGER:
                    case self::FLOAT:
                        break;
                    case self::OBJECT:
                    default:
                        break;
                }
            }
            $result = $this->db()
              ->insert($this::$_TABLE_NAME)
              ->cols($fields)
              ->query();
            return $result;
        }
        catch (\Exception $ex)
        {
            throw $ex;
        }
    }

    /**
     * 修改信息
     * @param $params 参数数组
     * @return string
     */
    public function update($params)
    {
        $attr_defines = $this::$_ATTRIBUTES;
        try
        {
            $fields = $this->bindRequest($params);
            //先检查合法性
            $this->validator($fields, $attr_defines, false);

            $where = array();
            $bind_values = array();
            $primary_key_value = null;
            //校验主键合法性
            foreach ($attr_defines as $attr_name => $attr_define)
            {
                $attr_value = isset($fields[$attr_define[self::COLUMN]]) ? $fields[$attr_define[self::COLUMN]] : null;
                //以自增主键为约束更新数据
                if (isset($attr_define[self::AI]) && $attr_define[self::AI] === true)
                {
                    //Update 必须有主键
                    if ($attr_value === null)
                    {
                        throw new ValidationException("Primary Key column [$attr_name] can't be null");
                    }

                    //占位符
                    $where = ["$attr_name = :$attr_name"];
                    $bind_values = [$attr_name => $attr_value];
                    $primary_key_value = $attr_value;
                    break;
                }
            }
            if (empty($where))
            {
                throw new ValidationException("Primary Key attribute not founded");
            }

            //数据是否存在
            $old_res = $this->get($primary_key_value);
            if (empty($old_res))
            {
                throw new ValidationException("The Data [$primary_key_value] is not exsit");
            }

            $update_flag = false;
            $update_column_value = array();
            //数据更新 校对数据是否变动
            foreach ($attr_defines as $attr_name => $attr_define)
            {
                $attr_value = isset($fields[$attr_define[self::COLUMN]]) ? $fields[$attr_define[self::COLUMN]] : null;
                if ((array_key_exists($attr_name, $fields)) && ($old_res[$attr_name] != $attr_value))
                {
                    $update_flag = true;
                    $update_column_value[$attr_name] = $attr_value;
                    $old_res[$attr_name] = $attr_value;
                }

                switch ($attr_define[self::TYPE])
                {
                    case self::ENUM:
                        break;
                    case self::DATE:
                        if (isset($attr_define[self::DFT]) && $attr_define[self::DFT] === self::NOW && ( $attr_value === null || $attr_value === self::NOW ))
                        {
                            $now_value = date('Y-m-d');
                            $update_column_value[$attr_name] = $now_value;
                            $old_res[$attr_name] = $attr_value;
                        }
                        break;
                    case self::DATETIME:
                        if (isset($attr_define[self::DFT]) && $attr_define[self::DFT] === self::NOW && ( $attr_value === null || $attr_value === self::NOW ))
                        {
                            $now_value = date('Y-m-d H:i:s');
                            $update_column_value[$attr_name] = $now_value;
                            $old_res[$attr_name] = $attr_value;
                        }
                        break;
                    case self::TIMESTAMP:
                        //如果类型是时间戳，而且是全表的时间戳字段，插入之前默认为当前系统时间戳(modified 修改)
                        if (( isset($attr_define[self::TIMESTAMP]) && $attr_define[self::TIMESTAMP] === true))
                        {
                            $now_value = time();
                            $update_column_value[$attr_name] = $now_value;
                            $old_res[$attr_name] = $attr_value;
                        }
                        break;
                    case self::STRING:
                    case self::INTEGER:
                    case self::FLOAT:
                        break;
                    case self::OBJECT:
                        break;
                    default:
                        break;
                }
            }



            //没有字段更改过
            if ($update_flag === false)
            {
                return [];
            }
            $result = $this->db()
              ->update($this::$_TABLE_NAME)
              ->cols($update_column_value)
              ->where($where)
              ->bindValues($bind_values)
              ->query();

            if (empty($result))
            {
                return [];   //没有修改数据
            }
            return $old_res;
        }
        catch (\Exception $ex)
        {
            throw $ex;
        }
    }

    /**
     * 修改删除状态
     * @param $params 参数数组
     * @return string
     */
    public function updateDeleted($params)
    {
        $attr_defines = $this::$_ATTRIBUTES;
        try
        {
            $where = array();
            $bind_values = array();
            $primary_key_value = null;
            foreach ($attr_defines as $attr_name => $attr_define)
            {
                $attr_value = isset($params[$attr_define[self::COLUMN]]) ? $params[$attr_define[self::COLUMN]] : null;
                //以自增主键为约束更新数据
                if (isset($attr_define[self::AI]) && $attr_define[self::AI] === true)
                {
                    //Update 必须有主键
                    if ($attr_value === null)
                    {
                        throw new ValidationException("Primary Key column [$attr_name] can't be null");
                    }
                    //占位符
                    $where = ["$attr_name = :$attr_name"];
                    $bind_values = [$attr_name => $attr_value];
                    $primary_key_value = $attr_value;
                    break;
                }
            }
            $deleted = isset($params[self::DELETED]) ? $params[self::DELETED] : null;
            $enum_array = $attr_defines[self::DELETED][self::OPTIONS];
            //检查枚举属性的值，是否在enum_array里面
            if (!isset($enum_array[$deleted]))
            {
                throw new ValidationException("ENUM column [" . self::DELETED . "] value {$deleted} not in enum_array");
            }

            if (empty($where))
            {
                throw new ValidationException("Primary Key attribute not founded");
            }

            //数据是否存在
            $old_res = $this->get($primary_key_value);
            if (empty($old_res))
            {
                throw new ValidationException("The Data [$primary_key_value] is not exsit");
            }



            //状态与原记录对比无变动
            if ($old_res[self::DELETED] == $deleted)
            {
                return [];
            }
            else
            {
                $old_res[self::DELETED] = $deleted;
                $old_res['modified'] = time();
            }

            $result = $this->db()
              ->update($this::$_TABLE_NAME)
              ->cols([self::DELETED => $deleted, 'modified' => time()])
              ->where($where)
              ->bindValues($bind_values)
              ->query();

            if (empty($result))
            {
                return [];   //没有修改数据
            }
            return $old_res;
        }
        catch (\Exception $ex)
        {
            throw $ex;
        }
    }

    /**
     * 单条数据获取
     * @param a $params
     * @return string
     */
    public function get($id)
    {
        $attr_defines = $this::$_ATTRIBUTES;
        try
        {
            $primary_key_name = null;
            foreach ($attr_defines as $attr_name => $attr_define)
            {
                //自增主键
                if (isset($attr_define[self::AI]) && $attr_define[self::AI] === true)
                {
                    $primary_key_name = $attr_name;
                    continue;
                }
            }
            //通过主键查找
            if ($primary_key_name === null)
            {
                throw new ValidationException("Primary Key column is not exsit");
            }
            $where = ["$primary_key_name = :$primary_key_name"];
            $result = $this->db()
              ->select('*')
              ->from($this::$_TABLE_NAME)
              ->where($where)
              ->bindValues(array($primary_key_name => $id))
              ->row();
            return $result;
        }
        catch (\Exception $ex)
        {
            throw $ex;
        }
    }
}