<?php

namespace Weixin\Libraries;

use System\Libraries\Weikeduo;
/**
 *  微客多
 * 
 * @author guibinYu
 */
class Weikeduo   
{
    private $agentConfig;
    public function __construct($agentConfig)
    {
        $this->agentConfig = $agentConfig;
    }
    
    /**
     * 获取微客多的访问token
     * @param type $agent_id
     * @return type
     */
    public function auth($agent_id)
    {
        $token = $this->RedisCache()->getToken($agent_id);
        if($token)
        {
            return $token;
        }
        $agentConfig = $this->getAgentConfig($agent_id);
        $weikeduo = new Weikeduo($agentConfig);
        $result = $weikeduo->tokens();
        if(isset($result['errcode']) && $result['errcode'] ==0)
        {
            $token = $result['data']['access_token'];
            $this->RedisCache()->setToken($agent_id,$token);
        }
        return $token;
    }
}