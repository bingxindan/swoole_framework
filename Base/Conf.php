<?php
namespace BxdFramework\Base;

/**
 * 加载/读取配置信息
 * @see http://localhost/pages/viewpage.action?pageId=18553373
 */
class Conf
{
    // 配置数据
    public static $data = [];
    // 修改时间
    public static $mtime = [];
    // 忽略目录
    const DIR_IGNORE = ['..', '.'];
    // 资源角色键
    const ROLE_KEY = 'role';
    // 资源角色忽略键
    const ROLE_IGNORE = ['roles'];
    // 资源角色索引分割符
    const ROLE_INDEX_SEPARATOR = ',';
    // 配置键分割符
    const CONF_INDEX_SEPARATOR = '.';

    /**
     * 获取配置信息
     * @param $key string 键 (支持最多4层 如: mysql.trade.master.0 资源.组.角色.实例)
     * @return array/string
     */
    public static function get(string $key)
    {
        if (empty($key)) {
            throw new \Exception("Conf get fail, invalid key : $key", Code::CONF_KEY_INVALID);
        }
        $info = null;
        $keyList = explode(self::CONF_INDEX_SEPARATOR, $key);
        switch (count($keyList)) {
            case 4:
                // 资源/组/角色/实例
                $ki = $keyList[0];
                $kj = $keyList[1];
                $kk = $keyList[2];
                $kl = $keyList[3];
                $info = self::$data[$ki][$kj][$kk][$kl] ?? null;
                break;
            case 3:
                // 资源/组/角色
                $ki = $keyList[0];
                $kj = $keyList[1];
                $kk = $keyList[2];
                $info = self::$data[$ki][$kj][$kk] ?? null;
                break;
            case 2:
                // 资源/组
                $ki = $keyList[0];
                $kj = $keyList[1];
                $info = self::$data[$ki][$kj] ?? null;
                break;
            case 1:
                // 资源
                $ki = $keyList[0];
                $info = self::$data[$ki] ?? null;
                break;
            default:
                throw new \Exception("Get conf fail, invalid key : $key", Code::CONF_KEY_INVALID);
                break;
        }
        return $info;
    }

    /**
     * 获取变更时间
     * @param $dir string 配置目录
     * @return void
     */
    public static function getMtime(string $dir, array $loadItems = [])
    {
        if (!is_dir($dir)) {
            throw new \Exception("Init fail, invalid config dir", Code::INI_DIR_INVALID);
        }
        // 获取资源目录
        $items = array_diff(scandir($dir), self::DIR_IGNORE);
        if (empty($items)) {
            throw new \Exception("Init fail, invalid config dir no children", Code::INI_DIR_INVALID);
        }
        $items = empty($loadItems) ? $items : array_intersect($items, $loadItems);
        $data = [];
        foreach ($items as $item) {
            // 获取资源环境
            $iDir = $dir . DIRECTORY_SEPARATOR . $item;
            // 设置修改时间
            $data[$item] = filemtime($iDir);
        }
        return $data;
    }

    /**
     * 加载配置文件
     * @param $dir string 配置目录
     * @return void
     */
    public static function load(string $dir, array $loadItems = [])
    {
        if (!is_dir($dir)) {
            throw new \Exception("Init fail, invalid config dir", Code::INI_DIR_INVALID);
        }
        // 获取资源目录
        $items = array_diff(scandir($dir), self::DIR_IGNORE);
        if (empty($items)) {
            throw new \Exception("Init fail, invalid config dir no children", Code::INI_DIR_INVALID);
        }
        $items = empty($loadItems) ? $items : array_intersect($items, $loadItems);
        foreach ($items as $item) {
            // 获取资源环境
            $iDir = $dir . DIRECTORY_SEPARATOR . $item;
            // 设置修改时间
            self::setMtime($item, filemtime($iDir));
            $envs = array_diff(scandir($iDir), self::DIR_IGNORE);
            // 是否包含当前环境
            if (!in_array(PROJECT_ENV, $envs)) continue;
            // 获取资源环境实例文件
            $iDirEnv = $iDir . DIRECTORY_SEPARATOR . PROJECT_ENV;
            $files = array_diff(scandir($iDirEnv), self::DIR_IGNORE);
            foreach ($files as $fName) {
                // 组
                $pathInfo = pathinfo($fName);
                $group = $pathInfo['filename'] ?? '';
                if (empty($group)) continue;
                // 解析ini文件
                $filePath = $iDirEnv . DIRECTORY_SEPARATOR . $fName;
                $iniArr = parse_ini_file($filePath, true);
                $roles = $iniArr[self::ROLE_KEY] ?? [];
                // 获取role
                foreach ($roles as $role => $index) {
                    if (in_array($role, self::ROLE_IGNORE)) continue;
                    $indexList = explode(self::ROLE_INDEX_SEPARATOR, $index);
                    $indexVals = [];
                    foreach ($indexList as $idx) {
                        $idxVal = $iniArr[$idx] ?? null;
                        if (!$idxVal) continue;
                        $indexVals[] = $idxVal;
                    }
                    if (empty($indexVals)) continue;
                    $setKey = $item . self::CONF_INDEX_SEPARATOR . $group . self::CONF_INDEX_SEPARATOR . $role;
                    $setVal = $indexVals;
                    self::set($setKey, $setVal);
                }
            }
        }
    }

    /**
     * 设置变更时间
     */
    public static function setMtime($item, $mtime)
    {
        self::$mtime[$item] = $mtime;
    }

    /**
     * 设置配置信息
     * @param $key string       键(支持 a.b.c 模式)
     * @param $val string/array 值
     * @return void
     */
    public static function set(string $key, $val)
    {
        $keyList = explode(self::CONF_INDEX_SEPARATOR, $key);
        $ki = $kj = $kk = null;
        switch (count($keyList)) {
            case 3:
                // 资源/组/角色
                $ki = $keyList[0];
                $kj = $keyList[1];
                $kk = $keyList[2];
                self::$data[$ki] = self::$data[$ki] ?? [];
                self::$data[$ki][$kj] = self::$data[$ki][$kj] ?? [];
                self::$data[$ki][$kj][$kk] = $val;
                break;
            case 2:
                // 资源/组
                $ki = $keyList[0];
                $kj = $keyList[1];
                self::$data[$ki] = self::$data[$ki] ?? [];
                self::$data[$ki][$kj] = $val;
                break;
            case 1:
                // 资源
                $ki = $keyList[0];
                self::$data[$ki] = $val;
            default:
                throw new \Exception("Conf set fail, invalid key : $key", Code::CONF_KEY_INVALID);
                break;
        }
    }
}
