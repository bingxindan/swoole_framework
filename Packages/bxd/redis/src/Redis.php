<?php
namespace BxdFramework\BxdRedis;

use BxdFramework\Base\Conf;
use BxdFramework\Base\Code;
use BxdFramework\Basic\BxdLog;

class Redis
{
    private static $redis;
    private static $host;
    private $client;
    private $client_host;

    /**
     * 构造
     * BxdRedis constructor.
     * @param $client
     * @param $host
     */
    private function __construct($client, $host)
    {
        $this->client = $client;
        $this->client_host = $host;
    }

    /**
     * 单例
     * @return bool|BxdRedis
     */
    static public function getInstance()
    {
        if (self::$redis) {
            return self::$redis;
        }
        $redis = self::connect();
        if (empty($redis)) {
            return false;
        }
        self::$redis = new self($redis, self::$host);
        return self::$redis;
    }

    /**
     * 魔法函数，拦截redis操作
     * @param $method
     * @param $arguments
     * @return bool
     */
    public function __call($method, $arguments)
    {
        if (empty($this->client)) {
            return false;
        }
        try {
            $start = microtime(true);
            $result = $this->client->$method(...$arguments);
            $end = microtime(true);
            BxdLog::redis($this->client_host, $method, json_encode($arguments), __CLASS__, ($end - $start) * 1000);
            return $result;
        } catch (\Exception $exc) {
            return false;
        }
    }

    /**
     * 连接
     * @return \Redis
     */
    static public function connect()
    {
        $redis = new \Redis();
        $configList = self::loadConfig();
        if (!$configList) {
            throw new \Exception("null redis config", Code::NULL_REDIS_CONFIG);
        }
        foreach ($configList as $v) {
            $ret = $redis->pconnect($v['HOST'], $v['PORT']);
            if (!$ret) {
                continue;
            }
            if (!$v['PASSWORD']) {
                break;
            }
            $ret = $redis->auth($v['PASSWORD']);
            if (!$ret) {
                continue;
            }
            self::$host = $v['HOST'];
            break;
        }
        return $redis;
    }

    /**
     * 加载配置
     * @return array
     */
    static private function loadConfig()
    {
        $list = [];
        $cluster = Conf::get('redis.main.master');
        if ($cluster) {
            foreach ($cluster as $v) {
                $list[] = [
                    'HOST' => isset($v['host']) ? $v['host'] : '',
                    'PORT' => isset($v['port']) ? $v['port'] : '',
                    'PASSWORD' => isset($v['password']) ? $v['password'] : '',
                ];
            }
            shuffle($list);
        }
        return $list;
    }
}