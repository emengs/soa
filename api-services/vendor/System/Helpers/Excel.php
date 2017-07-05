<?php

namespace System\Helpers;
class Excel
{
    //转换为日期
    const FORMAT_DATE = '_d';
    //转换为 数字或字符串格式化
    const FORMAT_STATUS = '_s';
    //没有指定转换的值的时候，返回默认指定值
    const FORMAT_STATUS_DEFAULT = '_df';
    //默认不转换
    const FORMAT_DEFAULT = '_f';
    //json格式
    const FORMAT_JSON = '_js';
    //转换金额格式 （除以100）
    const FORMAT_AMOUNT = '_m';
    //转换为数组长度
    const FORMAT_LENGTH = '_l';
    //转换为物流公司
    const FORMAT_LOGISTICS = '_ls';
    //转换为数字
    const FORMAT_NUMBER = '_nm';
    //多条数据的key
    const KEY_HASMANY = '_hasMany';
    //多个key值混合转换
    const MIX_STATUS = '_mixStatus';
    //转两个值相除
    const FORMAT_RATE = '_div';

   
    /**
     * 得到需要导出的字段
     * @return string
     */
    public function format($data, $needField)
    {
        if (!is_array($data) || !count(array($data)))
        {
            $this->export();
        }
        $exportData = [];
        if (is_array($data) && count($data))
        {
            foreach ($data as $key => $val)
            {
                $needData = [];
                foreach ($needField as $needKey => $needVal)
                {
                    $needData[] = $this->formatField($val, $needKey, $needVal);
                }
                $exportData[] = $needData;
            }
        }
        return $exportData;
    }

    /**
     * 格式化字段
     * @return string
     */
    public function formatField($data, $fieldKey, $fieldValue)
    {
        foreach ($fieldValue as $key => $val)
        {
            switch ($key)
            {
                case self::FORMAT_DEFAULT :
                    return self::filterSpecialChars($this->getValue($fieldKey, $data)) . "\t";
                case self::FORMAT_DATE :
                    return date($val, $this->getValue($fieldKey, $data));
                case self::FORMAT_STATUS :
                    $value = $this->getValue($fieldKey, $data);
                    //返回转换的值
                    if (isset($val[$value]))
                    {
                        return $val[$value] . "\t";
                    }
                    //没有指定转换的值的时候，返回默认指定值
                    if (isset($val[self::FORMAT_STATUS_DEFAULT]))
                    {
                        return $val[self::FORMAT_STATUS_DEFAULT] . "\t";
                    }
                    return '';
                case self::FORMAT_AMOUNT :
                    return BaseModel::amountToYuan($this->getValue($fieldKey, $data)) . "\t";
                case self::FORMAT_JSON :
                    return $this->getValue($fieldKey, $data, true) . "\t";
                case self::FORMAT_LENGTH :
                    return count($this->getValue($fieldKey, $data)) . "\t";
                case self::FORMAT_LOGISTICS :
                    return Logistics::get($this->getValue($fieldKey, $data))['text'] . "\t";
                case self::FORMAT_NUMBER :
                    return $this->getValue($fieldKey, $data);
                case self::FORMAT_RATE :
                    $explode = explode('-', $fieldKey);
                    $value0 = $this->getValue($explode[0], $data);
                    $value1 = $this->getValue($explode[1], $data);
                    if ($value1 == 0)
                    {
                        return 0;
                    }
                    return bcmul(bcdiv($value0, $value1, 2), 100, 2) . '%';
                case self::MIX_STATUS:
                    $explode = explode('-', $fieldKey);
                    $value = '';
                    foreach ($explode as $expldeVal)
                    {
                        $value.=$this->getValue($expldeVal, $data);
                    }
                    //返回转换的值
                    if (isset($val[$value]))
                    {
                        return $val[$value] . "\t";
                    }
                    //没有指定转换的值的时候，返回默认指定值
                    if (isset($val[self::FORMAT_STATUS_DEFAULT]))
                    {
                        return $val[self::FORMAT_STATUS_DEFAULT] . "\t";
                    }
                    return $this->getValue($fieldKey, $data);
                default :
                    return $this->getValue($fieldKey, $data) . "\t";
            }
        }
    }

    /**
     * 格式化字段
     * @return string
     */
    public function formatFieldCopy($data, $fieldKey, $fieldValue)
    {
        foreach ($fieldValue as $key => $val)
        {
            switch ($key)
            {
                case self::FORMAT_DEFAULT :
                    return self::filterSpecialChars($this->getValue($fieldKey, $data)) . "\t";
                case self::FORMAT_DATE :
                    return date($val, $this->getValue($fieldKey, $data));
                case self::FORMAT_STATUS :
                    $value = $this->getValue($fieldKey, $data);
                    //返回转换的值
                    if (isset($val[$value]))
                    {
                        return $val[$value] . "\t";
                    }
                    //没有指定转换的值的时候，返回默认指定值
                    if (isset($val[self::FORMAT_STATUS_DEFAULT]))
                    {
                        return $val[self::FORMAT_STATUS_DEFAULT] . "\t";
                    }
                    return '';
                case self::FORMAT_AMOUNT :
                    return BaseModel::amountToYuan($this->getValue($fieldKey, $data)) . "\t";
                case self::FORMAT_JSON :
                    return $this->getValue($fieldKey, $data, true) . "\t";
                case self::FORMAT_LENGTH :
                    return count($this->getValue($fieldKey, $data)) . "\t";
                case self::FORMAT_LOGISTICS :
                    return Logistics::get($this->getValue($fieldKey, $data))['text'] . "\t";
                case self::FORMAT_NUMBER :
                    return $this->getValue($fieldKey, $data);
                default :
                    return $this->getValue($fieldKey, $data) . "\t";
            }
        }
    }

    /**
     * 根据特殊的数组的键，得到对应数组值
     */
    public function getValue($key, $value)
    {
        $explode = explode('-', $key);
        if (count($explode) > 1)
        {
            foreach ($explode as $explodeKey => $expldeVal)
            {
                if (isset($value[$expldeVal]))
                {
                    unset($explode[$explodeKey]);
                    $value = $value[$expldeVal];
                }
                else
                {
                    if ($expldeVal != self::KEY_HASMANY)
                    {
                        return '';
                    }
                    //去掉数组 haymany 的key
                    foreach ($explode as $key => $val)
                    {
                        if ($val == self::KEY_HASMANY)
                        {
                            unset($explode[$key]);
                        }
                    }
                    return $this->hasManyData($explode, $value);
                }
            }
            if (is_array($value))
            {
                $column = '';
                $n = 1;
                $len = count($value);
                foreach ($value as $key => $val)
                {
                    $column .= $key . '：' . $val . ($n < $len ? '，' : '');
                }
                $value = $column;
            }
            return $value;
        }
        if (isset($value[$key]))
        {
            if (is_array($value[$key]))
            {
                $column = '';
                $n = 1;
                $len = count($value[$key]);
                foreach ($value[$key] as $key => $val)
                {
                    $column .= $key . '：' . $val . ($n < $len ? '，' : '');
                }
                $value[$key] = $column;
            }
            return $value[$key];
        }
        else
        {
            return "";
        }
    }

    /**
     * 返回多条记录
     */
    private function hasManyData($explode, $data)
    {
        $explodeKey = implode('-', $explode);
        $_data = '';
        $len = count($data);
        $i = 1;
        foreach ($data as $val)
        {
            $_data .= $this->getValue($explodeKey, $val) . ($i < $len ? " ；\r" : "");
            $i++;
        }
        return $_data;
    }

    static public function download($data, $fileName = 'data.xsl')
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getActiveSheet()->fromArray($data, null, 'A1');
        $objPHPExcel->getActiveSheet()->setTitle('Sheet1');
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($fileName);   //图片保存
    }

    public static function toArray($file)
    {
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        return $objPHPExcel->getSheet(0)->toArray(null, true, true, true);
    }

    static public function getExcelData($excelFile = null)
    {
        $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objPHPExcel = $objReader->load($excelFile);
        $sheet = $objPHPExcel->getSheet(0); //获取第一个工作表
        $highestRow = $sheet->getHighestRow(); //取得总行数
        $highestColumn = $sheet->getHighestColumn(); //取得总列数

        $data = [];
        for ($j = 1; $j <= $highestRow; $j++)
        { //从第1行开始
            $arrResult = '';
            for ($k = 'A'; $k <= $highestColumn; $k++)
            {
                //读取单元格
                $arrResult .= $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue() . ',';
            }
            $arrResult = rtrim($arrResult, ",");
            $data[] = explode(",", $arrResult);
        }
        return $data;
    }

    /**
     * 过滤特殊字符
     * @param $text
     * @return mixed
     */
    public static function filterSpecialChars($text)
    {
        //过滤emoji表情
        $a = json_encode($text);
        $b = preg_replace("/\\\ud([8-9a-f][0-9a-z]{2})/i", "", $a);
        $text = json_decode($b);

        //过滤特殊字符
        $pattern = "/[\x{3400}-\x{4DB5}\x{4E00}-\x{9FA5}\x{9FA6}-\x{9FBB}\x{F900}-\x{FA2D}\x{FA30}-\x{FA6A}\x{FA70}-\x{FAD9}\x{FF00}-\x{FFEF}\x{2E80}-\x{2EFF}\x{3000}-\x{303F}\x{31C0}-\x{31EF}\x{2F00}-\x{2FDF}\x{2FF0}-\x{2FFF}\x{3100}-\x{312F}\x{31A0}-\x{31BF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{31F0}-\x{31FF}\x{AC00}-\x{D7AF}\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{4DC0}-\x{4DFF}\x{A000}-\x{A48F}\x{A490}-\x{A4CF}\x{2800}-\x{28FF}\x{3200}-\x{32FF}\x{3300}-\x{33FF}\x{2700}-\x{27BF}\x{2600}-\x{26FF}\x{FE10}-\x{FE1F}\x{FE30}-\x{FE4F}0-9a-zA-Z—\x{21}-\x{7e}\x{00}-\x{ff}]/ui";
        $filterStr = preg_replace($pattern, '', $text);
        $filterPattern = self::addslashe("/" . $filterStr . "/ui");
        return preg_replace($filterPattern, '', $text);
    }

    /**
     * 字符串转义
     */
    public static function addslashe($val, $force = false)
    {

        if (!get_magic_quotes_gpc() || $force)
        {
            if (is_array($val))
            {
                foreach ($val as $key => $value)
                {
                    if (is_array($value))
                    {
                        $val[$key] = self::addslash($value, $force);
                    }
                    else
                    {
                        $val[$key] = addslashes($value);
                    }
                }
            }
            else
            {
                $val = addslashes($val);
            }
        }
        return $val;
    }
}