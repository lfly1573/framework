<?php

/**
 * session缓存驱动
 */

namespace lfly\session;

use lfly\App;
use lfly\contract\SessionHandlerInterface;

class Cache implements SessionHandlerInterface
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        //指定会话名以用做cookie的名字
        'name' => 'FCSESSID',
        //以秒数指定了发送到浏览器的cookie的生命周期
        'cookie_lifetime' => 0,
        //数据的有效期秒数
        'gc_maxlifetime' => 1440,
        //缓存字段前缀
        'prefix' => 'sess_',
    ];

    protected $sessionId;

    protected $sessionData;

    /**
     * 对象句柄
     */
    protected $handler;

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
        $this->handler = $this->app->cache;
    }

    /**
     * 初始化
     * @return void
     */
    public function init($config)
    {
        $this->config = array_merge($this->config, $config);
        $curid = $this->app->cookie->get($this->config['name']);
        if (empty($curid)) {
            $curid = $this->app->uniqid();
            $this->app->cookie->set($this->config['name'], $curid, $this->config['cookie_lifetime']);
        }
        $this->sessionId = $curid;
        $this->refreshData();
    }

    /**
     * 获取id
     * @return string
     */
    public function getid()
    {
        return $this->sessionId;
    }

    /**
     * 是否存在
     * @param  string $name 名称
     * @return bool
     */
    public function has($name)
    {
        return isset($this->sessionData[$name]);
    }

    /**
     * 获取单个
     * @param  string  $name 名称
     * @return mixed
     */
    public function get($name)
    {
        return $this->sessionData[$name] ?? null;
    }

    /**
     * 获取全部
     * @return array
     */
    public function getAll()
    {
        $this->refreshData();
        return $this->sessionData;
    }

    /**
     * 设置session数据
     * @param  string|array $name  名称
     * @param  mixed        $value 内容
     * @return bool
     */
    public function set($name, $value = null)
    {
        $this->refreshData();
        if (is_array($name)) {
            $this->sessionData = array_merge($this->sessionData, $name);
        } else {
            $this->sessionData[$name] = $value;
        }
        return $this->handler->set($this->getCacheName(), $this->sessionData, $this->config['gc_maxlifetime']);
    }

    /**
     * 删除
     * @param  string|array $name 名称
     * @return bool
     */
    public function delete($name)
    {
        $this->refreshData();
        if (is_array($name)) {
            foreach ($name as $key) {
                unset($this->sessionData[$key]);
            }
        } else {
            unset($this->sessionData[$name]);
        }
        return $this->handler->set($this->getCacheName(), $this->sessionData, $this->config['gc_maxlifetime']);
    }

    /**
     * 清空
     * @return void
     */
    public function clear()
    {
        $this->sessionData = [];
        $this->handler->delete($this->getCacheName());
    }

    /**
     * 关闭
     * @return void
     */
    public function close()
    {
    }

    /**
     * 刷新数据
     */
    protected function refreshData()
    {
        $this->sessionData = $this->handler->get($this->getCacheName(), []);
    }

    /**
     * 获取缓存字段
     */
    protected function getCacheName()
    {
        return $this->config['prefix'] . $this->sessionId;
    }
}
