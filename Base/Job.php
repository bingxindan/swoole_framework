<?php
namespace BxdFramework\Base;

/**
 * Description of Job
 *
 * @author zhangming <zhangming@bingxindan.com>
 */
class Job
{
    private static $storage = array();

    public static function register($type, callable $callback)
    {
        if (!is_callable($callback)) {
            return;
        }
        self::$storage[$type] = $callback;
    }

    public static function fire(...$args)
    {
        Watcher::getInstance()->fire('job', $args);
    }

    public static function execute($methods, ...$args)
    {
        $funs = explode('/', $methods);
        if (empty($funs)) {
            die("method error [$methods]");
        }

        $action = array_pop($funs);

        if (method_exists(get_called_class(), $action)) {
            call_user_func_array(static::$action, $args);
            return;
        }
        if (isset(self::$storage[$action])) {
            call_user_func_array(self::$storage[$action], $args);
            return;
        }

        $method = implode('\\', $funs);
        $classname = 'BxdFramework\Jobs\\' . ucwords($method, '\\') . 'Job';
        if (class_exists($classname)) {
            if (method_exists($classname, $action)) {
                self::$storage[$action] = [$classname, $action];
                call_user_func_array([$classname, $action], $args);
                return;
            }
        }
    }
}
