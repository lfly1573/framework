<?php

/**
 * 中间件提供者
 */

namespace lfly\contract;

use Closure;
use lfly\App;
use lfly\Response;

abstract class MiddlewareHandler
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 执行中间件
     * @param Request $request 输入内容
     * @param Closure $next    下一个中间件函数
     * @param mixed   $param   额外参数
     * @return Response
     */
    public function handle($request, Closure $next, ...$param)
    {
        //添加实际控制器执行前代码
        $response = $next($request);
        //添加实际控制器执行后代码
        return $response;
    }

    /**
     * 结束调度(内部不能输出任何内容)
     * @param Response $response 输出内容
     * @return void
     */
    public function end(Response $response)
    {
    }
}
