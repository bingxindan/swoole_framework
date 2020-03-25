<?php
namespace BxdFramework\Base;

use BxdFramework\Base\Request;
use BxdFramework\Base\Conf;

/**
 * 初始化加载配置文件
 *
 * @author zhangming <zhangming@bingxindan.com>
 */
class Bootstrap
{
    public static $ini = [];         // 加载配置

    /**
     * master启动前需要执行的事情
     * env环境标识
     * 运维推的资源配置信息
     * swoole配置
     * @param $documentRoot 项目根目录
     * @param string $name 项目名
     * @author zm
     */
    public static function startUp($documentRoot, $name = '')
    {
        // 指定环境
        if (!file_exists($documentRoot . '.envkey')) {
            throw new \Exception("envkey null\n", Code::INI_DIR_INVALID);
        }
        define('PROJECT_ENV', trim(file_get_contents($documentRoot . '.envkey')));

        // 加载环境配置文件目录
        define('PROJECT_CONFIG_PATH', $documentRoot . 'Conf' . DIRECTORY_SEPARATOR);

        // 加载配置文件
        self::$ini = parse_ini_file(PROJECT_CONFIG_PATH . PROJECT_ENV . DIRECTORY_SEPARATOR . 'conf.ini', true);
        if (empty(self::$ini['init']['dir'])) {
            throw new \Exception("Init fail, empty config dir\n", Code::INI_DIR_NONE);
        }
        Conf::load(self::$ini['init']['dir'], explode(',', self::$ini['swoole']['masterConf']));

        // 全局配置写入
        foreach (self::$ini['globals'] as $globalK => $globalV) {
            Request::globals($globalK, $globalV);
        }

        // 定义项目名称
        define('PROJECT_NAME', ($name == '' ? self::$ini['init']['projectName'] : $name));

        // 定义命名空间根目录，与composer.json里的"BxdFramework\\Base\\" 一致
        define('PROJECT_ROOT_NAME', 'BxdFramework');

        // 定义controller目录
        define('PROJECT_CONTROLLER_DIR', $documentRoot . 'Controllers' . DIRECTORY_SEPARATOR);

        // 定义脚本目录
        define('PROJECT_JOBS_DIR', $documentRoot . 'Jobs' . DIRECTORY_SEPARATOR);
        
        // 得到环境号 dev/beta/gray 0-29 
        define('PROJECT_ENV_FLAG', self::getEnvFlag());
    }

    /**
     * 获取环境配置号
     * @return int
     * @author zm
     */
    public static function getEnvFlag()
    {
        // 只有dev/beta/gray需要这个
        if (!in_array(PROJECT_ENV, ['dev', 'beta', 'gray', 'prod'])) {
            return '';
        }
        
        $flag = 0;
        $files = explode('/', __FILE__);
        foreach ($files as $file) {
            if(strpos($file, 'work') === 0){
                $flag = substr($file, -1) == 'k' ? 0 : explode('k', $file)[1];
            }
        }
        
        return $flag;
    }
}
