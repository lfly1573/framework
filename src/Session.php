<?php

/**
 * session类
 */

namespace lfly;

class Session
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        //类型 支持php和cache
        'type' => 'php',
        //指定会话名以用做cookie的名字
        'name' => 'FCSESSID',
        //以秒数指定了发送到浏览器的cookie的生命周期
        'cookie_lifetime' => 0,
        //数据的有效期秒数
        'gc_maxlifetime' => 1440,
    ];

    /**
     * session驱动
     */
    protected $driver;

    /**
     * @var \lfly\App
     */
    protected $app;

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $config = $this->app->config->get('session');
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->engine($this->config['type']);
    }

    /**
     * 获取和设置引擎
     * @param string $class 引擎类
     * @return \lfly\contract\SessionHandlerInterface
     */
    public function engine($class = null)
    {
        if (is_null($class) && !is_null($this->driver)) {
            return $this->driver;
        }
        if (empty($class)) {
            $class = $this->config['type'];
        }
        $class = (false !== strpos($class, '\\')) ? $class : __NAMESPACE__ . '\\session\\' . ucfirst(strtolower($class));
        $this->driver = $this->app->invokeClass($class);
        $this->driver->init($this->config);
        return $this->driver;
    }

    /**
     * 获取会话id
     * @return string
     */
    public function getid()
    {
        return $this->driver->getid();
    }

    /**
     * 是否存在
     * @param  string $name 名称
     * @return bool
     */
    public function has($name)
    {
        return $this->driver->has($name);
    }

    /**
     * 取值并删除
     * @param  string  $name    名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public function pull($name, $default = null)
    {
        $value = $this->get($name, $default);
        if (!is_null($value)) {
            $this->delete($name);
        }
        return $value;
    }

    /**
     * 获取单个
     * @param  string  $name    名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $value = $this->driver->get($name);
        if (is_null($value)) {
            $value = $default;
        }
        return $value;
    }

    /**
     * 获取全部
     * @return array
     */
    public function getAll()
    {
        return $this->driver->getAll();
    }

    /**
     * 设置
     * @param  string|array $name  名称
     * @param  mixed        $value 内容
     * @return bool
     */
    public function set($name, $value)
    {
        return $this->driver->set($name, $value);
    }

    /**
     * 删除
     * @param  string|array $name 名称
     * @return bool
     */
    public function delete($name)
    {
        return $this->driver->delete($name);
    }

    /**
     * 清空
     * @return void
     */
    public function clear()
    {
        $this->driver->clear();
    }

    /**
     * 关闭
     * @return void
     */
    public function close()
    {
        $this->driver->close();
    }
}
