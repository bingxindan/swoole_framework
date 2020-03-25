<?php
namespace BxdFramework\RabbitMq;

use BxdFramework\Base\Code;
use BxdFramework\Base\Conf;

class AmqpPublish extends Amqp
{
    /**
     * 获取实例
     * @param $attribute
     * @return \AMQPExchange
     * @author zm
     */
    public static function getInstance($exchangeAttr, $setReadTimeout = 0)
    {
        // 设置链接
        $conn = self::setConnection($setReadTimeout);
        
        // channel
        $channel = self::setChannel($conn);
        
        // 用来绑定交换机
        $exchange = self::setExchange($channel, $exchangeAttr);
        
        return [$conn, $exchange];
    }
}