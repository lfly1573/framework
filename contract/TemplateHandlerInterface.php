<?php

/**
 * 视图驱动接口
 */

namespace lfly\contract;

interface TemplateHandlerInterface
{
    /**
     * 检测是否存在模板文件
     * @param  string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template);

    /**
     * 渲染模板文件并输出
     * @param  string $template 模板文件
     * @param  array  $data     模板变量
     * @return void
     */
    public function fetch(string $template, array $data = []);

    /**
     * 渲染模板字符串并输出
     * @param  string $content 模板内容
     * @param  array  $data    模板变量
     * @return void
     */
    public function display(string $content, array $data = []);

    /**
     * 配置模板引擎
     * @param  array $config 参数
     * @return void
     */
    public function config(array $config);

    /**
     * 获取模板引擎配置
     * @param  string $name 参数名
     * @return mixed
     */
    public function getConfig(string $name);
}
