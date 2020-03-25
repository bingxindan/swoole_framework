<?php
namespace BxdFramework\Models;

use BxdFramework\DataBase\Model;

/**
 * 数据模型基础类
 * @Author: zhangming <zhangming@bingxindan.com>
 */
class Base extends Model
{
    // 库
    protected static $db = 'blog';
    // 表前缀
    protected static $tablePre = 'd_';
    // 主库key
    protected static $masterKey = 'master';
    // 从库key
    protected static $slaveKey = 'slave';
}
