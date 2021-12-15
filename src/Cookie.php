<?php

/**
 * cookie类
 */

namespace lfly;

use DateTimeInterface;

class Cookie
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        //cookie默认前缀
        'prefix' => 'fc_',
        //cookie保存路径
        'path' => '/',
        //cookie有效域名
        'domain' => '',
        //cookie启用安全传输
        'secure' => false,
    ];

    /**
     * Cookie数据
     * @var array
     */
    protected $cookie = [];

    /**
     * 构造函数
     * @param \lfly\Config $obj 配置类
     */
    public function __construct(Config $obj)
    {
        $config = $obj->get('cookie');
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $length = strlen($this->config['prefix']);
        foreach ($_COOKIE as $key => $value) {
            if ($length == 0 || substr($key, 0, $length) == $this->config['prefix']) {
                $this->cookie[substr($key, $length)] = $value;
            }
        }
    }

    /**
     * 是否存在Cookie参数
     * @param  string $name 名称
     * @return bool
     */
    public function has($name)
    {
        return isset($this->cookie[$name]);
    }

    /**
     * 获取cookie
     * @param  string  $name    名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return $this->cookie[$name] ?? $default;
    }

    /**
     * 获取全部cookie
     * @return array
     */
    public function getAll()
    {
        return $this->cookie;
    }

    /**
     * Cookie设置
     * @param  string                $name     名称
     * @param  mixed                 $value    内容
     * @param  int|DateTimeInterface $life     有效秒数
     * @param  bool                  $httponly 仅可通过HTTP协议访问
     * @param  bool                  $prefix   是否包含前缀
     * @return bool
     */
    public function set($name, $value, $life = 0, $httponly = false, $prefix = true)
    {
        if ($life instanceof DateTimeInterface) {
            $life = $life->getTimestamp() - time();
        }
        $result = setcookie(
            ($prefix ? $this->config['prefix'] : '') . $name,
            $value,
            $life ? time() + $life : 0,
            $this->config['path'],
            $this->config['domain'],
            $this->config['secure'],
            $httponly
        );
        if ($result) {
            if ($value == '' && $life < 0) {
                unset($this->cookie[$name]);
            } else {
                $this->cookie[$name] = $value;
            }
        }
        return $result;
    }

    /**
     * Cookie删除
     * @param  string $name   名称
     * @param  bool   $prefix 是否包含前缀
     * @return bool
     */
    public function delete($name, $prefix = true)
    {
        return $this->set($name, '', -86400, false, $prefix);
    }
}
