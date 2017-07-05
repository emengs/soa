<?php

namespace Common;


/**
 * 文本解析器
 *
 * @author zhijiazou
 * @since 2017/03/14
 */
class TextParser
{

    /**
     *  数组转xml
     * @param $arrayData 数组
     * @return XmlData
     */
    public static function arrayToXml($arrayData, $node = FALSE)
    {
        try
        {
            if (!is_array($arrayData))
            {
                throw new TextParserException('数据类型必须是数组');
            }
            $XmlData = "<?xml version='1.0' encoding='utf-8'?>\n";
            if ($node !== FALSE)
            {
                $XmlData .= "<$node>";
            }
            foreach ($arrayData as $key => $val)
            {
                if (is_array($val))
                {
                    $XmlData.="<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
                }
                else
                {
                    $XmlData.="<" . $key . ">" . $val . "</" . $key . ">";
                }
            }
            if ($node !== FALSE)
            {
                $XmlData .= "</$node>";
            }
            return $XmlData;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * xml转数组
     * @param $xmlData
     * @return arrayData
     */
    public static function xmlToArray($xmlData)
    {
        if ($xmlData)
        {
            try
            {
                //禁止引用外部xml实体 
                libxml_disable_entity_loader(true);
                $xmlstring = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
                $arrayData = json_decode(json_encode($xmlstring), true);
                return $arrayData;
            }
            catch (Exception $e)
            {
                throw $e;
            }
        }
    }

    /**
     * xml转Json
     * @param $xmlData
     * @return jsonData
     */
    public static function xmlToJson($xmlData)
    {
        try
        {
            $xml_array = simplexml_load_string($xmlData);
            $jsonData = json_encode($xml_array);
            return $jsonData;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}
?>