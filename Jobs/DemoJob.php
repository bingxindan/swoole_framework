<?php
namespace BxdFramework\Jobs;

use BxdFramework\Basic\Common;

class DemoJob
{
    public static function handle ()
    {
        try {
            $handleNum = 0;

        } catch (\Exception $e) {
            // 写日志
            Common::writeLog($e->getCode(), $e->getMessage());
        }
    }
}
