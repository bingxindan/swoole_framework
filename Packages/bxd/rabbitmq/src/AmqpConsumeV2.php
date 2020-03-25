<?php
namespace BxdFramework\RabbitMq;

class AmqpConsumeV2
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
        $conn = ConnV2::getInstance();
        
        // channel
        $channel = $conn->channel();
        
        return [$conn, $channel];
    }
}