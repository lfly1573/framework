<?php

/**
 * 门面类
 */

namespace lfly;

use ReflectionMethod;

class Facade
{
    /**
     * 始终创建新的对象实例
     * @var bool
     */
    protected static $alwaysNewInstance = false;

    /**
     * 以下方法不用新建实例
     * @var array
     */
    protected static $specialMethod = [];

    /**
     * 新建实例传入参数
     * @var array
     */
    protected static $newArgs = [];

    /**
     * 每次获取实例默认执行初始化方法
     * @var string
     */
    protected static $init;

    /**
     * 创建Facade实例
     * @param  string $class       类名或标识
     * @param  array  $args        变量
     * @param  bool   $newInstance 是否每次创建新的实例
     * @return object
     */
    protected static function createFacade(string $class = '', array $args = [], bool $newInstance = false)
    {
        if (empty($class)) {
            $facadeClass = static::getFacadeClass();
            $class = !empty($facadeClass) ? $facadeClass : (static::class);
        }
        $object = Container::getInstance()->make($class, $args, $newInstance);
        if (!empty(static::$init)) {
            $init = static::$init;
            $object->$init();
        }
        return $object;
    }

    /**
     * 获取当前Facade对应类名
     * @return string
     */
    protected static function getFacadeClass()
    {
    }

    /**
     * 带参数实例化当前Facade类
     * @return object
     */
    public static function instance(...$args)
    {
        //不是父类才执行
        if (__class__ != static::class) {
            return self::createFacade('', $args);
        }
    }

    /**
     * 调用类的实例
     * @param  string     $class       类名或者标识
     * @param  array|true $args        变量
     * @param  bool       $newInstance 是否每次创建新的实例
     * @return object
     */
    public static function make(string $class, $args = [], $newInstance = false)
    {
        if (__class__ != static::class) {
            return self::__callStatic('make', func_get_args());
        }

        //没有参数且总是创建新的实例化对象快捷参数
        if (true === $args) {
            $newInstance = true;
            $args = [];
        }

        return self::createFacade($class, $args, $newInstance);
    }

    // 调用实际类的方法
    public static function __callStatic($method, $params)
    {
        $args = [];
        $newInstance = false;
        if (static::$alwaysNewInstance && !in_array($method, static::$specialMethod)) {
            $args = static::$newArgs;
            $newInstance = true;
        }

        $facadeClass = static::getFacadeClass();
        if (!empty($facadeClass)) {
            $curClass = Container::getInstance()->getAlias($facadeClass);
            if (method_exists($curClass, $method)) {
                $curMethod = new ReflectionMethod($curClass, $method);
                if ($curMethod->isStatic()) {
                    return $curMethod->invokeArgs(null, $params);
                }
            }
        }

        return call_user_func_array([static::createFacade($facadeClass, $args, $newInstance), $method], $params);
    }
}
