<?php

namespace BxdFramework\Refer;

use BxdFramework\Basic\BxdLog;
use BxdFramework\Basic\Common;

class Referer extends Base
{
    private $file;
    private $content;

    private $refererPage;
    private $refererLocation;
    private $dp;
    private $detail;

    private $share;
    private $shareId;
    private $shareUser;
    private $viewUser;
    private $page;
    private $objectId;

    const PARAM_KEY = 'referer';
    const REFERER_PAGE_KEY = 'rp';
    const REFERER_LOCATION_KEY = 'rl';
    const REFERER_LOCATION_SEPARATOR = '-';
    const DP_KEY = 'dp';

    const REFERER_SHARE_USER_KEY = 'ru';
    const REFERER_SHARE_USER_UNIQUE_KEY = 'rq';

    private static $instances;


    /**
     * 构造
     * @param $page
     * @param $objectId
     */
    private function __construct ($page, $objectId = '')
    {
        $this->file = $this->getFile();
        $this->sess = $this->getSess();
        $this->objectId  = $objectId;
        $this->page  = $page;
        $this->initParam();
    }

    /**
     * 获取实例
     * @param $page
     * @param $objectId
     * @return self
     */
    static private function getInstance ($page, $objectId = '')
    {
        //static $instance;
        $key = $page.'_'.$objectId;
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($page, $objectId);
        }

        return self::$instances[$key];
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    /**
     * 添加param参数
     * @param $param
     * @param $refererPage
     * @param $refererLocation
     * @param $logDp
     * @param $page
     * @param $objectId
     * @return array
     */
    static public function genParam (array $param, $refererPage, $refererLocation = '', $logDp = '', $page = '', $objectId = '')
    {
        $instance = self::getInstance($page,$objectId);
        $param[self::PARAM_KEY] = array(
            self::REFERER_PAGE_KEY => strval($refererPage),
            self::REFERER_LOCATION_KEY => strval($instance->genLocation($refererLocation)),
        );

        $param[self::PARAM_KEY][self::REFERER_SHARE_USER_KEY] = $instance->getUserID();
        $param[self::PARAM_KEY][self::REFERER_SHARE_USER_UNIQUE_KEY] = $instance->getShareId($page,$objectId);

        if (!empty($logDp)) {
            $param[self::PARAM_KEY][self::DP_KEY] = strval($logDp);
        }

        return $param;
    }

    /**
     * 生成分享链接
     * @param $url
     * @param $page
     * @param $objectId
     * @param $refererPage
     * @param string $refererLocation
     * @param string $logDp
     * @return string
     */
    public static function genShareUrl($url, $refererPage, $refererLocation = '', $logDp = '', $page, $objectId) {
        return self::genUrl($url,$refererPage,$refererLocation,$logDp,$page,$objectId);
    }

    /**
     * 添加url参数
     * @param $url
     * @param $refererPage
     * @param $refererLocation
     * @param $logDp
     * @param $page
     * @param $objectId
     * @return string
     */
    static public function genUrl ($url, $refererPage, $refererLocation = '', $logDp = '', $page = '', $objectId  = '')
    {
        if (empty($url)) {
            return $url;
        }
        $instance = self::getInstance($page, $objectId);
        if (Common::isCmd($url)) {
            $url = $instance->genAppCmd($url, $refererPage, $refererLocation, $logDp, $page, $objectId);
        } else {
            $url = $instance->genH5Url($url, $refererPage, $refererLocation, $logDp, $page, $objectId);
        }
        return $url;
    }

    /**
     * 生成app命令
     * @param $cmd
     * @param $refererPage
     * @param $refererLocation
     * @param $logDp
     * @param $page
     * @param $objectId
     * @return string
     */
    public function genAppCmd ($cmd, $refererPage, $refererLocation = '', $logDp = '', $page = '', $objectId = '')
    {
        $cmdData = explode('?', $cmd);
        $args = [];
        $argsPKey = 'params';
        if (empty($cmdData[1])) {
            $paramVal = [];
        } else {
            foreach (explode('&', $cmdData[1]) as $v) {
                $d = explode('=', $v);
                if (empty($d[1])) continue;
                $args[$d[0]] = $d[1];
            }
            $paramVal = isset($args[$argsPKey]) ? json_decode(urldecode($args[$argsPKey]), true) : [];
        }
        $paramVal[self::PARAM_KEY] = array(
            self::REFERER_PAGE_KEY => strval($refererPage),
            self::REFERER_LOCATION_KEY => strval($this->genLocation($refererLocation)),
        );
        if (!empty($logDp)) {
            $paramVal[self::PARAM_KEY][self::DP_KEY] = strval($logDp);
        }

        if (!empty($page) && !empty($objectId)) {
            if ($this->shareId !== '') {
                $paramVal[self::PARAM_KEY][self::REFERER_SHARE_USER_UNIQUE_KEY] = strval($this->shareId);
            }
            if ($this->viewUser !== '') {//当前查看人，是下一个分享人
                $paramVal[self::PARAM_KEY][self::REFERER_SHARE_USER_KEY] = strval($this->viewUser);
            }
        }

        $args[$argsPKey] = urlencode(json_encode($paramVal));
        $queryArr = [];
        foreach($args as $k => $v) {
            $queryArr[] = sprintf("%s=%s", $k, $v);
        }
        $newCmd = sprintf("%s?%s", $cmdData[0], implode('&', $queryArr));
        return $newCmd;
    }

    /**
     * 生成h5 url
     * @param $url
     * @param $refererPage
     * @param $refererLocation
     * @param $logDp
     * @param $page
     * @param $objectId
     * @return string
     */
    public function genH5Url ($url, $refererPage, $refererLocation, $logDp = '', $page = '', $objectId = '')
    {
        $url = rtrim($url, '?');
        $url = rtrim($url, '&');
        $location = $this->genLocation($refererLocation);
        $query = sprintf("%s=%s&%s=%s", self::REFERER_PAGE_KEY, $refererPage, self::REFERER_LOCATION_KEY, $location);
        $parsedUrl = parse_url($url);
        if (empty($parsedUrl['path'])) {
            $url .= '/';
        }
        $separator = empty($parsedUrl['query']) || strpos($url,'?') === false ? '?' : '&';
        $url .= $separator . $query;

        if (!empty($logDp)) {
            $url .= sprintf("&%s=%s", self::DP_KEY, strval($logDp));
        }

        if (!empty($page) && !empty($objectId)) {
            if ($this->shareId !== '') {
                $url .= sprintf("&%s=%s", self::REFERER_SHARE_USER_UNIQUE_KEY, strval($this->shareId));
            }

            if ($this->viewUser !== '') {//当前查看人，是下一个分享人
                $url .= sprintf("&%s=%s", self::REFERER_SHARE_USER_KEY, strval($this->viewUser));
            }
        }

        return $url;
    }

    /**
     * 生成location
     * @param $refererLocation
     * @return string
     */
    public function genLocation ($refererLocation)
    {
        $str = is_array($refererLocation) ? implode(self::REFERER_LOCATION_SEPARATOR, $refererLocation) : $refererLocation;
        return rawurlencode($str);
    }

    /**
     * 添加日志
     * @param $page
     * @param $detail
     */
    static public function addLog ($page, array $detail = array())
    {
        $object_id = isset($detail['object_id'])?$detail['object_id']:'';
        $instance = self::getInstance($page,$object_id);
        try {
            // 生成详情
            $instance->genDetail($detail);
            if (isset($detail['object_id'])) {
                // 生成分享
                $instance->genShare($page,$detail['object_id']);
            }
            // 生成内容
            $instance->genContent($page);
            // 写日志
            $instance->writeLog();
        } catch(\Exception $e) {
            $instance->errLog($e->getMessage());
        }
    }

    /**
     * 获取分享ID
     * @param $page
     * @param $objectId
     * @return mixed
     */
    private function getShareId($page, $objectId) {
        if (empty($this->shareId)) {
            $this->shareId =  md5($this->getSess().$page.$objectId);
        }
        return $this->shareId;
    }

    /**
     * 错误日志
     * @param $msg
     */
    private function errLog ($msg)
    {
        $content = sprintf("[Referer::addLog, fail][%s]", $msg);
        BxdLog::error($content);
    }

    /**
     * 写日志
     */
    private function writeLog ()
    {
        if (empty($this->file) || !file_put_contents($this->file, $this->content, FILE_APPEND | LOCK_EX)) {
            throw new \Exception('Invalid file : ' . $this->file);
        }
    }

    /**
     * 生成分享节点
     * @param $page
     * @param $objectId
     */
    private function genShare($page, $objectId) {
        //分享标识
        $share_id = $this->getShareId($page, $objectId);
        $this->share = [$share_id=>['sharer'=>$this->shareUser,'viewer'=>$this->viewUser,'object_id'=>$objectId]];
    }

    /**
     * 生成内容
     * @param $page
     */
    private function genContent ($page)
    {
        $contentArr = array(
            'session' => $this->sess,
            'request_time' => date('Y-m-d H:i:s'),
            'page' => $page,
            'page_detail' => $this->detail,
            'refer_page' => $this->refererPage,
            'refer_location' => $this->refererLocation,
            'share' => $this->share
        );
        $this->content = json_encode($contentArr) . "\n";
    }

    /**
     * 生成详情
     * @param $detail
     */
    private function genDetail ($detail)
    {
        $this->detail = $detail;
        $this->detail['dp'] = $this->dp;
    }

    /**
     * 获取日志文件
     */
    private function getFile ()
    {
        $date = date('Ymd');
        $fmt = env('REFERER.FILE_FMT');
        $file = sprintf($fmt, $date);
        return $file;
    }

    /**
     * 初始化参数
     */
    private function initParam ()
    {
        $this->refererPage = isset($_REQUEST[self::REFERER_PAGE_KEY]) ? trim($_REQUEST[self::REFERER_PAGE_KEY]) : '';
        $this->refererLocation = isset($_REQUEST[self::REFERER_LOCATION_KEY]) ? urldecode($_REQUEST[self::REFERER_LOCATION_KEY]) : '';
        $this->dp = isset($_REQUEST[self::DP_KEY]) ? trim($_REQUEST[self::DP_KEY]) : '';

        //分享数据
        $this->shareId = isset($_REQUEST[self::REFERER_SHARE_USER_UNIQUE_KEY])?$_REQUEST[self::REFERER_SHARE_USER_UNIQUE_KEY]:$this->getShareId($this->page, $this->objectId);
        $this->shareUser = isset($_REQUEST[self::REFERER_SHARE_USER_KEY])?trim($_REQUEST[self::REFERER_SHARE_USER_KEY]):-1;
        $this->viewUser = $this->getUserID();
    }

    /**
     * 获取sess
     * @return string
     */
    private function getSess ()
    {
        return SESS_KEY;
    }

    /**
     * 获取当前用户ID
     * @return int
     */
    private function getUserID() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    }
}
