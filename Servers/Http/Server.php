<?php
define('PROJECT_WORK_ROOT', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR); // 项目根目录
include PROJECT_WORK_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use BxdFramework\Base\Dispatcher;
use BxdFramework\Base\Request;
use BxdFramework\Base\Response;
use BxdFramework\Base\Conf;
use BxdFramework\Base\Code;
use BxdFramework\Basic\BxdLog;
use BxdFramework\RestCall\RestAPI;
use BxdFramework\Sign\Sign;
use BxdFramework\Base\Bootstrap;

/**
 * Http协议的服务端
 * Class Server
 * @package PROJECT\Service\Http
 * @Author: zhangming
 */
class Server extends Bootstrap
{
    const SW_CONF_KEY = 'swoole.srv_server.srv.0';

    protected $host = '127.0.0.1';
    protected $port = 9502;

    protected static $swIni = [];       // 加载配置
    protected $services = [];
    protected static $configs = [];     // 加载配置
    protected $instance = null;         // 主服务HTTP
    protected $redisService = null;     // Redis协议服务
    protected $webSocketService = null; // websocket协议服务
    protected $register = [];           // Redis注册的方法
    protected $currentFd = null;
    protected static $memoryUsed = 0;   // 内存使用
    protected $requestLog;              // 请求日志文件

    // 初始化路由
    protected $routes = [];

    public function __construct()
    {
        try {
            // 初始化
            self::resetParams();
            // 加载配置 启动前调用
            self::startUp(PROJECT_WORK_ROOT);
            // 启动服务
            $this->start();
        } catch (Exception $e) {
            $msg = 'consturct fail, msg:' . $e->getMessage() . ', code:' . $e->getCode();
            $this->_log($msg, 'ERROR');
        }
    }

    public function __destruct()
    {

    }

    public static function resetParams()
    {
        self::$ini = [];
    }

    /**
     * 打印日志
     * @param $msg
     * @param string $level
     * @author zm
     */
    public function _log($msg, $level = 'INFO')
    {
        echo "[" . date('Y-m-d H:i:s') . "] [$level] $msg\n";
    }

    public function start()
    {
        // 出现FatalError时，向客户端返回错误信息
        register_shutdown_function([$this, 'onShutdown']);

        // swoole配置数据
        $swooleConf = Conf::get(self::SW_CONF_KEY);
        if (empty($swooleConf)) {
            throw new Exception("Start fail, empty swoole ini\n", Code::INI_SWOOLE_NONE);
        }
        if (empty($swooleConf['host']) || empty($swooleConf['port'])) {
            throw new Exception("Start fail, invalid swoole ini\n", Code::INI_SWOOLE_INVALID);
        }
        
        // 启动swoole服务
        $port = PROJECT_ENV_FLAG === '' ? $swooleConf['port'] : ($swooleConf['port'] + PROJECT_ENV_FLAG);
        $this->instance = new \swoole_http_server($swooleConf['host'], $port);
        unset($swooleConf['host'], $swooleConf['port']);
        // 格式化日志日期
        $date = date('Ymd');
        $swooleConf['log_file'] = sprintf($swooleConf['log_file'], $date);
        $this->requestLog = sprintf($swooleConf['request_file'], $date);
        $this->instance->set($swooleConf);
        $this->instance->on('Start', array($this, 'onStart'));
        $this->instance->on('Manager', array($this, 'onManagerStart'));
        $this->instance->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->instance->on('Request', array($this, 'onRequest'));
        $this->instance->on('Task', array($this, 'onTask'));
        $this->instance->on('Finish', array($this, 'onFinish'));
        $this->instance->start();
    }

    /**
     * 启动master
     * 之前的程序和加载的内容如有改动需重启master才能有效
     * @author zm
     */
    public function onStart()
    {
        // 其它系统这段程序会报错
        if ('Linux' == PHP_OS) {
            swoole_set_process_name(PROJECT_NAME . ':Master ' . PROJECT_ENV_FLAG);
        }
        $this->_log(PROJECT_NAME . ':Master ' . PROJECT_ENV_FLAG);
    }

    /**
     * swoole中worker/task进程都是由Manager进程Fork并管理的
     * @author zm
     */
    public function onManagerStart()
    {
        if ('Linux' == PHP_OS) {
            swoole_set_process_name(PROJECT_NAME . ':Manager ' . PROJECT_ENV_FLAG);
        }
        $this->_log(PROJECT_NAME . ':Manager ' . PROJECT_ENV_FLAG);
    }

    /**
     * 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
     * @param $serv
     * @param $worker_id
     * @author zm
     */
    public function onWorkerStart($server, $worker_id)
    {
        Conf::load(self::$ini['init']['dir'], explode(',', self::$ini['swoole']['workerConf']));

        if ($worker_id >= $server->setting['worker_num']) {
            // 投递一个异步任务到task_worker池中。此函数是非阻塞的，执行完毕会立即返回
            if ('Linux' == PHP_OS) {
                swoole_set_process_name(PROJECT_NAME . ':Task ' . PROJECT_ENV_FLAG);
            }
            define('PROJECT_PROCESS', 'task');
            $this->_log(PROJECT_NAME . ':Task ' . PROJECT_ENV_FLAG);
        } else {
            // 启动worker进程，接受由Reactor线程投递的请求数据包，并执行PHP回调函数处理数据
            if ('Linux' == PHP_OS) {
                swoole_set_process_name(PROJECT_NAME . ':Worker ' . PROJECT_ENV_FLAG);
            }
            define('PROJECT_PROCESS', 'worker');
            $this->_log(PROJECT_NAME . ':Worker ' . PROJECT_ENV_FLAG);
        }
    }

    /**
     * Http服务器的支持，异步 非阻 塞多进程
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     * @author zm
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        // 查看内存使用
        $memoryStatus = $this->getMemoryUseStatus();
        // 请求开始时间
        $begTime = microtime(true);
        
        $this->currentFd = $response->fd;

        foreach ($request->server as $k => $v) {
            Request::server(strtoupper($k), $v);
        }
        foreach ($request->header as $k => $v) {
            Request::server(strtoupper($k), $v);
            Request::server('HTTP_' . strtoupper($k), $v);
        }
        Request::server('worker_id', getmypid());
        foreach ((array)$request->get as $k => $v) {
            Request::get($k, $v);
        }
        foreach ((array)$request->post as $k => $v) {
            Request::post($k, $v);
        }

        try {
            $uri = trim(Request::server('REQUEST_URI'), '/');
            // nginx在header中传的项目类型， 0:srv;1:pub_api;2:pri_api;
            $requestProjectType =0;
            $projectTypes = json_decode(self::$ini['init']['projectType'], true);
            if (!isset($projectTypes[$requestProjectType])) {
                throw new Exception('项目类型不在规范内，请联系运维', '501');
            }
            if (in_array($requestProjectType, array(1))) {
                $requestParam = Request::request();
                if (empty($requestParam)) {
                    throw new \Exception("lackof requested params", Code::LACKOF_REQUIRED_PARAM);
                }
                $sign = new Sign($requestParam);
                $sign->handle($request);
            }
            $code = Response::getCode();
            if (!$code) {
                // 解析当前路由
                Dispatcher::getInstance()->proceed($projectTypes[$requestProjectType], $uri);
            }
        } catch (\Throwable $ex) {
            BxdLog::error('onRequest [' . $ex->getMessage() . '][' . $ex->getTraceAsString() . ']');
            Response::debug($ex->getMessage() . "<br/><pre>" . $ex->getTraceAsString() . '</pre>');
        }
        $code = !isset($code) ? Response::getCode() : $code;
        $response->header('Content-Type', Response::contentType());
        $str = Response::result();
        if (strlen($str) > self::$ini['init']['routerTableSize']) {
            //$response->gzip();
        }
        $response->end($str);
        // 每一个请求流程时间
        $this->requestUseTime($begTime, $memoryStatus);
        // 清除变量内存使用
        Response::reset();
    }

    public function onTask()
    {
    }

    public function onFinish()
    {
    }

    public function onShutdown()
    {
        $error = error_get_last();
        if (empty($error)) {
            return;
        }
        if (Request::type() == Request::HTTP) {
            if (Request::inDebug()) {
                $str = Response::result();
            } else {
                $str = Response::fatalError(500, $error['message'], $error['file'], $error['line']);
            }
            $length = strlen($str);
            $header = "HTTP/1.1 500 Internal Server Error\r\nServer: bingxindan-FatalError\r\nContent-Type: text/html\r\nContent-Length: $length\r\n\r\n$str";
            $this->instance->send($this->currentFd, $header);
        }
        Response::reset();
        self::resetParams();
    }

    /**
     * 一个请求流程时间
     * @param $begTime
     * @author zm
     */
    public function requestUseTime($begTime, $memoryStatus)
    {
        $useTime = round((microtime(true) - $begTime) * 1000, 2);
        $requestConsuming = sprintf(
            "REQUEST: [%s] [%s] [%s: %s] %s %s %s [worker_id:%s]\n",
            date('Y-m-d H:i:s', intval($begTime)),
            $useTime, 
            Request::server('REQUEST_METHOD'),
            Request::server('REQUEST_URI'),
            json_encode(Request::get()), 
            json_encode(Request::post()), 
            $memoryStatus, 
            Request::server('worker_id')
        );
        if($requestConsuming && !file_put_contents($this->requestLog, $requestConsuming, FILE_APPEND | LOCK_EX)) {
            throw new \Exception('Invalid file: ' . $this->requestLog, 500);
        }
    }

    /**
     * 内存使用状态
     * @return string
     * @author zm
     */
    public function getMemoryUseStatus()
    {
        $nowMemoryUsed = memory_get_usage();
        $diffMemoryUsed = $nowMemoryUsed - self::$memoryUsed;
        self::$memoryUsed = $nowMemoryUsed;
        return sprintf("[request_memory:%s] [last_request_memory_diff:%s]", $nowMemoryUsed, $diffMemoryUsed);
    }
}

// 启动master
$app = new Server();
