<?php
namespace BxdFramework\RabbitMq;

use BxdFramework\Base\Code;
use BxdFramework\Base\Conf;

class Conn
{
    private static $rabbitmq;

    /**
     * 构造
     * RabbitMq constructor.
     * @param $client
     * @param $host
     */
    private function __construct()
    {
    }

    /**
     * 单例
     * @return bool|BxdRedis
     */
    static public function getInstance($setReadTimeout = 60)
    {
        if (self::$rabbitmq) {
            return self::$rabbitmq;
        }
        $rabbitmq = self::connect($setReadTimeout);
        if (empty($rabbitmq)) {
            return false;
        }
        self::$rabbitmq = $rabbitmq;
        return self::$rabbitmq;
    }

    /**
     * 连接
     * @return \Redis
     */
    static public function connect($setReadTimeout = 60)
    {
        $conn = null;
        $configList = self::loadConfig();
        if (!$configList) {
            throw new \Exception("null rabbitmq config", Code::NULL_RABBITMQ_CONFIG);
        }

        foreach ($configList as $v) {
            //创建连接
            $conn = new \AMQPConnection();
            $conn->setHost($v['host']);
            $conn->setPort($v['port']);
            $conn->setLogin($v['user']);
            $conn->setPassword($v['password']);
            $conn->setVhost($v['vhost']);
            $conn->setReadTimeout($setReadTimeout);
            if (!$conn->connect()) {
                continue;
            }
            break;
        }
        
        return $conn;
    }

    /**
     * 加载配置
     * @return array
     */
    static private function loadConfig()
    {
        $list = [];
        $cluster = Conf::get('rabbitmq.main.master');
        if ($cluster) {
            foreach ($cluster as $v) {
                $list[] = [
                    'host' => isset($v['host']) ? $v['host'] : '',
                    'port' => isset($v['port']) ? $v['port'] : '',
                    'user' => isset($v['user']) ? $v['user'] : '',
                    'password' => isset($v['password']) ? $v['password'] : '',
                    'vhost' => isset($v['vhost']) ? $v['vhost'] : '/',
                ];
            }
            shuffle($list);
        }
        return $list;
    }
    
    public static function reset()
    {
        self::$rabbitmq = null;
    }
}