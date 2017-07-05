<?php

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno))
    {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
    }
    switch ($errno)
    {
        case E_ERROR:
            Log4p::error(array('type' => 'SYSTEM_ERROR', 'respone' => "[$errno] $errstr on line $errline in file $errfile"));
            exit(1);
            break;

        case E_WARNING:
            Log4p::error(array('type' => 'SYSTEM_WARNING', 'respone' => "[$errno] $errstr on line $errline in file $errfile"));
            break;

        case E_NOTICE:
            Log4p::error(array('type' => 'SYSTEM_NOTICE', 'respone' => "[$errno] $errstr on line $errline in file $errfile"));
            break;

        default:
            Log4p::error(array('type' => 'SYSTEM_Unknown', 'respone' => "[$errno] $errstr on line $errline in file $errfile"));
            break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}
set_error_handler("myErrorHandler");
