<?php
namespace BxdFramework\Base;

/**
 * 路由设置，直接找文件，不做任何解析
 * Class Dispatcher
 * @package BxdFramework\Base
 * @Author: zhangming <zhangming@bingxindan.com>
 */
class Dispatcher
{
    const URI_LENGTH = 2; // 限制uri 必须2个 controller/function
    private static $instance = null;

    private function __construct()
    {
    }

    /**
     * 单例 实例化
     * @param string $basePath
     * @return null|Dispatcher
     * @author zm
     */
    public static function getInstance($basePath = '')
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basePath);
        }
        return self::$instance;
    }

    /**
     * 直接执行函数逻辑
     * @param $projectType
     * @param string $uri
     * @author zm
     */
    public function proceed($projectType, $uri = '')
    {
        if ('' == $uri) {
            $uri = trim(Request::server('REQUEST_URI'), '/');
        }

        // 解析uri controller/fun
        $urlInfo = parse_url($uri);
        if (isset($urlInfo['path'])) {
            $queryArr = [];
            parse_str($urlInfo['path'], $queryArr);
            foreach ($queryArr as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                Request::get($k, $v);
            }
        }

        // uri模板 controller/function
        $uris = explode('/', trim($urlInfo['path']));
        if (empty($uris) || count($uris) != self::URI_LENGTH) {
            Response::error('no route found', 404);
            return;
        }

        // 类里函数
        $fun = $uris[1];
        // 拼接类名
        $className = 'BxdFramework\Controllers';

        /**
         * pubapi/priapi项目，把uri转换成规则目录样式
         * pub/controller
         */
        if (!empty($projectType) && $projectType != 'Srv') {
            $className .= '\\' . $projectType;
        }
        $className .= '\\' . $uris[0] . 'Controller';

        // 静态方式执行 controller::fun
        $className::$fun();
    }
}
