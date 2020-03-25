<?php

namespace BxdFramework\Basic;
use BxdFramework\Base\Conf;

class BxdLog
{
    const LOG_CONF_KEY = 'log.srv_server.srv.0';

    static public function redis($host, $method, $arguments, $class, $time)
    {
        $logConf = Conf::get(self::LOG_CONF_KEY);
        if($logConf['sql_log_status'] == 1){
            $logFile = sprintf($logConf['redis_log_file'], date('Ymd'));
            $logInfo = sprintf(
                "[%s] [%s] [%s] [%s] [%s] [%s]%s", 
                date('Y-m-d H:i:s'), 
                $time, 
                $host, 
                $method, 
                $arguments, 
                $class, 
                PHP_EOL
            );
            file_put_contents($logFile, $logInfo, FILE_APPEND | LOCK_EX);
        }
    }
    
    static public function sql($sql, $time, $host)
    {
        $logConf = Conf::get(self::LOG_CONF_KEY);
        if($logConf['sql_log_status'] == 1){
            $logFile = sprintf($logConf['sql_log_file'], date('Ymd'));
            $logInfo = sprintf("[%s] [%s] [%s] [%s]%s", date('Y-m-d H:i:s'), $time, $host, $sql, PHP_EOL);
            file_put_contents($logFile, $logInfo, FILE_APPEND | LOCK_EX);
        }
    }

    static public function info($info)
    {
        $logConf = Conf::get(self::LOG_CONF_KEY);
        $logFile = sprintf($logConf['run_log_file'], date('Ymd'));
        $logInfo = sprintf("[%s] [info] %s%s", date('Y-m-d H:i:s'), $info, PHP_EOL);
        file_put_contents($logFile, $logInfo, FILE_APPEND | LOCK_EX);
    }

    static public function debug($info)
    {
        $trace = '';
        $debug = debug_backtrace();
        foreach($debug as $key => $item) {
            $trace .= $item['function'] . ' on ' . $item['file'] . ' at ' . $item['line'] . PHP_EOL;
        }
        $logConf = Conf::get(self::LOG_CONF_KEY);
        $logFile = sprintf($logConf['run_log_file'], date('Ymd'));
        $logInfo = sprintf('[%s] [debug] %s%s%s', date('Y-m-d H:i:s'), $info, PHP_EOL, $trace);
        file_put_contents($logFile, $logInfo, FILE_APPEND | LOCK_EX);
    }

    static public function error($info)
    {
        $logConf = Conf::get(self::LOG_CONF_KEY);
        $logFile = sprintf($logConf['run_log_file'], date('Ymd'));
        $logInfo = sprintf("[%s] [error] %s%s", date('Y-m-d H:i:s'), $info, PHP_EOL);
        file_put_contents($logFile, $logInfo, FILE_APPEND | LOCK_EX);
    }

    static public function TCPError($info)
    {
        $logConf = Conf::get(self::LOG_CONF_KEY);
        $logFile = sprintf($logConf['tcp_run_log_file'], date('Ymd'));
        $logInfo = sprintf("[%s] [error] %s%s", date('Y-m-d H:i:s'), $info, PHP_EOL);
        file_put_contents($logFile, $logInfo, FILE_APPEND | LOCK_EX);
    }

    static public function sendWarning($info) {
        $logConf = Conf::get(self::LOG_CONF_KEY);
        $url = $logConf['warning_url'];
        $get_data = 'info=' . $info;
        $url = $url . '?' . $get_data;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($curl);
        $data = json_decode($result, true);
        return $data;
    }
}
