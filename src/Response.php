<?php

/**
 * 输出
 */

namespace lfly;

use InvalidArgumentException;
use lfly\exception\HttpResponseException;

class Response
{
    /**
     * 原始数据
     * @var mixed
     */
    protected $data;

    /**
     * 输出内容
     * @var string
     */
    protected $content = null;

    /**
     * 当前contentType
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * 字符集
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * 状态码
     * @var integer
     */
    protected $code = 200;

    /**
     * 个性化输出参数
     * @var array
     */
    protected $options = [];

    /**
     * header参数
     * @var array
     */
    protected $header = [];

    /**
     * 当前引擎
     * @var string
     */
    protected $engine = 'html';

    /**
     * 当前模版
     * @var string
     */
    protected $template;

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
    }

    /**
     * 切换引擎(默认是html,切换需要最先调用)
     * @param  string  $type 输出类名
     * @return \lfly\Response
     *
     * @throws InvalidArgumentException
     */
    public function engine($type)
    {
        $class = (false !== strpos($type, '\\')) ? $type : __NAMESPACE__ . '\\response\\' . ucfirst(strtolower($type));
        $response = $this->app->invokeClass($class);
        if (!$response instanceof $this) {
            throw new InvalidArgumentException('response type error: ' . $class);
        }
        $this->app->instance('response', $response);
        return $response;
    }

    /**
     * 初始化
     * @param  mixed  $data 输出数据
     * @param  int    $code 状态码
     * @return $this
     */
    public function init($data = '', int $code = 200)
    {
        $this->setData($data);
        $this->setCode($code);
        $this->contentType($this->contentType, $this->charset);
        return $this;
    }

    /**
     * 设置模版
     * @param  string $template 模版名称
     * @return $this
     */
    public function setTemplate($template = '')
    {
        if (empty($template)) {
            $template = $this->app->stripClass($this->app->request->controller()) . '/' . $this->app->request->action();
            if ($template == '/' || !$this->app->view->engine()->exists($template)) {
                $template = '';
            }
        }
        $this->template = $template;
        return $this;
    }

    /**
     * 发送数据到客户端
     * @return void
     * 
     * @throws InvalidArgumentException
     */
    public function send()
    {
        //监听输出前事件
        $this->app->event->trigger('ResponseSend', $this->app);

        $showContent = $this->getContent();

        if (!headers_sent() && !empty($this->header)) {
            // 发送状态码
            http_response_code($this->code);
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                header($name . (!is_null($val) ? ':' . $val : ''));
            }
        }

        $this->setOther();
        $this->sendContent($showContent);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * 获取输出数据
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    public function getContent()
    {
        if (null == $this->content) {
            $content = $this->output($this->data);

            if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([$content, '__toString'])) {
                throw new InvalidArgumentException(sprintf('variable type error： %s', gettype($content)));
            }

            $this->content = (string)$content;
        }
        return $this->content;
    }

    /**
     * 设置显示数据
     * @param  string $content 输出数据
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 设置输出数据
     * @param  mixed $data 输出数据
     * @return $this
     */
    public function setData($data)
    {
        if (is_array($data) && $this->app->isDebug()) {
            $data['_debug_system_info'] = $this->app->exception->debug();
        }
        $this->data = $data;
        return $this;
    }

    /**
     * 设置HTTP状态
     * @param  integer $code 状态码
     * @return $this
     */
    public function setCode(int $code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * LastModified
     * @param  string $time
     * @return $this
     */
    public function lastModified($time)
    {
        $this->header['Last-Modified'] = $time;
        return $this;
    }

    /**
     * Expires
     * @param  string $time
     * @return $this
     */
    public function expires($time)
    {
        $this->header['Expires'] = $time;
        return $this;
    }

    /**
     * ETag
     * @param  string $eTag
     * @return $this
     */
    public function eTag($eTag)
    {
        $this->header['ETag'] = $eTag;
        return $this;
    }

    /**
     * 页面缓存控制
     * @param  string $cache 状态码
     * @return $this
     */
    public function cacheControl($cache)
    {
        $this->header['Cache-control'] = $cache;
        return $this;
    }

    /**
     * 页面输出类型
     * @param  string $contentType 输出类型
     * @param  string $charset     输出编码
     * @return $this
     */
    public function contentType($contentType, $charset = 'utf-8')
    {
        $this->header['Content-Type'] = $contentType . '; charset=' . $charset;
        return $this;
    }

    /**
     * 设置响应头
     * @param  array $header  参数
     * @return $this
     */
    public function header(array $header = [])
    {
        $this->header = array_merge($this->header, $header);
        return $this;
    }

    /**
     * 获取头部信息
     * @param  string $name 头部名称
     * @return mixed
     */
    public function getHeader(string $name = '')
    {
        if (!empty($name)) {
            return $this->header[$name] ?? null;
        }
        return $this->header;
    }

    /**
     * 输出的参数
     * @param  mixed $options 输出参数
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * 获取原始数据
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 获取状态码
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 获取当前引擎
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * 调试输出并中止
     * @return $this
     */
    public function halt($data, $type = '')
    {
        if ($type == 'var') {
            $data = var_dump($data);
        } elseif (!is_string($data)) {
            $data = print_r($data, true);
        }
        $this->content = '<pre>' . $data . '</pre>';
        throw new HttpResponseException($this);
    }

    /**
     * 个性化处理数据(不同类别需自定义实现该功能)
     * @param  mixed $data 要处理的数据
     * @return mixed
     */
    protected function output($data)
    {
        if (!empty($this->template)) {
            return $this->app->view->fetch($this->template, is_array($data) ? $data : ['_return' => $data]);
        }
        if (is_array($data)) {
            return '<pre>' . print_r($data, true) . '</pre>';
        }
        return $data;
    }

    /**
     * 头部其他设定
     * @return void
     */
    protected function setOther()
    {
    }

    /**
     * 输出数据
     * @param string $content 输出的数据
     * @return void
     */
    protected function sendContent(string $content)
    {
        echo $content;
    }
}
