<?php
namespace BxdFramework\DataBase;

use BxdFramework\DataBase\Conn;
use BxdFramework\DataBase\Build;
use BxdFramework\DataBase\Query;

class Model
{
    // 库
    protected static $db;
    // 表
    protected static $table;
    // 表前缀
    protected static $tablePre;
    // 主库key
    protected static $masterKey;
    // 从库key
    protected static $slaveKey;

    /**
     * 构造
     */
    public function __construct()
    {
    }

    /**
     * 获取插入id
     * @return string
     */
    public static function insertId()
    {
        $pdo = Conn::get(static::$db, static::$masterKey);
        return $pdo->lastInsertId();
    }

    /**
     * 获取表名
     * @return string
     */
    public static function getTable()
    {
        return static::$tablePre . static::$table;
    }

    /**
     * 插入
     * @param $data <array>
     * array(
     *        field1 => value1,
     *        field2 => value2,
     * )
     * @return $result  <boolean>
     */
    public static function insert($data)
    {
        if (empty($data)) {
            return false;
        }
        $pdo = Conn::get(static::$db, static::$masterKey);
        list($fields, $params, $values) = Build::insertParams($data);
        $fieldStr = implode(', ', $fields);
        $placeholderStr = implode(', ', array_keys($params));
        $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", self::getTable(), $fieldStr, $placeholderStr);
        list($stmt, $result) = Query::execute($pdo, $sql, $params, $values);
        return $result;
    }

    /**
     * 更新
     * @param $cond <array>
     * array(
     *      field1 => value1,
     *      field2 => value2,
     * )
     * @param $data <array>
     * array(
     *      field3 => value3,
     *      field4 => value4,
     * )
     * @return $affectedRows    <int>
     */
    public static function update($cond, $data)
    {
        if (empty($data)) return 0;
        $pdo = Conn::get(static::$db, static::$masterKey);
        list($whereStr, $whereParams, $whereValues) = Build::whereParams($cond);
        list($updateStr, $updateParams, $updateValues) = Build::updateParams($data);
        $sql = sprintf("UPDATE `%s` SET %s WHERE %s", self::getTable(), $updateStr, $whereStr);
        $params = $whereParams + $updateParams;
        $values = $whereValues + $updateValues;
        list($stmt, $result) = Query::execute($pdo, $sql, $params, $values);
        $affectedRows = $stmt->rowCount();
        return $affectedRows;
    }

    /**
     * 更新
     * @param $cond <array>
     * array(
     *      field1 => value1,
     *      field2 => value2,
     * )
     * @return $affectedRows    <int>
     */
    public static function delete($cond)
    {
        if (empty($cond)) return 0;
        $pdo = Conn::get(static::$db, static::$masterKey);
        list($whereStr, $whereParams, $whereValues) = Build::whereParams($cond);
        $sql = sprintf("DELETE FROM `%s` WHERE %s", self::getTable(), $whereStr);
        list($stmt, $result) = Query::execute($pdo, $sql, $whereParams, $whereValues);
        $affectedRows = $stmt->rowCount();
        return $affectedRows;
    }

    /**
     * 查询
     * @param   $fields <array>
     * @param $cond <array>
     * array(
     *      field1 => value1,
     *      field2 => value2,
     * )
     * @param   $order <string>
     * @param   $limit <string>
     * @param   $master <boolean>
     * @return
     */
    private static function select(Array $fields, Array $cond, $order = null, $limit = null, $master = false)
    {
        $dbKey = $master ? static::$masterKey : static::$slaveKey;
        $pdo = Conn::get(static::$db, $dbKey);
        $fieldStr = Build::fieldStr($fields);
        list($whereStr, $whereParams, $whereValues) = Build::whereParams($cond);
        list($orderStr, $orderParams, $orderValues) = Build::orderParams($order);
        list($limitStr, $limitParams, $limitValues) = Build::limitParams($limit);
        $sql = sprintf(
            "SELECT %s FROM `%s` %s %s%s%s",
            $fieldStr,
            self::getTable(),
            !empty($whereStr) ? 'WHERE' : '',
            $whereStr,
            $orderStr,
            $limitStr
        );
        list($stmt, $result) = Query::execute($pdo, $sql, $whereParams, $whereValues);
        return $stmt;
    }

    /**
     * 查询单行
     * @param   $fields <array>
     * @param $cond <array>
     * array(
     *      field1 => value1,
     *      field2 => ['>', value2],
     * )
     * @param   $order <string>
     * @param   $limit <string>
     * @param   $master <boolean>
     * @return
     */
    public static function row(Array $fields, Array $cond, $order = null, $fetch = \PDO::FETCH_OBJ, $master = false)
    {
        $limit = null;
        $stmt = self::select($fields, $cond, $order, $limit, $master);
        return $stmt->fetch($fetch);
    }

    /**
     * 查询多行
     * @param   $fields <array>
     * @param $cond <array>
     * array(
     *      field1 => value1,
     *      field2 => ['>', value2],
     * )
     * @param   $order <string>
     * @param   $limit <string>
     * @param   $master <boolean>
     * @return
     */
    public static function rows(Array $fields, Array $cond, $order = null, $limit = null, $fetch = \PDO::FETCH_OBJ, $master = false)
    {
        $stmt = self::select($fields, $cond, $order, $limit, $master);
        return $stmt->fetchAll($fetch);
    }

    public static function count(Array $cond)
    {
        // 获取链接
        $pdo = Conn::get(static::$db, static::$slaveKey);
        // 预处理SQL
        list($whereStr, $whereParams, $whereValues) = Build::whereParams($cond);
        $sql = sprintf("SELECT COUNT(*) FROM %s %s %s", self::getTable(), !empty($whereStr) ? 'WHERE' : '', $whereStr);
        // 返回PDOStatement
        list($stmt, $result) = Query::execute($pdo, $sql, $whereParams, $whereValues);
        return $stmt->fetchColumn();
    }

    public static function query($sql, $params, $values, $master = false)
    {
        // 获取链接
        $dbKey = $master ? static::$masterKey : static::$slaveKey;
        $pdo = Conn::get(static::$db, $dbKey);
        // 返回PDOStatement
        list($stmt, $result) = Query::execute($pdo, $sql, $params, $values);
        return $stmt;
    }

    /**
     * 事务开启
     */
    public static function begTransaction()
    {
        try {
            $pdo = Conn::get(static::$db, static::$masterKey);

            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
            $pdo->beginTransaction();
        } catch (\PDOException $e) {
            $pdo->rollback();
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
            throw new \Exception("Begin transaction fail", 500);
        }
    }

    /**
     * 事务提交
     */
    public static function commTransaction()
    {
        try {
            $pdo = Conn::get(static::$db, static::$masterKey);

            $pdo->commit();
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        } catch (\PDOException $e) {
            $pdo->rollback();
            throw new \Exception("Commit transaction fail", 500);
        }
    }

    /**
     * 事务回滚
     */
    public static function rollTransaction()
    {
        try {
            $pdo = Conn::get(static::$db, static::$masterKey);

            $pdo->rollback();
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        } catch (\PDOException $e) {
            throw new \Exception("Rollback transaction fail", 500);
        }
    }
}
