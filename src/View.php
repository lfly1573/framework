<?php

/**
 * 视图类
 */

namespace lfly;

use RuntimeException;
use Exception;

class View
{
    /**
     * 模板变量
     * @var array
     */
    protected $data = [];

    /**
     * 内容过滤
     * @var mixed
     */
    protected $filter;

    /**
     * @var App
     */
    protected $app;

    /**
     * 模版驱动
     */
    protected $driver;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->engine();
    }

    /**
     * 获取和设置模板引擎
     * @param string $class 模板引擎类
     * @return TemplateHandlerInterface
     */
    public function engine($class = null)
    {
        if (is_null($class) && !is_null($this->driver)) {
            return $this->driver;
        }
        $class = $class ?? $this->app->getAlias('template');
        $this->driver = $this->app->invokeClass($class);
        return $this->driver;
    }

    /**
     * 模板变量赋值
     * @param string|array $name  模板变量
     * @param mixed        $value 变量值
     * @return $this
     */
    public function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    /**
     * 视图过滤
     * @param Callable $filter 过滤方法或闭包，参数为模版整体内容
     * @return $this
     */
    public function filter(callable $filter = null)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template 模板文件名
     * @param array  $vars     模板变量
     * @return string
     * @throws Exception
     */
    public function fetch(string $template = '', array $vars = [])
    {
        return $this->getContent(function () use ($vars, $template) {
            $this->engine()->fetch($template, array_merge($this->data, $vars));
        });
    }

    /**
     * 渲染内容输出
     * @param string $content 内容
     * @param array  $vars    模板变量
     * @return string
     */
    public function display(string $content, array $vars = [])
    {
        return $this->getContent(function () use ($vars, $content) {
            $this->engine()->display($content, array_merge($this->data, $vars));
        });
    }

    /**
     * 获取解析后的模版文件 用于incude
     * @param string $template 模板文件名
     * @return string
     */
    public function getFile($template)
    {
        if (method_exists($this->engine(), 'getFile')) {
            return $this->engine()->getFile($template);
        }
        throw new RuntimeException('method not exists: View::getFile(' . $template . ')');
    }

    /**
     * 获取模板引擎渲染内容
     * @param $callback
     * @return string
     * @throws Exception
     */
    protected function getContent($callback)
    {
        //页面缓存
        ob_start();
        ob_implicit_flush(0);

        //渲染输出
        try {
            $callback();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }

        //获取并清空缓存
        $content = ob_get_clean();

        if ($this->filter) {
            $content = call_user_func_array($this->filter, [$content]);
        }

        return $content;
    }

    /**
     * 模板变量赋值
     * @access public
     * @param string $name  变量名
     * @param mixed  $value 变量值
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 取得模板显示变量的值
     * @access protected
     * @param string $name 模板变量
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * 检测模板变量是否设置
     * @access public
     * @param string $name 模板变量名
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
}
