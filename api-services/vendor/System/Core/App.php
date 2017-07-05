<?php
/**
 *  页面自动中转
 * @author guibinYu
 */
//namespace Vendor\System\Core;


require_once  dirname(dirname(__DIR__)) .'/System/Helpers/FunctionHelper.php';
require_once  dirname(dirname(__DIR__)) .'/Logger/Logger/Log4p.php';


class App {
    public $module;
    public $controller;
	public $action;
    public $params;
    static $platfrom;
	public function __construct() {
		if (function_exists ( 'spl_autoload_register' )) {
			spl_autoload_register ( 'self::autoload' );
		}
	}
	
    /**
     * 主入口
     * @param type $data
     */
	public function run($data) {
        $result = $this->parseUrl($data);
        
        if($result) { //没结果是最好的结果
            return $result;
        }
        $result = $this->execute ();
        
		if($result) {
            return $result;
        }
	}
    /**
     * 解析URL: // 获得要调用的模块，控制器、方法、及参数
     */
    private function parseUrl($data) 
    {
		if(empty($data['class']) || empty($data['method']))
        {
           return array('code'=>1, 'msg'=>'bad request');
        }
        
        $class = explode('_', $data['method']);
        if(count($class)<1)
        {
            return array('code'=>1, 'msg'=>'access error');
        }
        //对外访问的接口限制
//        $method = array('index','view','create','update','delete');
//
//        if(!in_array($class[1], $method))
//        {
//            return array('code'=>1, 'msg'=>'access privilege grant failed');
//        }
        $this->module = ucfirst($data['class']);
        $this->controller = ucfirst($class[0]);
        $this->action =  ucfirst($class[1]);
        $this->params = isset($data['param_array'])?(is_array($data['param_array']) ? $data['param_array'] : json_decode($data['param_array'],true)):[];
	}
    
    /**
     *  判断类对应文件是否载入,并执行调用
     * @return type
     */
    private function execute() {
        self::$platfrom = dirname(dirname(dirname(__DIR__))).'/Applications/'.$this->module;
        
        $class_path = self::$platfrom .'/Controllers/'.$this->controller.'.php' ;

        if(!is_file($class_path))
        {
            Log4p::error(['type' => 'SYSTEM_ERROR', 'request' => $this->params, 'response' => " class $this->controller not found"]);
            return array('code'=>1, 'msg'=>"  class $this->controller not found");
        }
        if(is_file($class_path))
        {
            require_once $class_path;
        }
        try 
        {
            //静态访问
//            $ret = call_user_func_array(array("Controllers\\{$this->module}\\{$this->controller}","{$this->action}"), array('params'=>$this->params));   
            //非静态访问
            $handle = "\\{$this->module}\Controllers\\{$this->controller}";
            $result = call_user_func_array(array(new $handle(array('params'=>$this->params)),$this->action),$this->params);

            // 发送数据给客户端，调用成功，data下标对应的元素即为调用结果
            
            if(isset($result['code']))
            {
                if($result['code'] !=0 )
                {
                    $logData = array('params'=> $this->params ,'response'=>$result);
                    log_result('response', $logData,'error.log');
                }
                return $result;
            }
            else
            {
                return array('code'=>0, 'msg'=>'Success','data'=>$result);
            }
        }
        catch(PDOException $e)
        {
            $logData = array('params'=> $this->params ,'response'=>$e);
            Log4p::error(['type' => 'PDOException', 'request' => $this->params,'response' => array('ex_file' => $e->getFile(), 'ex_line' => $e->getLine(), 'ex_msg' => $e->getMessage())]);

            return array('code'=>1, 'msg'=>'response failure','data'=>$logData);
        }
        catch(Exception $e)
        {
            $logData = array('params'=> $this->params ,'response'=>$e);
            Log4p::error(['type' => 'Exception', 'request' => $this->params, 'response' => $e->getMessage()]);
            return array('code'=>$e->getCode(), 'msg'=>$e->getMessage());
        }

	}
    
	static public function autoload($className) {
		if (class_exists ( $className, false ))
        {
            return true;
        }
        
		$class_array = array ();
        $className = str_replace('\\', '/', $className);
        $class_array [] =  '/'.$className . '.php';
		$class_array [] = '/System/Core/'. $className . '.php';
        $class_array [] = '/System/Libraries/'. $className . '.php';
		$class_array [] = '/controllers/'. $className . '.php';
        $class_array [] = '/Models/'. $className . '.php';
		$class_array [] =  '/Libraries/' . $className . '.php';
        $class_array [] =  '/Libraries/' . $className . '.php';
        $class_array [] =  __DIR__.'/' . $className . '.php';
        
        $class_array [] =  dirname(dirname(__DIR__)).'/' . $className . '.php';
        
		foreach ( $class_array as $file ) 
        {
          
			if (is_file ( self::$platfrom.$file )) 
            {
				include_once  self::$platfrom.$file;
				return TRUE;
			}
            if (is_file (dirname(self::$platfrom).$file )) 
            {
				include_once dirname(self::$platfrom).$file;
				return TRUE;
			}
            if (is_file ($file )) 
            {
				include_once $file;
				return TRUE;
			}
		}
//        echo dirname(dirname(__DIR__)).'/' . $className . '.php';
//		echo $className;
	}
}
?>