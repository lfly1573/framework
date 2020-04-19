<?php

/**
 * 缓存驱动接口
 */

namespace lfly\contract;

interface CacheHandlerInterface
{
    /**
     * 初始化
     * @param  array $config 配置
     * @return void
     */
    public function init($config);

    /**
     * 获取对象句柄
     */
    public function handler();

    /**
     * 是否存在
     * @param  string $key 名称
     * @return bool
     */
    public function has($key);

    /**
     * 获取单个
     * @param  string  $key 名称
     * @return mixed
     */
    public function get($key);

    /**
     * 设置
     * @param  string       $key   名称
     * @param  mixed        $value 内容
     * @param int|\DateTime $ttl   有效时间 0为永久
     * @return bool
     */
    public function set($key, $value, $ttl = null);

    /**
     * 自增缓存（针对数值缓存）
     * @param  string    $key  名称
     * @param  int       $step 步长
     * @return false|int
     */
    public function inc($key, $step = 1);

    /**
     * 自减缓存（针对数值缓存）
     * @param  string    $key  名称
     * @param  int       $step 步长
     * @return false|int
     */
    public function dec($key, $step = 1);

    /**
     * 删除
     * @param  string $key 名称
     * @return bool
     */
    public function delete($key);

    /**
     * 清空
     * @return void
     */
    public function clear();
}
