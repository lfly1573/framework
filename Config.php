<?php

/**
 * 配置设置和获取
 */

namespace lfly;

class Config
{
    /**
     * 配置参数
     * @var array
     */
    protected $configParam = [];

    /**
     * 构造方法 默认载入app的配置信息
     */
    public function __construct()
    {
        $this->loadFile(CONFIG_PATH . 'app' . EXT);
    }

    /**
     * 载入配置文件
     * @param  string $file 配置文件名
     * @return array
     */
    public function loadFile($file)
    {
        if (is_file($file)) {
            $curConfig = (include $file);
            if (is_array($curConfig)) {
                return $this->set($curConfig);
            }
        }
        return [];
    }

    /**
     * 检测配置是否存在
     * @param  string $name 配置参数名
     * @return bool
     */
    public function has($name)
    {
        return !is_null($this->get($name));
    }

    /**
     * 获取配置参数
     * @param  string $name    配置参数名（支持多级配置 .号分割）
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function get($name = null, $default = null)
    {
        // 无参数时获取所有
        if (is_null($name)) {
            return $this->configParam;
        }

        if (false === strpos($name, '.')) {
            return isset($this->configParam[$name]) ? $this->configParam[$name] : $default;
        }

        $name = explode('.', $name);
        $config = $this->configParam;
        foreach ($name as $value) {
            if (isset($config[$value])) {
                $config = $config[$value];
            } else {
                return $default;
            }
        }
        return $config;
    }

    /**
     * 设置配置参数
     * @param  array $config 配置参数
     * @return array
     */
    public function set(array $config)
    {
        $this->configParam = array_merge($this->configParam, $config);
        return $this->configParam;
    }
}
