<?php

/**
 * 服务注册
 */

namespace lfly;

class Service
{
    /**
     * @var App
     */
    protected $app;

    /**
     * 注册的系统服务
     * @var array
     */
    protected $services = [];

    /**
     * 服务实例
     * @var array
     */
    protected $instances = [];

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 服务注册
     */
    public function register()
    {
        foreach ($this->services as $service) {
            if (class_exists($service) && !isset($this->instances[$service])) {
                $instance = new $service($this->app);
                if (method_exists($instance, 'register')) {
                    $instance->register();
                }
                $this->instances[$service] = $instance;
            }
        }
    }

    /**
     * 服务启动
     */
    public function boot()
    {
        foreach ($this->instances as $instance) {
            if (method_exists($instance, 'boot')) {
                $this->app->invoke([$instance, 'boot']);
            }
        }
    }

    /**
     * 载入默认配置
     * @param string $file 文件路径
     * @return void
     */
    public function loadFile($file = null)
    {
        $file = $file ?? CONFIG_PATH . 'service' . EXT;
        if (is_file($file)) {
            $curServices = (include $file);
            if (is_array($curServices)) {
                $this->services = array_unique(array_merge($this->services, $curServices));
            }
            $this->register();
        }
    }
}
