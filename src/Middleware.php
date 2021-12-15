<?php

/**
 * 中间件管理类
 */

namespace lfly;

use Closure;
use InvalidArgumentException;
use LogicException;
use Throwable;

class Middleware
{
    /**
     * 中间件别名
     * @var array
     */
    protected $alias = [];

    /**
     * 全局中间件预设
     * @var array
     */
    protected $globalConfig = [];

    /**
     * 中间件执行队列
     * @var array
     */
    protected $queue = [];

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
     * 载入默认配置
     * @param string $file 文件路径
     * @return $this
     */
    public function loadFile($file = null)
    {
        $file = $file ?? CONFIG_PATH . 'middleware' . EXT;
        if (is_file($file)) {
            $config = (include $file);
            if (is_array($config)) {
                if (!empty($config['alias'])) {
                    $this->alias = $config['alias'];
                }
                if (!empty($config['global'])) {
                    $this->globalConfig = $config['global'];
                }
            }
        }
        return $this;
    }

    /**
     * 载入全局中间件
     * @return $this
     */
    public function importDefault()
    {
        $this->import($this->globalConfig);
        return $this;
    }

    /**
     * 导入中间件
     * @param array  $middlewares 中间件数组
     * @param string $type        中间件类型
     * @return void
     */
    public function import($middlewares = [], $type = 'global')
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware, $type);
        }
    }

    /**
     * 注册中间件
     * @param mixed  $middleware 单个中间件
     * @param string $type       中间件类型
     * @return void
     */
    public function add($middleware, $type = 'global')
    {
        $middleware = $this->buildMiddleware($middleware, $type);

        if (!empty($middleware)) {
            $this->queue[$type][] = $middleware;
            $this->queue[$type] = array_unique($this->queue[$type], SORT_REGULAR);
        }
    }

    /**
     * 注册路由中间件
     * @param mixed $middleware 单个中间件
     * @return void
     */
    public function route($middleware)
    {
        $this->add($middleware, 'route');
    }

    /**
     * 注册控制器中间件
     * @param mixed $middleware 单个中间件
     * @return void
     */
    public function controller($middleware)
    {
        $this->add($middleware, 'controller');
    }

    /**
     * 注册中间件到开始位置
     * @param mixed  $middleware 单个中间件
     * @param string $type       中间件类型
     */
    public function unshift($middleware, string $type = 'global')
    {
        $middleware = $this->buildMiddleware($middleware, $type);

        if (!empty($middleware)) {
            if (!isset($this->queue[$type])) {
                $this->queue[$type] = [];
            }
            array_unshift($this->queue[$type], $middleware);
        }
    }

    /**
     * 获取注册的中间件
     * @param string $type 中间件类型
     * @return array
     */
    public function all(string $type = 'global')
    {
        return $this->queue[$type] ?? [];
    }

    /**
     * 调度管道
     * @param string $type 中间件类型
     * @return \lfly\Pipeline
     */
    public function pipeline(string $type = 'global')
    {
        return (new Pipeline())
            ->through(array_map(function ($middleware) {
                return function ($request, $next) use ($middleware) {
                    [$call, $params] = $middleware;
                    if (is_array($call) && is_string($call[0])) {
                        $call = [$this->app->make($call[0]), $call[1]];
                    }
                    $response = call_user_func($call, $request, $next, ...$params);

                    if (!$response instanceof Response) {
                        throw new LogicException('The middleware must return Response instance');
                    }
                    return $response;
                };
            }, ($this->queue[$type] ?? [])))
            ->whenException([$this, 'handleException']);
    }

    /**
     * 结束调度
     * @param \lfly\Response $response 输出对象
     */
    public function end(Response $response)
    {
        foreach ($this->queue as $queue) {
            foreach ($queue as $middleware) {
                [$call] = $middleware;
                if (is_array($call) && is_string($call[0])) {
                    $instance = $this->app->make($call[0]);
                    if (method_exists($instance, 'end')) {
                        $instance->end($response);
                    }
                }
            }
        }
    }

    /**
     * 异常处理
     * @param \lfly\Request  $passable
     * @param \Throwable     $e
     * @return \lfly\Response
     */
    public function handleException($passable, Throwable $e)
    {
        $handler = $this->app->make(Exception::class);
        $handler->report($e);
        return $handler->render($passable, $e);
    }

    /**
     * 解析中间件 设定调用中间件的handle方法
     * @access protected
     * @param mixed  $middleware 单个中间件
     * @param string $type       中间件类型
     * @return array
     */
    protected function buildMiddleware($middleware, string $type)
    {
        if (is_array($middleware)) {
            [$middleware, $params] = $middleware;
            if (!is_array($params)) {
                $params = (array)$params;
            }
        }

        if ($middleware instanceof Closure) {
            return [$middleware, $params ?? []];
        }

        if (!is_string($middleware)) {
            throw new InvalidArgumentException('The middleware is invalid');
        }

        //中间件别名检查
        if (isset($this->alias[$middleware])) {
            $middleware = $this->alias[$middleware];
        }

        if (is_array($middleware)) {
            $this->import($middleware, $type);
            return [];
        }

        return [[$middleware, 'handle'], $params ?? []];
    }
}