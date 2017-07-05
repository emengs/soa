<?php

namespace Weixin\Models;
use System\Core\Connection;
use System\Libraries\MinzhiRedis;
/**
 *  基础控制器
 * @author guibinYu
 */
class BaseModel
{
    private  $db_config;
    private  $redis_config;
    public static $dbobj = '';
    /** @var redis 前缀名称 */
    protected $Redis_prefix_name='shake_';
    protected $RedisPrefixSetting='shake_setting';
    protected $RdisQueuePrefix = "shake_queue";
    protected $RdisUserTokenPrefix='user_token';
	
    /** @var string  */
    public function __construct()
    {
        //       $this->db_config = weixin_load_config('Config_'.ENV,'database');
//      $this->redis_config = weixin_load_config('Config_'.ENV,'redis');
        $this->db_config = weixin_load_config('Config_'.ENV,'database');
        $this->redis_config = weixin_load_config('Config_'.ENV,'redis');
    }
    /**
     * 载入默认的db
     * @param type $db
     * @return type
     */
    public  function db($db = 'default')
    {
        if(empty(self::$dbobj))
        {
            self::$dbobj = new \System\Core\Connection($this->db_config[$db]);
        }
        return self::$dbobj ;
    }
    /**
     * 载入默认的db
     * @param type $db
     * @return type
     */
    public function RedisCache($expire)
    {
        $config=$this->redis_config;
        $config['prefix']=$this->redis_config['prefix'].$expire;
        return new \System\Libraries\ShakeRedis($config);
    }
    
    
}