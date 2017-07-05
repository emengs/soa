<?php

namespace Webadmin\Models;


use Webadmin\exception\ValidationException;
use System\Helpers\Excel;

/**
 *  excel model
 * @author zouzhijia
 */
class ExcelModel extends BaseModel
{
    const URL = 'http://dfs.dkh.snsshop.net/excel/create';
    const PROJECT_NAME = 'shake_winning';

    /**
     * 导出活动
     * @param $data 参数数组
     * @int $agent_id 商户id
     * @return
     */
    public function exportShakeWinning($data, $agent_id)
    {
        //导出文件名
       // $ShakeWinning = new ShakeWinningModel();
      //  $agent = $model_agent->get($agent_id);
        $fileName = $agent_id . '_ShakeWinning('.date('Y-m-d',time()).')';
        //订单导出字段规则
        $heads = [['姓名/昵称', '中奖奖项', '奖项类型', '中奖详情', '中奖时间']];
        $needField = [
            'user_name' => [Excel::FORMAT_DEFAULT => ''],
            'level' => [Excel::FORMAT_STATUS => ['未中奖','一等奖','二等奖','三等奖','四等奖','五等奖','六等奖','七等奖','八等奖','九等奖']],
            'prize_type' => [Excel::FORMAT_STATUS => ['1'=>'卡劵','2'=>'现金','3'=>'未中奖']],
            'prize_name' => [Excel::FORMAT_NUMBER => ''],
            'create_time' => [Excel::FORMAT_DATE => 'Y-m-d H:i:s'],
        ];
        //进行数据导出
        $util_Excel = new Excel();
        $body = $util_Excel->format($data, $needField);
        foreach ($body as $key => $value)
        {
//            $status = '';
//            if ($value[12] == 2)
//            {
//                $status = '已关闭';
//            }
//            elseif ($value[3] > date('Y-m-d H:i:s'))
//            {
//                $status = '未开始';
//            }
//            elseif ($value[4] < date('Y-m-d H:i:s'))
//            {
//                $status = '已结束';
//            }
//            elseif ($value[4] >= date('Y-m-d H:i:s') && $value[3] <= date('Y-m-d H:i:s') && $value[12] == 1)
//            {
//                $status = '进行中';
//            }
//            $t = $value[4];
//            $value[4] = $status;
//            $value[3].=' ' . $t;
//
//            if ($value[6] != 0)
//            {
//                $s = $value[6];
//                $value[7] = bcmul(bcdiv($value[5], $s, 4), 100, 2) . '%';
//            }
//            else
//            {
//                $value[7] = '0.00%';
//            }
//            $deleted_enum = ActivityModel::DELETED_ENUM_ARRAY;
//            $value[12] = $deleted_enum[$value[12]];
            $body[$key] = $value;
        }

        $download_url = self::request(self::URL, ['data' => json_encode($body), 'project_name' => self::PROJECT_NAME
            , 'heads' => json_encode($heads), 'file_name' => $fileName]);
        return $download_url;
    }



    public static function request($url, $param)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($param))
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }

        try
        {
            $result = curl_exec($ch);
            curl_close($ch);
            if ($result === false)
            {
                throw new ValidationException(40227);
            }
            else
            {
                $re = json_decode($result, true);
                if ($re['code'] == 0)
                {
                    return $re['data'];
                }
                else
                {
                    throw new ValidationException($re['msg']);
                }
            }
        }
        catch (\Exception $e)
        {
            $response ['msg'] = $e->getMessage();
        }
        return $response;
    }
}