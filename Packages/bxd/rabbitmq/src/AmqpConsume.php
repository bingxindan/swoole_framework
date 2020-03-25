<?php
namespace BxdFramework\RabbitMq;

use BxdFramework\Base\Code;
use BxdFramework\Base\Conf;

class AmqpConsume extends Amqp
{
    /**
     * 
     * @param $attribute
     * @return \AMQPExchange
     * @author zm
     */
    public static function getInstance($exchangeAttr, $queueAttr, $setReadTimeout = 60)
    {
        // 设置链接
        $conn = self::setConnection($setReadTimeout);
        
        // channel
        $channel = self::setChannel($conn);
        
        // 用来绑定交换机
        $exchange = self::setExchange($channel, $exchangeAttr);
        
        // 创建队列
        $queue = self::setQueue($channel, $queueAttr);
        
        // 绑定交换机和队列
        $queue->bind($exchangeAttr['exchangeName'], $queueAttr['routingKey']);
        
        return [$conn, $queue];
    }
}