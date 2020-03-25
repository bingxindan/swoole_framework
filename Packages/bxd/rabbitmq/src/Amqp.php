<?php
namespace BxdFramework\RabbitMq;

use BxdFramework\Base\Code;
use BxdFramework\Base\Conf;

class Amqp
{
    protected static $conn;
    protected static $channel;
    protected static $exchange;

    // 属性和函数对应map
    private static $exchangeMap = [
        'exchangeName' => 'setName',
        'exchangeType' => 'setType',
        'exchangeFlags' => 'setFlags',
        'exchangeArguments' => 'setArguments',
        'exchangeArgument' => 'setArgument',
    ];

    // 队列 属性和函数对应map
    private static $queueMap = [
        'queueName' => 'setName',
        'queueFlags' => 'setFlags',
        'queueArguments' => 'setArguments',
        'queueArgument' => 'setArgument',
    ];
    
    public static function setConnection($setReadTimeout = 60)
    {
        return Conn::connect($setReadTimeout);
    }

    /**
     * 创建channel
     * @return \AMQPChannel
     * @author zm
     */
    public static function setChannel($conn)
    {
        return new \AMQPChannel($conn);
    }

    /**
     * 用来绑定交换机
     * @author zm
     */
    public static function setExchange($channel, $exchangeAttr)
    {
        if (empty($exchangeAttr)) {
            throw new \Exception('交换机属性为空', Code::RABBITMQ_EXCHANGE_ATTR_ERROR);
        }

        if (!isset($exchangeAttr['exchangeName'])) {
            throw new \Exception('交换机名为空', Code::RABBITMQ_EXCHANGE_NAME_NULL);
        }

        // 设置交换机
        $exchange = new \AMQPExchange($channel);

        // 设置交换机属性
        foreach ($exchangeAttr as $attrField => $attrVal) {
            if (!isset(self::$exchangeMap[$attrField])) {
                continue;
            }
            $fun = self::$exchangeMap[$attrField];
            $exchange->$fun($attrVal);
        }

        $exchange->declareExchange();
        
        return $exchange;
    }

    /**
     * 设置队列属性
     * @param $queueAttr
     * @throws \Exception
     * @author zm
     */
    public static function setQueue($channel, $queueAttr)
    {
        if (empty($queueAttr)) {
            throw new \Exception('队列属性为空', Code::RABBITMQ_QUEUE_ATTR_NULL);
        }

        // 设置队列
        $queue = new \AMQPQueue($channel);

        // 设置队列属性
        foreach ($queueAttr as $attrField => $attrVal) {
            if (!isset(self::$queueMap[$attrField])) {
                continue;
            }
            $fun = self::$queueMap[$attrField];
            $queue->$fun($attrVal);
        }

        $queue->declareQueue();
        
        return $queue;
    }

    // 断开链接
    public static function disconnect($conn)
    {
        $conn->disconnect();
    }
    
    public static function reset()
    {
        Conn::reset();
    }
}