<?php

/**
 * 文件驱动接口
 */

namespace lfly\contract;

interface FileHandlerInterface
{
    /**
     * 初始化
     * @param  array $config 配置
     * @return $this
     */
    public function init($config);

    /**
     * 保存文件
     * @param  array $fromFile 原始文件
     * @param  array $toFile   新文件路径
     * @return string|array
     */
    public function putFile($fromFile, $toFile);

    /**
     * 删除文件
     * @param  string $file 完整文件
     * @return bool
     */
    public function delFile($file = null);
}
