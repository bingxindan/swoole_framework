<?php
namespace BxdFramework\RabbitMq\AmqpV1;

use BxdFramework\Base\Code;
use BxdFramework\Base\Conf;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConnV1
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
    static public function getInstance()
    {
        if (self::$rabbitmq) {
            return self::$rabbitmq;
        }
        $rabbitmq = self::connect();
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
    static public function connect()
    {
        $conn = null;
        $configList = self::loadConfig();
        if (!$configList) {
            throw new \Exception("null rabbitmq config", Code::NULL_RABBITMQ_CONFIG);
        }

        foreach ($configList as $v) {
            //创建连接
            $conn = new AMQPStreamConnection(
                $v['host'],
                $v['port'],
                $v['user'],
                $v['password'],
                $v['vhost']
            );
            if (!$conn) {
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