<?php

/**
 * redis缓存
 */

namespace lfly\cache;

use lfly\contract\CacheHandlerInterface;

class Redis implements CacheHandlerInterface
{
    /**
     * 原始对象句柄
     */
    protected $handler;

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => 0,
        'timeout' => 0,
        'expire' => 86400,
        'persistent' => false,
        'prefix' => '',
        'serialize' => [],
    ];

    /**
     * 初始化
     * @param  array $config 配置
     * @return void
     */
    public function init($config)
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        if (!extension_loaded('redis')) {
            throw new \LogicException('extension not exists: redis');
        }
        $this->handler = new \Redis;

        if ($this->config['persistent']) {
            $this->handler->pconnect($this->config['host'], (int)$this->config['port'], (int)$this->config['timeout']);
        } else {
            $this->handler->connect($this->config['host'], (int)$this->config['port'], (int)$this->config['timeout']);
        }

        if ('' != $this->config['password']) {
            $this->handler->auth($this->config['password']);
        }

        if (0 != $this->config['database']) {
            $this->handler->select($this->config['database']);
        }
    }

    /**
     * 获取对象句柄
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * 是否存在
     * @param  string $key 名称
     * @return bool
     */
    public function has($key)
    {
        return $this->handler->exists($this->getCacheKey($key)) ? true : false;
    }

    /**
     * 获取单个
     * @param  string  $key 名称
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->handler->get($this->getCacheKey($key));
        if (false === $value || is_null($value)) {
            return null;
        }
        return $this->unserialize($value);
    }

    /**
     * 设置
     * @param  string       $key   名称
     * @param  mixed        $value 内容
     * @param int|\DateTime $ttl   有效时间 0为永久
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $expire = is_null($ttl) ? $this->config['expire'] : $ttl;
        $key = $this->getCacheKey($key);
        $value = $this->serialize($value);
        $expire = $this->getExpireTime($expire);

        if ($expire) {
            $this->handler->setex($key, $expire, $value);
        } else {
            $this->handler->set($key, $value);
        }
        return true;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @param  string    $key  名称
     * @param  int       $step 步长
     * @return false|int
     */
    public function inc($key, $step = 1)
    {
        return $this->handler->incrby($this->getCacheKey($key), $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @param  string    $key  名称
     * @param  int       $step 步长
     * @return false|int
     */
    public function dec($key, $step = 1)
    {
        return $this->handler->decrby($this->getCacheKey($key), $step);
    }

    /**
     * 删除
     * @param  string $key 名称
     * @return bool
     */
    public function delete($key)
    {
        $result = $this->handler->del($this->getCacheKey($key));
        return $result > 0;
    }

    /**
     * 清空
     * @return void
     */
    public function clear()
    {
        $this->handler->flushDB();
    }

    /**
     * 获取实际的缓存标识
     * @param string $key 名称
     * @return string
     */
    public function getCacheKey($key)
    {
        return $this->config['prefix'] . $key;
    }

    /**
     * 获取有效期
     * @param int|DateTimeInterface $expire 有效期
     * @return int
     */
    protected function getExpireTime($expire)
    {
        if ($expire instanceof \DateTimeInterface) {
            $expire = $expire->getTimestamp() - time();
        }
        return (int)$expire;
    }

    /**
     * 序列化数据
     * @param mixed $data 缓存数据
     * @return string
     */
    protected function serialize($data)
    {
        if (is_numeric($data)) {
            return (string)$data;
        }
        $serialize = $this->config['serialize'][0] ?? "serialize";
        if (is_array($serialize)) {
            $args = [$data];
            if (is_array($serialize[1])) {
                $args = array_merge($args, $serialize[1]);
            } else {
                $args[] = $serialize[1];
            }
            return call_user_func_array($serialize[0], $args);
        } else {
            return $serialize($data);
        }
    }

    /**
     * 反序列化数据
     * @param string $data 缓存数据
     * @return mixed
     */
    protected function unserialize($data)
    {
        if (is_numeric($data)) {
            return $data;
        }
        $unserialize = $this->config['serialize'][1] ?? "unserialize";
        if (is_array($unserialize)) {
            $args = [$data];
            if (is_array($unserialize[1])) {
                $args = array_merge($args, $unserialize[1]);
            } else {
                $args[] = $unserialize[1];
            }
            return call_user_func_array($unserialize[0], $args);
        } else {
            return $unserialize($data);
        }
    }

    /**
     * 动态调用
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->handler, $method], $args);
    }
}
