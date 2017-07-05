<?php
namespace System\Core;

use System\Core\RedisCache;
/**
 *  基础控制器
 * @author guibinYu
 */
class StandardRedis extends RedisCache{
    private $expire;
    public function __construct($config){
        $this->expire = $config['expire'];

        parent::__construct($config['host'], $config['port'], $config['prefix'].':');

        $this->select($config['db']);
        $this->check();
	}
    
	private function ping() {
		try {
			$pong = $this->redis->ping();
		} catch ( Exception $e ) {
			return false;
		}
		return true;
	}
    private function check()
    {

//        if(trim($this->db) != '' )
//        {
//            exit('db error');
//        }
        if(empty($this->expire))
        {
            exit('expire error ');
        }
    }
    /**
     * 为 key 设置生存时间,接受的时间参数是 UNIX 时间戳(unix timestamp)
     * @param  [type] $key  [description]
     * @param  [type] $time [description]
     * @return [type]       [description]
     */
    public function expireAt($key,$time){
    	return $this->redis->expireAt($key,$time);
    }
    /**
	 * 队列左边出队
	 * @param  [type] $course [description]
	 * @return [type]         [description]
	 */
	public function lPop($course){
       
		return $this->redis->lPop($course);
	}
	/**
	 * 队列右边入队
	 * @param  [type] $course [description]
	 * @param  [type] $value  [description]
	 * @return [type]         [description]
	 */
	public function rPush($course,$value){
       
		return $this->redis->rPush($course,$value );
	}
	/**
	 * 返回队列长度
	 * @param  [type] $course [description]
	 * @return [type]         [description]
	 */
	public function lLen($course){
       
		return (int)$this->redis->lLen($course);
	}
	/**
	 * 获取hash表元素个数
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function hLen($key){
		return $this->redis->hLen($key);
	}
	/**
	 * 返回哈希表 key 中给定域 field 的值
	 * @param  [type] $key   [description]
	 * @param  [type] $field [description]
	 * @return [type]        [description]
	 */
	public function hGet($key,$field){
     
		return $this->redis->hGet($key,$field);
	}
         /**
	 * 为 key 的值加上增量 increment
	 * @param unknown $key
	 */
	public function hGetAll( $key){
     
		return $this->redis->hGetAll( $key);
	}
	/**
	 * 将哈希表 key 中的域 field 的值设为 value
	 * @param  [type] $hkey  [description]
	 * @param  [type] $key   [description]
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function hSet($key,$field,$value) {
      
        $this->expire($key, $this->expire);
        
		return $this->redis->hSet($key, $field, $value );
	}
	/**
	 * 删除哈希表 key 中的一个指定域 field
	 * @param  [type] $key   [description]
	 * @param  [type] $field [description]
	 * @return [type]        [description]
	 */
	public function hDel($key,$field){
		return $this->redis->hDel($key,$field);
	}
	
	/**
	 * 判断hash里面是否存在指定域 $field
	 * @param string $key
	 * @param string $field
	 * @return boolean
	 */
	public function hExists($key , $field){
		return $this->redis->hExists($key , $field);
	}
	
	/**
	 * 一次获取多个hash域的值
	 * @param string $key
	 * @param array $fields
	 */
	public function hMget($key , $fields){
       
		return $this->redis->hMget($key , $fields);
	}
	/**
	 * 一次设置多个hash域
	 * @param string $key
	 * @param array $fielddata
	 */
	public function hMset($key , $fielddata){
     
        $this->expire($key, $this->expire);
		return $this->redis->hMset($key , $fielddata);
	}
	/**
	 * 为哈希表 key 中的域 field 的值加上增量 increment
	 * @param unknown $key
	 * @param unknown $field
	 * @param number $increment
	 */
	public function hIncrBy( $key, $field, $increment = 1 ){
     
		return $this->redis->hIncrBy( $key, $field, (int)$increment );
	}
	/**
	 * 为 key 的值加上增量 increment
	 * @param unknown $key
	 * @param unknown $field
	 * @param number $increment
	 */
	public function incrBy( $key, $increment = 1 ){
      
		return $this->redis->incrBy( $key, (int)$increment );
	}

    /**
     * 为 key 的值减少增量 increment
     * @param unknown $key
     * @param unknown $field
     * @param number $increment
     */
    public function decrBy( $key, $increment = 1 ){

        return $this->redis->decrBy( $key, (int)$increment );
    }

	/**
	 * 删除单个字符串类型的 key ，时间复杂度为O(1)。
	 * 删除单个列表、集合、有序集合或哈希表类型的 key ，时间复杂度为O(M)， M 为以上数据结构内的元素数量。
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function del($key){
		return $this->redis->Del($key);
	}
    /**
	 * 删除单个字符串类型的 key ，时间复杂度为O(1)。
	 * 删除单个列表、集合、有序集合或哈希表类型的 key ，时间复杂度为O(M)， M 为以上数据结构内的元素数量。
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function delStr($key){
        
       
		return $this->redis->Del($key);
	}
	/**
	 * 返回 key 所关联的字符串值
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function get($key){
      
		return $this->redis->get($key);
	}
	/**
	 * 将字符串值 value 关联到 key 。expire 为key设置过期时间单位s
	 * @param [type]  $key    [description]
	 * @param [type]  $value  [description]
	 * @param boolean $expire [description]
	 */
	public function setex( $key, $expire='',$value ) {
      
        $expire = $expire ? $expire : $this->expire;
        return $this->redis->setex($key, $expire,$value);
	}
	/**
	 * 检查给定 key 是否存在。
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function exists($key){
		return $this->redis->exists($key);
	}

	/**
	 * 在有序set里面添加一个元素
	 * @param unknown $key
	 * @param unknown $score
	 * @param unknown $member
	 */
	public function zAdd($key, $score, $member) {
		return $this->redis->zAdd ($key, $score, $member );
	}

	/**
	 * 返回有序结合列表
	 */
	public function zRange($key, $start, $stop, $withscore = true) {
		return $this->redis->zRange ($key, $start, $stop, $withscore );
	}
	/**
	 * 删除一个元素
	 * @param unknown $key
	 * @param unknown $member
	 */
	public function zRem($key, $member) {
		return $this->redis->zRem ($key, $member );
	}
	/**
	 * 将信息 message 发送到指定的频道 channel
	 * @param  array $channel [description]
	 * @param  [type] $message [description]
	 * @return 接收到信息 message 的订阅者数量
	 */
	public function publish($channel,$message){
		return $this->redis->publish($channel,$message);
	}

    /**
     * 减少指定的整数
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function decr( $key, $increment = 1 ){

      
        return $this->redis->decr( $key, (int)$increment );
    }

    /**
     * 向集合添加一个或多个成员
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function sadd($key,$value){
        return $this->redis->sadd( $key, $value);
    }

    /**
     * 判断 member 元素是否是集合 key 的成员
     * @param $name
     * @param $arguments
     * @return mixed
     */

    public function sismember($key,$value){
        return $this->redis->sIsMember( $key, $value);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function smembers($key){

        return $this->redis->sMembers($key);
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function zScore($key,$value){

        return $this->redis->zScore($key,$value);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function zIncrBy($key,$num,$value){

        return $this->redis->zIncrBy($key,$num,$value);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function sRem($key,$value){
        return $this->redis->sRem($key,$value);
    }

    public function sDiff($key1,$key2){
       return $this->redis->sDiff($key1,$key2);
    }


    public function zRank($key,$value){
        return $this->redis->zRank($key,$value);
    }


    public function multi(){
        return $this->redis->multi();
    }


    public function exec(){
        return $this->redis->exec();
    }

    public function lRange($key,$start,$end){
        return $this->redis->lRange($key,$start,$end);
    }

    public function __call($name, $arguments) {
        return $this->redis->$name($arguments[0]);
    }
}