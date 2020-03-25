<?php
namespace BxdFramework\Models;

/**
 * @Author: zhangming <zhangming@bingxindan.com>
 */
class Demo extends Base
{
    protected static $table = 'demo';

    // 状态
    const DELETE_YES = 1; // 删除
    const DELETE_NO  = 0; // 未删除
    const ON_SALE_YES = 1; // 上架
    const ON_SALE_NO  = 0; // 下架

    /**
     * 获取
     * @param array $fields
     * @param $where
     * @param null $order
     * @param null $limit
     * @param int $fetch
     * @param bool $master
     * @return mixed
     * @author crx
     */
    public static function getGoodsByConds(
        $fields = ['*'],
        $where,
        $order = null,
        $limit = null,
        $fetch = \PDO::FETCH_ASSOC,
        $master = false,
        $isOne = 0
    )
    {
        return $isOne 
            ? self::row($fields, $where, $order, $fetch, $master) 
            : self::rows($fields, $where, $order, $limit, $fetch, $master);
    }
}
