<?php
namespace BxdFramework\Base;

/**
 * 错误码
 * @see http://localhost/display/public/0-SrvCode
 */
class Code
{
    // framework
    const OK = 0;               // 成功
    const INI_DIR_NONE = 1;     // 无初始化配置目录
    const INI_SWOOLE_NONE = 2;     // 无swoole配置信息
    const INI_SWOOLE_INVALID = 3;     // 无效swoole配置信息
    const INI_DIR_INVALID = 4;     // 无效配置目录
    const CONF_KEY_INVALID = 5;     // 无效的配置键
    const REQ_URI_INVALID = 6; 		// 请求uri无效
    const METHOD_NOT_ALLOW = 7;		// 请求方法不允许访问
    const REST_METHOD_NOT_FOUND = 8;// rest请求方法不存在
    const METHOD_NOT_FOUND = 9;     // 请求方法不存在
    const LACKOF_REQUIRED_PARAM = 10;       // 缺少必须参数
    const INVALID_REQUIRED_PARAM = 11;      // 必须参数非法
    const METHOD_AUTHZ_FAILED = 12;		// 方法未授权
    const NULL_REDIS_CONFIG = 13; // redis配置不存在
    const NULL_RABBITMQ_CONFIG = 14; // rabbitmq配置不存在
    const RABBITMQ_EXCHANGE_ATTR_ERROR = 15; // rabbitmq交换机属性为空
    const RABBITMQ_EXCHANGE_NAME_NULL = 16; // rabbitmq交换机名为空
    const RABBITMQ_QUEUE_ATTR_NULL = 17; // rabbitmq队列属性为空

    /* SPU接口 */
    const API_SPU_RESULT_NULL = 100000; // 结果为空
    const API_RMQ_PARAMS_NULL = 100001; // 参数错误
}
