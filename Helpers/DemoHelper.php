<?php
namespace BxdFramework\Helpers;

use BxdFramework\Base\Code;
use BxdFramework\Base\Response;
use BxdFramework\Basic\Common;
use BxdFramework\Basic\BxdLog;

/**
 * @Author: zhangming <zhangming@bingxindan.com>
 */
class PriceHelper extends BaseHelper
{
    /**
     * @return array
     * @author zm
     */
    public static function getDemo()
    {
        try {
            // 输出数据
            return $ret;
        } catch (\Exception $e) {
            BxdLog::error('[' . $e->getCode() . ']' . $e->getMessage());
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }
}
