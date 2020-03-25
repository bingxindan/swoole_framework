<?php

namespace BxdFramework\DataBase;

use BxdFramework\Basic\BxdLog;

class Query
{
    /**
     * 执行-预处理SQL
     * @param   $pdo        <conn>
     * @param   $sql        <string>
     * @param   $params     <array> array( param => col,)
     * @param   $values     <array> array( param => val,)
     * @return  $ret        <boolean>
     */
    static public function execute ($pdo, $sql, $params = [], $values = [], $types = [])
    {
        // 获取pdo链接状态
        $connectionStatus = $pdo->getAttribute(constant("PDO::ATTR_CONNECTION_STATUS"));
        $connectionStatus = explode(' ', $connectionStatus);
        $host = isset($connectionStatus[0]) ? $connectionStatus[0] : '';
        // 执行时间
        $begTime = microtime(true);
        // 预执行
        $stmt = $pdo->prepare($sql);

        // 绑定
        foreach($params as $param => $column) {
            $value = $values[$param];
            /**
             * pdo param type
             * @see http://php.net/manual/en/pdo.constants.php
             */
            if(isset($types[$column])) {
                $type = $types[$column];
            } elseif(is_int($value)) {
                $type = \PDO::PARAM_INT;
            } elseif(is_bool($value)) {
                $type = \PDO::PARAM_BOOL;
            } elseif(is_null($value)) {
                $type = \PDO::PARAM_NULL;
            } elseif(is_string($value)) {
                $type = \PDO::PARAM_STR;
            } else {
                $type = false;
            }
            /**
             * why not bindParam() ?
             * @see https://stackoverflow.com/questions/1179874/what-is-the-difference-between-bindparam-and-bindvalue
             */
            $stmt->bindValue($param, $value, $type);
        }
        $result = $stmt->execute();
        // 记录日志
        $execTime = round((microtime(true) - $begTime) * 1000, 2);
        BxdLog::sql($sql, $execTime, $host);
        return [$stmt, $result];
    }

}
