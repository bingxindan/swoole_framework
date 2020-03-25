<?php

namespace BxdFramework\Controllers;

use BxdFramework\Base\Code;
use BxdFramework\Base\Request;
use BxdFramework\Base\Response;
use BxdFramework\Basic\BxdLog;
use BxdFramework\Basic\SpeedLog;
use BxdFramework\Helpers\BaseHelper;

/**
 * 服务
 * @Date: 18/7/6
 * @Author: zhangming <zhangming@bingxindan.com>
 */
class DemoController
{
    /**
     * 获取
     * @author zm
     */
    public static function getDemo ()
    {
        try {
            SpeedLog::addLog('getSpu', SpeedLog::STEP_ACT_BEG);

            $time        = Request::get('time');

            // 时间
            $curTime = empty($time) ? time() : $time;

            $data = PriceHelper::getSpu($goodsIds, $curTime, $status, $isShowStock, $isMemShow);

            BxdLog::info("getDemo [curTime:$curTime][data:" . json_encode($data) . "]");

            SpeedLog::addLog('getSpu', SpeedLog::STEP_ACT_END);

            Response::succ($data);
        } catch (\Exception $e) {
            BxdLog::info('getDemo Exception [' . $e->getCode() . '][' . $e->getMessage() . ']');
            Response::error(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
