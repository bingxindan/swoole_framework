<?php
namespace BxdFramework\RabbitMq\AmqpV1;

class ConsumerV1
{
    /**
     * 获取管道
     * @param $attribute
     * @return \AMQPExchange
     * @author zm
     */
    public static function getChannel()
    {
        // 设置链接
        $conn = ConnV1::getInstance();
        
        // channel
        $channel = $conn->channel();
        
        return [$conn, $channel];
    }
}