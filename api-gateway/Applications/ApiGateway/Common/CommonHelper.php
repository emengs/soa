<?php

/**
 * 获取代码调用信息
 */
function getDebugInfo()
{
    $debugTrace = array();
    $debug = debug_backtrace();
    $debug = $debug[1];
    // print_r($debug);
    $debugTrace['line'] = $debug['line'];
    $debugTrace['file'] = $debug['file'];
    $debugTrace['function'] = $debug['function'];
    return $debugTrace;
}
