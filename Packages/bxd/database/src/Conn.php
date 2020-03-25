<?php
namespace BxdFramework\DataBase;

use BxdFramework\Basic\BxdLog;
use BxdFramework\DataBase\Config;

class Conn
{

	// 库链
	static private $connList;
    // 库有效期
    static private $ttlList;
    const EXPIRE = 3600;

    static public function get ($db, $key)
    {
        $configKey = "$db.$key";
        if (!isset(self::$connList[$configKey]) || self::$connList[$configKey]['expire'] <= time() ) {
            $configList = Config::get("conn.$configKey");
            if (count($configList) > 1) {
                shuffle($configList);
            }
            foreach ($configList as $config) {
                $config['name'] = $db;
                $conn = self::connect($config);
                if ($conn) {
                    $expire = $config['expire'] ?? self::EXPIRE;
                    self::$connList[$configKey] = [
                        'conn' => $conn,
                        'expire' => time() + $expire,
                    ];
                    break;
                }
            }
        }
        return self::$connList[$configKey]['conn'] ?? false;
    }

    /**
     * 构造
     */
    static private function connect ($config)
    {
        $pdo = null;

        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['name']);
            $pdo = new \PDO($dsn, $config['user'], $config['pass']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            if($config['persistent']) $pdo->setAttribute(\PDO::ATTR_PERSISTENT);
        } catch(\PDOException $e) {
            $msg = sprintf('[PDO:connect][%s][%s][%s]', json_encode($config), $e->getCode(), $e->getMessage());
            BxdLog::error($msg);
        }

        return $pdo;
    }

}
