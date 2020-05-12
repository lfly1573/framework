<?php

/**
 * 缓存类
 */

namespace lfly;

use InvalidArgumentException;
use ReflectionFunction;
use Closure;

class Cache
{
    /**
     * 配置参数
     * @var array
     */
    protected $config;

    /**
     * 驱动
     * @var array
     */
    protected $driver = [];

    /**
     * 当前驱动
     * @var CacheHandlerInterface
     */
    protected $curDriver;

    /**
     * 当前引擎名称
     * @var string
     */
    protected $curEngine;

    /**
     * @var App
     */
    protected $app;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->config = $this->app->config->get('cache');
        if (!empty($this->config['default'])) {
            $this->engine($this->config['default']);
        }
    }

    /**
     * Facade初始化函数
     */
    public function facade()
    {
        if ($this->curEngine != $this->config['default']) {
            $this->engine($this->config['default']);
        }
    }

    /**
     * 获取和设置引擎
     * @param string $name 缓存类型
     * @return $this
     * 
     * @throws InvalidArgumentException
     */
    public function engine($name = null)
    {
        if (is_null($name)) {
            $name = $this->config['default'];
        }
        $this->curEngine = $name;
        if (isset($this->driver[$name])) {
            $this->curDriver = $this->driver[$name];
            return $this;
        }
        if (empty($this->config['engine'][$name])) {
            throw new InvalidArgumentException('cache engine error: ' . $name);
        }
        $class = (false !== strpos($this->config['engine'][$name]['type'], '\\')) ? $class : __NAMESPACE__ . '\\cache\\' . ucfirst(strtolower($this->config['engine'][$name]['type']));
        $this->driver[$name] = $this->app->invokeClass($class);
        $this->driver[$name]->init($this->config['engine'][$name]);
        $this->curDriver = $this->driver[$name];
        return $this;
    }

    /**
     * 获取当前引擎类型
     */
    public function type()
    {
        return $this->config['engine'][$this->curEngine]['type'];
    }

    /**
     * 获取对象句柄
     */
    public function handler()
    {
        return $this->curDriver->handler();
    }

    /**
     * 是否存在
     * @param  string $key 名称
     * @return bool
     */
    public function has($key)
    {
        return $this->curDriver->has($key);
    }

    /**
     * 取值并删除
     * @param  string  $key     名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        if (!is_null($value)) {
            $this->delete($key);
        }
        return $value;
    }

    /**
     * 追加一个缓存数据
     * @param  string $key   名称
     * @param  mixed  $value 值
     * @return bool
     */
    public function push($key, $value)
    {
        $item = $this->get($key, []);
        if (!is_array($item)) {
            return false;
        }
        $item[] = $value;
        if (count($item) > 1000) {
            array_shift($item);
        }
        $item = array_unique($item);
        $this->set($key, $item);
        return true;
    }

    /**
     * 不存在写入返回
     * @param  string $key   名称
     * @param  mixed  $value 值
     * @return mixed
     */
    public function remember($key, $value, $ttl = null)
    {
        if ($this->has($key)) {
            return $this->get($key);
        } else {
            $this->set($key, $value, $ttl);
            return $value;
        }
    }

    /**
     * 读取
     * @param  string  $key     名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $value = $this->curDriver->get($key);
        if (is_null($value)) {
            $value = $default;
        }
        return $value;
    }

    /**
     * 写入
     * @param string       $key   名称
     * @param mixed        $value 存储数据
     * @param int|DateTime $ttl   有效时间 0为永久
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->curDriver->set($key, $value, $ttl);
    }

    /**
     * 自增缓存（针对数值缓存）
     * @param  string    $key  名称
     * @param  int       $step 步长
     * @return false|int
     */
    public function inc($key, $step = 1)
    {
        return $this->curDriver->inc($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @param  string    $key  名称
     * @param  int       $step 步长
     * @return false|int
     */
    public function dec($key, $step = 1)
    {
        return $this->curDriver->dec($key, $step);
    }

    /**
     * 删除
     * @param  string $key 名称
     * @return bool
     */
    public function delete($key)
    {
        return $this->curDriver->delete($key);
    }

    /**
     * 清空
     * @return void
     */
    public function clear()
    {
        $this->curDriver->clear();
    }

    /**
     * 动态调用
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->curDriver, $method], $args);
    }

    /**
     * 写入缓存文件
     * @param string $file      缓存文件
     * @param mixed  $cacheData 缓存数据
     * @param bool   $append    是否在原文件末尾追加内容
     * @return bool
     */
    public static function writeFile($file, $cacheData, bool $append = false)
    {
        if ($file != '') {
            $bolPhpCode = ('.' . pathinfo($file, PATHINFO_EXTENSION) == EXT);
            if ($bolPhpCode && is_array($cacheData)) {
                $cacheData = 'return ' . self::getCacheVarsFormat($cacheData) . ';';
            }
            $openMode = ($append) ? 'ab' : 'wb';
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }
            if (@$fp = fopen($file, $openMode)) {
                flock($fp, LOCK_EX);
                if ($bolPhpCode) {
                    fwrite($fp, "<?php" . PHP_EOL . "!defined('LFLY_VERSION') and exit();" . PHP_EOL . $cacheData . PHP_EOL);
                } else {
                    fwrite($fp, $cacheData);
                }
                flock($fp, LOCK_UN);
                fclose($fp);
                @chmod($file, 0777);
                return true;
            }
        }
        return false;
    }

    /**
     * php数组格式化
     */
    public static function getCacheVarsFormat($array, $level = 0)
    {
        $space = '';
        for ($i = 0; $i <= $level; $i++) {
            $space .= "\t";
        }
        $evaluate = '[' . PHP_EOL;
        $comma = $space;
        foreach ($array as $key => $val) {
            $key = is_string($key) ? '\'' . addcslashes($key, '\'') . '\'' : $key;
            if (is_array($val)) {
                $evaluate .= "{$comma}{$key} => " . self::getCacheVarsFormat($val, $level + 1);
            } else {
                if (is_string($val)) {
                    $val = '\'' . addcslashes($val, '\'') . '\'';
                } elseif (is_bool($val)) {
                    $val = $val ? 1 : 0;
                } elseif (is_null($val)) {
                    $val = '\'\'';
                } elseif (is_scalar($val)) {
                    if (!is_numeric($val)) {
                        $val = '\'' . addcslashes(strval($val), '\'') . '\'';
                    }
                } elseif ($val instanceof Closure) {
                    $val = self::getCacheClosureFormat($val);
                } else {
                    $val = '\'\'';
                }
                $evaluate .= "{$comma}{$key} => $val";
            }
            $comma = ',' . PHP_EOL . $space;
        }
        $evaluate .= PHP_EOL . $space . ']';
        return $evaluate;
    }

    /**
     * php闭包格式化
     */
    public static function getCacheClosureFormat($func)
    {
        $reflect = new ReflectionFunction($func);
        $start = $reflect->getStartLine() - 1;
        $end = $reflect->getEndLine() - 1;
        $filename = $reflect->getFileName();
        $code = implode('', array_slice(file($filename), $start, $end - $start + 1));
        if (preg_match('/^.*?(function\s*\(.+\}).*$/is', $code, $match)) {
            return $match[1];
        }
        return '\'\'';
    }
}
