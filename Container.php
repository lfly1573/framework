<?php

/**
 * 容器基础类
 */

namespace lfly;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use LogicException;
use InvalidArgumentException;
use IteratorAggregate;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class Container implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * 当前容器对象静态实例
     * @var Container|Closure
     */
    protected static $instance;

    /**
     * 容器中的对象实例
     * @var array
     */
    protected $instances = [];

    /**
     * 容器类名绑定
     * @var array
     */
    protected $bind = [];

    /**
     * 容器实例化后回调
     * @var array
     */
    protected $invokeCallback = [];

    /**
     * 静态获取当前容器的实例
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static; //后期静态绑定
        }

        if (static::$instance instanceof Closure) {
            return (static::$instance)();
        }

        return static::$instance;
    }

    /**
     * 静态设置当前容器的实例
     * @param object|Closure $instance 类或匿名函数
     * @return void
     */
    public static function setInstance($instance)
    {
        static::$instance = $instance;
    }

    /**
     * 静态获取容器中的对象实例
     * @param string     $abstract    类名或者标识
     * @param array|true $vars        变量
     * @param bool       $newInstance 是否每次创建新的实例
     * @return object
     */
    public static function pull($abstract, $vars = [], $newInstance = false)
    {
        return static::getInstance()->make($abstract, $vars, $newInstance);
    }

    /**
     * 根据标识获取真实类名
     * @param  string $abstract  类名或者标识
     * @param  bool   $recursion 是否递归查询
     * @return string
     */
    public function getAlias($abstract, $recursion = true)
    {
        if (isset($this->bind[$abstract])) {
            $bind = $this->bind[$abstract];
            if ($recursion && is_string($bind) && $bind != $abstract) {
                return $this->getAlias($bind);
            }
        }
        return $abstract;
    }

    /**
     * 绑定一个类、闭包、实例、接口实现到容器
     * @param string|array $abstract 类标识、接口
     * @param mixed        $concrete 要绑定的类、闭包或者实例
     * @return $this
     */
    public function bind($abstract, $concrete = null)
    {
        if (is_array($abstract)) {
            foreach ($abstract as $key => $value) {
                $this->bind($key, $value);
            }
        } elseif ($concrete instanceof Closure) {
            $this->bind[$abstract] = $concrete;
        } elseif (is_object($concrete)) {
            $this->instance($abstract, $concrete);
        } elseif (!is_null($concrete) && $abstract != $concrete) {
            $this->bind[$abstract] = $concrete;
        }
        return $this;
    }

    /**
     * 创建类的实例
     * @param string $abstract    类名或者标识
     * @param array  $vars        变量
     * @param bool   $newInstance 是否每次创建新的实例
     * @return mixed
     */
    public function make($abstract, $vars = [], $newInstance = false)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract]) && !$newInstance) {
            return $this->instances[$abstract];
        }

        if (isset($this->bind[$abstract]) && $this->bind[$abstract] instanceof Closure) {
            $object = $this->invokeFunction($this->bind[$abstract], $vars);
        } else {
            $object = $this->invokeClass($abstract, $vars);
        }

        if (!$newInstance) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * 绑定一个类实例到容器
     * @param string $abstract 类名或者标识
     * @param object $instance 类的实例
     * @return $this
     */
    public function instance($abstract, $instance)
    {
        $abstract = $this->getAlias($abstract);

        $this->instances[$abstract] = $instance;

        return $this;
    }

    /**
     * 判断容器中是否存在类及标识
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->bind[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * 判断容器中是否存在类及标识 PSR-11(精简版未继承ContainerInterface)
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public function has($abstract)
    {
        return $this->bound($abstract);
    }

    /**
     * 获取容器中的对象实例 PSR-11(精简版未继承ContainerInterface)
     * @param string $abstract 类名或者标识
     * @return object
     * 
     * @throws LogicException
     */
    public function get($abstract)
    {
        if ($this->has($abstract)) {
            return $this->make($abstract);
        }
        throw new LogicException('class not exists: ' . $abstract);
    }

    /**
     * 判断容器中是否存在对象实例
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public function exists($abstract)
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->instances[$abstract]);
    }

    /**
     * 删除容器中的对象实例
     * @param string $abstract 类名或者标识
     * @return void
     */
    public function delete($abstract)
    {
        $abstract = $this->getAlias($abstract);
        if (isset($this->instances[$abstract])) {
            unset($this->instances[$abstract]);
        }
    }

    /**
     * 调用反射执行函数或者闭包方法
     * @param string|Closure $function 函数或者闭包
     * @param array          $vars     参数
     * @return mixed
     * 
     * @throws LogicException
     */
    public function invokeFunction($function, $vars = [])
    {
        try {
            $reflect = new ReflectionFunction($function);
        } catch (ReflectionException $e) {
            throw new LogicException('function not exists: ' . $function . '()');
        }

        $args = $this->bindParams($reflect, $vars);

        return $function(...$args);
    }

    /**
     * 调用反射执行类的实例化
     * @param string $class 类名
     * @param array  $vars  参数
     * @return mixed
     * 
     * @throws LogicException
     */
    public function invokeClass($class, $vars = [])
    {
        try {
            $reflect = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new LogicException('class not exists: ' . $class);
        }

        //自定义实例化"public static function __make()"
        if ($reflect->hasMethod('__make')) {
            $method = $reflect->getMethod('__make');
            if ($method->isPublic() && $method->isStatic()) {
                $args = $this->bindParams($method, $vars);
                return $method->invokeArgs(null, $args);
            }
        }

        $constructor = $reflect->getConstructor();
        $args = $constructor ? $this->bindParams($constructor, $vars) : [];
        $object = $reflect->newInstanceArgs($args);

        $this->invokeAfter($class, $object);

        return $object;
    }

    /**
     * 调用反射执行函数或者方法
     * @param mixed $callable   函数或者方法
     * @param array $vars       参数
     * @param bool  $accessible 设置是否可访问
     * @return mixed
     */
    public function invoke($callable, $vars = [], $accessible = false)
    {
        if ($callable instanceof Closure || (is_string($callable) && false === strpos($callable, '::'))) {
            return $this->invokeFunction($callable, $vars);
        } else {
            return $this->invokeMethod($callable, $vars, $accessible);
        }
    }

    /**
     * 调用反射执行类的方法
     * @param mixed $method     方法(数组或者字符)
     * @param array $vars       参数
     * @param bool  $accessible 设置是否可访问
     * @return mixed
     * 
     * @throws LogicException
     */
    public function invokeMethod($method, $vars = [], $accessible = false)
    {
        if (is_array($method)) {
            [$class, $method] = $method;
            $class = is_object($class) ? $class : $this->invokeClass($class);
        } else {
            // 静态方法
            [$class, $method] = explode('::', $method);
        }

        try {
            $reflect = new ReflectionMethod($class, $method);
        } catch (ReflectionException $e) {
            $class = is_object($class) ? get_class($class) : $class;
            throw new LogicException('method not exists: ' . $class . '::' . $method . '()');
        }

        $args = $this->bindParams($reflect, $vars);
        if ($accessible) {
            $reflect->setAccessible($accessible);
        }
        return $reflect->invokeArgs(is_object($class) ? $class : null, $args);
    }

    /**
     * 调用反射执行类的方法(直接传入ReflectionMethod类)
     * @param object            $instance 对象实例
     * @param ReflectionMethod  $reflect  反射类
     * @param array             $vars     参数
     * @return mixed
     */
    public function invokeReflectMethod($instance, ReflectionMethod $reflect, array $vars = [])
    {
        $args = $this->bindParams($reflect, $vars);
        return $reflect->invokeArgs($instance, $args);
    }

    /**
     * 注册一个容器对象实例化后回调函数
     * @param string|Closure $abstract 类名或者标识或者匿名函数
     * @param Closure|null   $callback 绑定到指定类的回调匿名函数 参数(当前对象,当前容器)
     * @return void
     */
    public function resolving($abstract, Closure $callback = null)
    {
        if ($abstract instanceof Closure) {
            $this->invokeCallback['*'][] = $abstract;
            return;
        }

        $abstract = $this->getAlias($abstract);
        $this->invokeCallback[$abstract][] = $callback;
    }

    /**
     * 执行对象实例化后回调
     * @param string $class  对象类名
     * @param object $object 容器对象实例
     * @return void
     */
    protected function invokeAfter($class, $object)
    {
        if (isset($this->invokeCallback['*'])) {
            foreach ($this->invokeCallback['*'] as $callback) {
                $callback($object, $this);
            }
        }

        if (isset($this->invokeCallback[$class])) {
            foreach ($this->invokeCallback[$class] as $callback) {
                $callback($object, $this);
            }
        }
    }

    /**
     * 绑定参数
     * @param ReflectionFunctionAbstract $reflect 反射类
     * @param array                      $vars    参数
     * @return array
     */
    protected function bindParams(ReflectionFunctionAbstract $reflect, $vars = [])
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }

        // 判断数组类型 数字数组时按顺序绑定参数
        reset($vars);
        $type = key($vars) === 0 ? 1 : 0;   //是否数字数组
        $params = $reflect->getParameters();
        $args = [];

        foreach ($params as $param) {
            $name = $param->getName();
            $class = $param->getClass();

            if ($class) {
                //如果是类，判断是否是同一类别的类实例化，不是则无参数自动注入
                $args[] = $this->getObjectParam($class->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && isset($vars[$name])) {
                $args[] = $vars[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException('method param miss:' . $name);
            }
        }

        return $args;
    }

    /**
     * 获取对象类型的参数值
     * @param string $className 类名
     * @param array  $vars      参数(地址传入)
     * @return mixed
     */
    protected function getObjectParam($className, &$vars)
    {
        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className) {
            //同一类型赋值
            $result = $value;
            array_shift($vars);
        } else {
            //自动注入
            $result = $this->make($className);
        }

        return $result;
    }

    //obj->key = value
    public function __set($key, $value)
    {
        $this->bind($key, $value);
    }

    //obj->key 不存在时
    public function __get($key)
    {
        return $this->get($key);
    }

    //isset(obj->key)
    public function __isset($key)
    {
        return $this->exists($key);
    }

    //unset(obj->key)
    public function __unset($key)
    {
        $this->delete($key);
    }

    /**
     * ArrayAccess
     */

    //isset(obj[key])
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    //obj[key] 不存在时
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    //obj[key] = value
    public function offsetSet($key, $value)
    {
        $this->bind($key, $value);
    }

    //unset(obj[key])
    public function offsetUnset($key)
    {
        $this->delete($key);
    }

    /**
     * Countable
     */

    //count(obj)
    public function count()
    {
        return count($this->instances);
    }

    /**
     * IteratorAggregate
     */

    //foreach(obj as $key => $value)
    public function getIterator()
    {
        return new ArrayIterator($this->instances);
    }
}
