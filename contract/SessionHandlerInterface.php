<?php

/**
 * session驱动接口
 */

namespace lfly\contract;

interface SessionHandlerInterface
{
    /**
     * 初始化
     * @param  array $config 配置
     * @return void
     */
    public function init($config);

    /**
     * 获取id
     * @return string
     */
    public function getid();

    /**
     * 是否存在
     * @param  string $name 名称
     * @return bool
     */
    public function has($name);

    /**
     * 获取单个
     * @param  string  $name 名称
     * @return mixed
     */
    public function get($name);

    /**
     * 获取全部
     * @return array
     */
    public function getAll();

    /**
     * 设置session数据
     * @param  string|array $name  名称
     * @param  mixed        $value 内容
     * @return bool
     */
    public function set($name, $value = null);

    /**
     * 删除
     * @param  string|array $name 名称
     * @return bool
     */
    public function delete($name);

    /**
     * 清空
     * @return void
     */
    public function clear();

    /**
     * 关闭
     * @return void
     */
    public function close();
}
