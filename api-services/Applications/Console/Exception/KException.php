<?php

namespace Webadmin\Exception;

/**
 * 异常基础类
 * @author zhijiazou
 */
class KException extends \Exception
{
//    code=0=成功;code=1=系统错误;code=2=配置错误;code=3=登陆错误;code=4=参数错误
    public static $_MESSAGE_DEFINE = [
      30101 => '未登陆',
      40101 => 'action 不存在',
      40201 => '查询信息参数错误',
      40202 => '修改状态值错误',
      40203 => '活动开始时间需小于活动结束时间',
      40204 => '奖项设置不能为空',
      40205 => '奖项设置不可小于三项',
      40206 => '奖项设置不可大于五项',
      40207 => '奖项设置中奖概率之和不可大于百分百',
      40208 => '二维码编号不能为空',
      40209 => '活动编号不能为空',
      40210 => '物流编号不能为空',
      40211 => '中奖记录编号不能为空',
      40212 => '页面编号不能为空',
      40213 => '页面入口不能超过三个',
      40214 => '管理员编号不能为空',
      40215 => '商家信息编号不能为空',
      40216 => '商家信息不存在',
      40217 => '获取微客多token失败',
      40218 => '状态不能为空',
      40219 => '请先关闭其他活动',
      40220 => '活动信息不存在',
      40221 => '管理员编号不能为空',
      40222 => '管理员信息不存在',
      40223 => '账号或密码不能为空',
      40224 => '验证码不能为空',
      40225 => '验证码错误',
      40226 => '账号或密码错误',
      40227 => '文件系统异常',
      40228 => '页面类型不能为空',
      40229 => '编辑活动先关闭该活动',
      40230 => '发货信息编号不能为空',
      40231 => '原密码错误',
      40232=>'请先关闭其他活动',
      40233=>'参数错误',
    ];

    public function __construct($message = "", $code = 1, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if (isset(self::$_MESSAGE_DEFINE[$message]))
        {
            $this->message = self::$_MESSAGE_DEFINE[$message];
        }
    }
}
?>