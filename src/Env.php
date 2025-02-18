<?php

/**
 * 环境变量
 */

namespace lfly;

class Env
{
    /**
     * 环境变量数据
     * @var array
     */
    protected $data = [];

    /**
     * 数据转换映射
     * @var array
     */
    protected $convert = [
        'true'  => true,
        'false' => false,
        'off'   => false,
        'on'    => true,
    ];

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
        $this->data = $_ENV;
        if (is_file(ROOT_PATH . '.env')) {
            $this->loadFile(ROOT_PATH . '.env');
        }
    }

    /**
     * 读取环境变量文件
     * @param string $file 环境变量文件
     * @return void
     */
    public function loadFile($file)
    {
        $envData = parse_ini_file($file, true, INI_SCANNER_RAW) ?: [];
        $this->set($envData);
    }

    /**
     * 获取环境变量值
     * @param string $name    环境变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->data;
        }

        $name = strtoupper(str_replace('.', '_', $name));
        if (isset($this->data[$name])) {
            $result = $this->data[$name];
            if (is_string($result) && isset($this->convert[$result])) {
                return $this->convert[$result];
            }
            return $result;
        }
        return $default;
    }

    /**
     * 设置环境变量值
     * @param string|array $env   环境变量
     * @param mixed        $value 值
     * @return void
     */
    public function set($env, $value = null)
    {
        if (is_array($env)) {
            $env = array_change_key_case($env, CASE_UPPER);
            foreach ($env as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $this->data[$key . '_' . strtoupper($k)] = $v;
                    }
                } else {
                    $this->data[$key] = $val;
                }
            }
        } else {
            $name = strtoupper(str_replace('.', '_', $env));
            $this->data[$name] = $value;
        }
    }

    /**
     * 检测是否存在环境变量
     * @param string $name 参数名
     * @return bool
     */
    public function has($name)
    {
        return !is_null($this->get($name));
    }

    /**
     * 设置环境变量
     * @param string $name  参数名
     * @param mixed  $value 值
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * 获取环境变量
     * @param string $name 参数名
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * 检测是否存在环境变量
     * @param string $name 参数名
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }
}
