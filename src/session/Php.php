<?php

/**
 * session文件驱动
 */

namespace lfly\session;

use lfly\contract\SessionHandlerInterface;

class Php implements SessionHandlerInterface
{
    /**
     * 初始化
     * @param  array $config 配置
     * @return void
     */
    public function init($config)
    {
        session_start(['name' => $config['name'], 'cookie_lifetime' => $config['cookie_lifetime'], 'gc_maxlifetime' => $config['gc_maxlifetime']]);
    }

    /**
     * 获取id
     * @return string
     */
    public function getid()
    {
        return session_id();
    }

    /**
     * 是否存在
     * @param  string $name 名称
     * @return bool
     */
    public function has($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * 获取单个
     * @param  string  $name 名称
     * @return mixed
     */
    public function get($name)
    {
        return $_SESSION[$name] ?? null;
    }

    /**
     * 获取全部
     * @return array
     */
    public function getAll()
    {
        return $_SESSION;
    }

    /**
     * 设置session数据
     * @param  string|array $name  名称
     * @param  mixed        $value 内容
     * @return bool
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $_SESSION[$key] = $value;
            }
        } else {
            $_SESSION[$name] = $value;
        }
        return true;
    }

    /**
     * 删除
     * @param  string|array $name 名称
     * @return bool
     */
    public function delete($name)
    {
        if (is_array($name)) {
            foreach ($name as $key) {
                unset($_SESSION[$key]);
            }
        } else {
            unset($_SESSION[$name]);
        }
        return true;
    }

    /**
     * 清空
     * @return void
     */
    public function clear()
    {
        session_destroy();
        unset($_SESSION);
    }

    /**
     * 关闭
     * @return void
     */
    public function close()
    {
        session_write_close();
    }
}
