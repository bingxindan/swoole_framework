<?php

namespace BxdFramework\BxdMysql;

use BxdFramework\Basic\BxdLog;

class DB
{

    // 库实例
    private $name;
    // 库配置
    private $config;
    private $master;
    // 库链接
    private static $conn;
    private static $conf;

    private $settingOpr = ['=' => 1, '+' => 1, '-' => 1, '*' => 1, '/' => 1];
    private $conditionOpr = ['=' => 1, '!=' => 1, '<>' => 1, '>' => 1, '>=' => 1, '<' => 1, '<=' => 1, 'IN' => 1, 'NOT IN' => 1];

    /**
     * 构造
     */
    private function __construct($dbName)
    {
        // 初始化参数
        $this->name = $dbName;
    }

    /**
     * 实例化
     * @param $dbName    <string>   实例
     */
    static public function getInstance($dbName)
    {
        static $instance;
        if(isset($instance[$dbName])) return $instance[$dbName];
        $instance[$dbName] = new self($dbName);
        return $instance[$dbName];
    }

    /**
     * 加载配置
     * @param
     * [(
     *  host => 地址
     *  port => 端口
     *  name => 账号
     *  pass => 密码
     *  database => 库
     *  charset => 字符
     *  collation => utf8_bin
     *  persistent => false
     * )]
     */
    static private function loadConfig($dbName)
    {
        $dbConf = [];

        $ini = (object)parse_ini_file(CONF_ENV_PATH . '/mysql.ini');
        $conf = $ini->get($dbName)->toArray();

        $name = $conf['database']['name'] ?? '';
        $master = $conf['database']['master'] ?? '';
        $slaves = $conf['database']['slaves'] ?? [];
        $persistent = $conf['database']['persistent'] ?? '';
        if(!$name) return $dbConf;

        if($master) {
            $dbConf['master']['user'] = $master['user'];
            $dbConf['master']['pass'] = $master['pass'];
            $dbConf['master']['name'] = $name;
            $dbConf['master']['host'] = $master['host'];
            $dbConf['master']['port'] = $master['port'];
            $dbConf['master']['persistent'] = $persistent;
        }

        if($slaves) {
            foreach($slaves as $key => $slave) {
                $dbConf['slaves'][$key]['user'] = $slave['user'];
                $dbConf['slaves'][$key]['pass'] = $slave['pass'];
                $dbConf['slaves'][$key]['name'] = $name;
                $dbConf['slaves'][$key]['host'] = $slave['host'];
                $dbConf['slaves'][$key]['port'] = $slave['port'];
                $dbConf['slaves'][$key]['persistent'] = $persistent;
            }
        }

        return $dbConf;
    }

    /**
     * 获取数据库链接
     */
    private function getConnection($master = false)
    {
        $this->master = $master;
        $dbName = sprintf("%s.%s", $this->name, $master ? 'master' : 'slaves');
        if(isset(self::$conn[$dbName]) && self::$conn[$dbName]) return self::$conn[$dbName];

        $dbConf = $this->loadConfig($this->name);
        $masterConf = $dbConf['master'] ?? [];
        $slavesConf = $dbConf['slaves'] ?? [];
        if($master) {
            self::$conn[$dbName] = $this->connect($masterConf);
            if(self::$conn[$dbName]) self::$conf[intval($master)] = $masterConf;
        } else {
            if(shuffle($slavesConf)) {
                $slavesConf[] = $masterConf;
                foreach($slavesConf as $key => $slave) {
                    self::$conn[$dbName] = $this->connect($slave);
                    if(self::$conn[$dbName]) {
                        self::$conf[intval($master)] = $masterConf;
                        break;
                    }
                }
            } else {
                self::$conn[$dbName] = $this->connect($masterConf);
                if(self::$conn[$dbName]) self::$conf[intval($master)] = $masterConf;
            }
        }

        if(!self::$conn[$dbName]) throw new \Exception("Mysql connect $dbName fail", 500);

        return self::$conn[$dbName];
    }

    /**
     * 建立链接
     */
    private function connect($config)
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['name']);
            $pdo = new \PDO($dsn, $config['user'], $config['pass']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            if($config['persistent']) $pdo->setAttribute(\PDO::ATTR_PERSISTENT);
        } catch(\PDOException $e) {
            $pdo = false;
        }
        return $pdo;
    }

    /**
     * 事务开启
     */
    public function begTransaction()
    {
        $conn = $this->getConnection(true);
        try {
            $conn->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
            $conn->beginTransaction();
        } catch(\PDOException $e) {
            $conn->rollback();
            $conn->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
            throw new \Exception("Begin transaction fail", 500);
        }
    }

    /**
     * 事务提交
     */
    public function commTransaction()
    {
        $conn = $this->getConnection(true);
        try {
            $conn->commit();
            $conn->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        } catch(\PDOException $e) {
            $conn->rollback();
            throw new \Exception("Commit transaction fail", 500);
        }
    }

    /**
     * 事务回滚
     */
    public function rollTransaction()
    {
        $conn = $this->getConnection(true);
        try {
            $conn->rollback();
            $conn->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        } catch(\PDOException $e) {
            throw new \Exception("Rollback transaction fail", 500);
        }
    }

    /**
     * 外部接口-获取插入id
     * @return id   <int>   插入id
     */
    public function insertId()
    {
        $conn = $this->getConnection(true);
        return $conn->lastInsertId();
    }

    /**
     * @param $params       <array>
     * array(
     *      where => 条件(必须)
     * )
     * @param $master       <boolean>   主库链
     * @return $stat        <object>    stat对象
     */
    public function count($table, $params, $master = false)
    {
        // 获取链接
        $conn = $this->getConnection($master);
        // 预处理SQL
        list($whereStr, $whereParams, $whereValues) = $this->genWhereParams($params['where']);
        $sql = sprintf("SELECT count(1) cnt FROM %s WHERE %s", $table, $whereStr);
        // 返回PDOStatement
        return $this->execute($conn, $sql, $whereParams, $whereValues);
    }

    /**
     * 外部接口-查询
     * @param $table        <string>    表名
     * @param $params       <array>
     * array(
     *      field => 字段(必须)
     *      where => 条件(必须)
     *      order => 排序
     *      limit => 分页
     * )
     * @param $master       <boolean>   是否用主库链
     * @return $stat        <object>    stat对象
     */
    public function select($table, $params, $master = false)
    {
        // 获取链接
        $conn = $this->getConnection($master);
        // 预处理SQL
        $fieldStr = $this->genFieldStr($params['field']);
        list($whereStr, $whereParams, $whereValues) = $this->genWhereParams($params['where']);
        list($orderStr, $orderParams, $orderValues) = isset($params['order']) ? $this->genOrderParams($params['order']) : ['',[],[]];
        list($limitStr, $limitParams, $limitValues) = isset($params['limit']) ? $this->genLimitParams($params['limit']) : ['',[],[]];
        $selectParams = $whereParams + $limitParams;
        $selectValues = $whereValues + $limitValues;
        $sql = sprintf("SELECT %s FROM `%s` WHERE %s%s%s", $fieldStr, $table, $whereStr, $orderStr, $limitStr);
        // 返回PDOStatement
        return $this->execute($conn, $sql, $selectParams, $selectValues);
    }

    /**
     * 外部接口-更新
     * @param $table        <string>    表名
     * @param $params       <array>
     * array(
     *      where => 赋值(必须)
     *      value => 字段(必须)
     * )
     * @return $stat        <object>    stat对象
     */
    public function update($table, $params)
    {
        // 获取链接
        $conn = $this->getConnection(true);
        // 预处理SQL
        list($whereStr, $whereParams, $whereValues) = $this->genWhereParams($params['where']);
        list($updateStr, $updateParams, $updateValues) = $this->genUpdateParams($params['update']);
        list($limitStr, $limitParams, $limitValues) = isset($params['limit']) ? $this->genLimitParams($params['limit']) : ['',[],[]];
        $updateParams = $whereParams + $updateParams + $limitParams;
        $updateValues = $whereValues + $updateValues + $limitValues;
        $sql = sprintf("UPDATE `%s` SET %s WHERE %s%s", $table, $updateStr, $whereStr, $limitStr);
        // 返回PDOStatement
        return $this->execute($conn, $sql, $updateParams, $updateValues);
    }

    /**
     * 外部接口-删除
     * @param $table        <string>    表名
     * @param $params       <array>
     * array(
     *      where => 赋值(必须)
     * )
     * @return $stat        <object>    stat对象
     */
    public function delete($table, $params)
    {
        // 获取链接
        $conn = $this->getConnection(true);
        // 预处理SQL
        list($whereStr, $whereParams, $whereValues) = $this->genWhereParams($params['where']);
        list($limitStr, $limitParams, $limitValues) = isset($params['limit']) ? $this->genLimitParams($params['limit']) : ['',[],[]];
        $deleteParams = $whereParams + $limitParams;
        $deleteValues = $whereValues + $limitValues;
        $sql = sprintf("DELETE FROM `%s` WHERE %s%s", $table, $whereStr, $limitStr);
        // 返回PDOStatement
        return $this->execute($conn, $sql, $deleteParams, $deleteValues);
    }

    /**
     * 补充特殊字符'`'
     * SELECT *,`a`,`b` FROM d_test
     */
    private function genFieldStr($data)
    {
        $str = '*';
        if(!$data) return $str;

        $data = is_array($data) ? $data : explode(',', $data);
        foreach($data as $key => $item) {
            $item = trim($item);
            $data[$key] = strpos($item, '*') !== false ? $item : "`$item`";
        }

        $str = implode(', ', $data);
        return $str;
    }

    /**
     * 生成条件stat参数
     * SELECT * FROM d_test WHERE a = :w0 AND b = :w1
     * UPDATE d_test SET ... WHERE a = :w0 AND b = :w1;
     * DELETE FROM d_test WHERE a = :w0 AND b = :w1;
     */
    private function genWhereParams($data)
    {
        $cnt = 0;
        $str = '';
        $arr = [];
        $params = [];
        $values = [];
        if(!is_array($data)) return [$data, $params, $values];

        foreach($data as $column => $condition) {
            if(is_array($condition)) {
                foreach($condition as $operator => $quantity) {
                    if(!isset($this->conditionOpr[$operator])) continue;
                    if(is_array($quantity)) {
                        $pArr = [];
                        foreach($quantity as $key => $value) {
                            $p = sprintf(":w%d", $cnt++);
                            $pArr[] = $p;
                            $params[$p] = $column;
                            $values[$p] = $value;
                        }
                        $arr[] = sprintf("`%s` %s(%s)", $column, $operator, implode(', ', $pArr));
                    } else {
                        $p = sprintf(":w%d", $cnt++);
                        $params[$p] = $column;
                        $values[$p] = $quantity;
                        $arr[] = sprintf("`%s` %s %s", $column, $operator, $p);
                    }
                }
            } else {
                $p = sprintf(":w%d", $cnt++);
                $params[$p] = $column;
                $values[$p] = $condition;
                $arr[] = sprintf("`%s` = %s", $column, $p);
            }
        }

        $str = implode(' AND ', $arr);
        return [$str, $params, $values];
    }

    /**
     * 生成更新值stat参数
     * UPDATE d_test SET a = :u0, b = :u1 WHERE 1;
     */
    private function genUpdateParams($data)
    {
        $cnt = 0;
        $str = '';
        $arr = [];
        $params = [];
        $values = [];
        if(!is_array($data)) return [$data, $params, $values];

        foreach($data as $column => $setting) {
            if(is_array($setting)) {
                foreach($setting as $key => $item) {
                    if(!isset($this->settingOpr[$key])) continue;
                    $p = sprintf(":u%d", $cnt++);
                    $params[$p] = $column;
                    $values[$p] = $item;
                    $arr[] = $key === "=" ? sprintf("`%s` = %s", $column, $p) : sprintf("`%s` = `%s` %s %s", $column, $column, $key, $p);
                }
            } else {
                $p = sprintf(":u%d", $cnt++);
                $params[$p] = $column;
                $values[$p] = $setting;
                $arr[] = sprintf("`%s` = %s", $column, $p);
            }
        }
        $str = implode(', ', $arr);

        return [$str, $params, $values];
    }

    /**
     * 生成插入值stat参数
     * INSERT d_test (a, b) VALUES (:v0, :v1)
     */
    private function genInsertParams($data)
    {
        $cnt = 0;
        $str = '';
        $strArr = [];
        $params = [];
        $values = [];
        if(!is_array($data)) return [$data, $params, $values];

        $data = isset($data[0]) && is_array($data[0]) ? $data : [$data];
        foreach($data as $key => $item) {
            $pArr = [];
            foreach($item as $column => $assignment) {
                $p = sprintf(":v%d", $cnt++);
                $pArr[] = $p;
                $params[$p] = $column;
                $values[$p] = $assignment;
            }
            $strArr[] = sprintf('(%s)', implode(', ', $pArr));
        }
        $str = implode(', ', $strArr);

        return [$str, $params, $values];
    }

    /**
     * 生成排序stat参数
     * SELECT * FROM d_test WHERE 1 ORDER BY :o0, :o1;
     */
    private function genOrderParams($data)
    {
        $cnt = 0;
        $str = '';
        $arr = [];
        $params = [];
        $values = [];
        if(!is_array($data)) return [$data, $params, $values];

        $str = $data ? ' ORDER BY ' . implode(',', $data) : '';

        return [$str, $params, $values];
    }

    /**
     * 生成分页stat参数
     * SELECT * FROM d_test WHERE 1 LIMIT :l0, :l1;
     */
    private function genLimitParams($data)
    {
        $cnt = 0;
        $str = '';
        $arr = [];
        $params = [];
        $values = [];
        if(!is_array($data)) return [$data, $params, $values];

        foreach($data as $key => $item) {
            $p = sprintf(":l%d", $cnt++);
            $arr[] = sprintf("%s", $p);
            $params[$p] = $key;
            $values[$p] = $item;
        }
        $str = $arr ? ' LIMIT ' . implode(', ', $arr) : '';

        return [$str, $params, $values];
    }

    /**
     * 获取pdo完整sql
     * @see [http://php.net/manual/en/pdostatement.debugdumpparams.php#113400]
     */
    private function getFullSql($string, $data)
    {
        if(!$data || !is_array($data)) return $string;
        $indexed = $data == array_values($data);
        foreach($data as $k => $v) {
            if(is_string($v)) $v = "$v";
            $string = preg_replace("/$k/", $v, $string, 1);
        }
        return $string;
    }

    /**
     * 执行-预处理SQL
     * @param $conn     <conn>
     * @param $sql      <string>
     * @param $params   <array> array( param => col,)
     * @param $values   <array> array( param => val,)
     * @return $ret     <boolean>
     */
    public function execute($conn, $sql, $params = [], $values = [], $types = [])
    {
        $stmt = $conn->prepare($sql);

        // 绑定
        foreach($params as $param => $column) {
            $value = $values[$param];
            /**
             * pdo param type
             * @see [http://php.net/manual/en/pdo.constants.php]
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
             * @see [https://stackoverflow.com/questions/1179874/what-is-the-difference-between-bindparam-and-bindvalue]
             */
            $stmt->bindValue($param, $value, $type);
        }

        // 执行
        $execSql = $this->getFullSql($sql, $values);
        $begTime = microtime(true);
        $stmt->execute();
        $execTime = round((microtime(true) - $begTime) * 1000, 2);

        // 记录日志
        BxdLog::sql($execSql, $execTime, self::$conf[intval($this->master)]['host']);

        return $stmt;
    }

    /**
     * 执行-复杂SQL
     * @param $sql          <string>
     * @param $master       <boolean>
     * @return $stmt        <object>
     */
    public function query($sql, $master = false)
    {
        $conn = $this->getConnection($master);
        $stmt = $this->execute($conn, $sql, [], [], []);
        return $stmt;
    }
}
