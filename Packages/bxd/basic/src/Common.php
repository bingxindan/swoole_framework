<?php
namespace BxdFramework\Basic;

use BxdFramework\Base\Request;
use BxdFramework\RestCall\RestAPI;

class Common
{
    // 日志错误级别
    const LEV_ERR = 'error';
    const LEV_OK  = 'ok';
    
    /**
     * demo
     * @return  string
     */
    public static function demo()
    {
    }

    /**
     * 直接输出日志
     * @param $code
     * @param $msg
     * @author zm
     */
    public static function writeLog($code, $msg)
    {
        if (empty($msg)) {
            return;
        }

        $time = date('Y-m-d H:i:s');
        $msg = "[$time][$code]$msg\n";

        echo $msg;
    }
}
