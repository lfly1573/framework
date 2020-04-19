<?php

/**
 * 管道操作
 */

namespace lfly;

use Closure;
use Exception;
use Throwable;
use lfly\exception\HttpResponseException;

class Pipeline
{
    protected $passable;

    protected $pipes = [];

    protected $exceptionHandler;

    /**
     * 设置管道执行程序列表
     * @param array|mixed $pipes 管道执行程序数组
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    /**
     * 设置管道执行传入数据
     * @param mixed $passable 每个管道程序传入执行数据
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * 设置异常处理器
     * @param callable $handler 异常处理程序
     * @return $this
     */
    public function whenException($handler)
    {
        $this->exceptionHandler = $handler;
        return $this;
    }

    /**
     * 执行所有管道程序
     * @param Closure $destination 最后执行顺序函数(注意管道内程序可以控制先执行还是后执行)
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            function ($passable) use ($destination) {
                try {
                    return $destination($passable);
                } catch (HttpResponseException $e) {
                    return $e->getResponse();
                } catch (Throwable | Exception $e) {
                    return $this->handleException($passable, $e);
                }
            }
        );

        return $pipeline($this->passable);
    }

    /**
     * 生成array_reduce处理的匿名函数
     * @return Closure
     */
    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    return $pipe($passable, $stack);
                } catch (HttpResponseException $e) {
                    return $e->getResponse();
                } catch (Throwable | Exception $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    /**
     * 异常处理
     * @param mixed     $passable 管道执行数据
     * @param Throwable $e        错误对象
     * @return mixed
     */
    protected function handleException($passable, Throwable $e)
    {
        if ($this->exceptionHandler) {
            return call_user_func($this->exceptionHandler, $passable, $e);
        }
        throw $e;
    }
}
