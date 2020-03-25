<?php
namespace BxdFramework\Basic;

use BxdFramework\Base\Conf;

class SpeedLog
{
    private $ts;
    private $key;
    private $file;
    private static $speed = NULL;

    const STEP_ACT_BEG = 0;
    const STEP_ACT_END = 1;

    const MIN_USE_MILLITIME = 0.5;

    const LOG_CONF_KEY = 'log.srv_server.srv.0';

    /**
     * 构造 
     */
    private function __construct()
    {
        $logConf = Conf::get(self::LOG_CONF_KEY);
        $this->file = sprintf($logConf['speed_log_file'], date('Ymd'));
    }

    /**
     * 获取实例
     */
    static private function getInstance()
    {
        static $instance;
        if($instance) return $instance;
        $instance = new self();
        return $instance;
    }

    static public function clear()
    {
        $instance = self::getInstance();
        $instance->ts = '';
        $instance->key = '';
        $instance->file = '';
        self::$speed = NULL;
    }

    static public function setKey($key)
    {
        if(!$key) return;
        $instance = self::getInstance();
        $instance->ts = microtime(true);
        $instance->key = $key;
    }

    /**
     * 记录日志
     */
    static public function addLog($func, $step)
    {
        if(defined('NOSPEED')) return;
        $instance = self::getInstance();
        if(!$func) {
            throw new \Exception("Invalid add: $func $step", 500);
        }

        if($step == self::STEP_ACT_BEG) {
            self::$speed[$func][self::STEP_ACT_BEG] = microtime(true);
        } elseif($step == self::STEP_ACT_END) {
            if(!isset(self::$speed[$func][self::STEP_ACT_BEG])) {
                throw new \Exception("Invalid add: $func beg_time invalid");
            }
            self::$speed[$func][self::STEP_ACT_END] = microtime(true);
        } else {
            throw new \Exception("Invalid add: $func $step", 500);
        }
    }

    /**
     * 记录日志
     */
    public function speedLog()
    {
        $instance = self::getInstance();
        if(!$instance->file) {
            throw new \Exception('Invalid file: ' . $instance->file, 500);
        }

        $instance->ts = $instance->ts ?? microtime(true);
        $instance->key = $instance->key ?? 'SpeedLog';

        $speed = self::$speed ?? [];
        if(!$speed) {
            throw new \Exception("Invalid log", 500);
        }

        $content = '';
        $head = sprintf("%s|%.4f|%s", getmypid(), $instance->ts, $instance->key);
        foreach($speed as $key => $item) {
            if(!isset($item[0]) || !isset($item[1])) continue;
            $useTime = round(($item[1] - $item[0]) * 1000, 2);
            if($useTime >= self::MIN_USE_MILLITIME) {
                $content .= sprintf("%s|%s|%.1f\n", $head, $key, $useTime);
            }
        }

        if($content && !file_put_contents($instance->file, $content, FILE_APPEND | LOCK_EX)) {
            throw new \Exception('Invalid file: ' . $instance->file, 500);
        }
    }

    public static function resetStatic()
    {
        $instance = self::getInstance();
        // 如果没有打日志时，不执行，避免报错
        if(empty(self::$speed)) {
            return;
        }
        $instance->speedLog();
        self::$speed = null;
    }
}
