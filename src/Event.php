<?php

/**
 * 事件类
 */

namespace lfly;

use ReflectionClass;
use ReflectionMethod;

class Event
{
    /**
     * 是否需要事件响应
     * @var bool
     */
    protected $withEvent = true;

    /**
     * 监听者
     * @var array
     */
    protected $listener = [];

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
        $this->withEvent($this->app->config->get('with_event', true));
    }

    /**
     * 设置是否开启事件响应
     * @param bool $status 是否需要事件响应
     * @return $this
     */
    public function withEvent(bool $status)
    {
        $this->withEvent = $status;
        return $this;
    }

    /**
     * 载入配置
     * @param string $file 文件路径
     * @return $this
     */
    public function loadFile($file = null)
    {
        if (!$this->withEvent) {
            return $this;
        }
        $file = $file ?? CONFIG_PATH . 'event' . EXT;
        if (is_file($file)) {
            $config = (include $file);
            if (is_array($config)) {
                if (!empty($config['listen'])) {
                    $this->listenEvents($config['listen']);
                }
                if (!empty($config['subscribe'])) {
                    $this->subscribe($config['subscribe']);
                }
            }
        }
        return $this;
    }

    /**
     * 批量注册事件监听
     * @param array $events 事件定义
     * @return $this
     */
    public function listenEvents(array $events)
    {
        if (!$this->withEvent) {
            return $this;
        }
        foreach ($events as $event => $listeners) {
            $this->listener[$event] = array_merge($this->listener[$event] ?? [], (array)$listeners);
        }
        return $this;
    }

    /**
     * 注册事件监听
     * @param string $event    事件名称
     * @param mixed  $listener 监听操作（或者类名）
     * @param bool   $first    是否优先执行
     * @return $this
     */
    public function listen($event, $listener, bool $first = false)
    {
        if (!$this->withEvent) {
            return $this;
        }

        if ($first && isset($this->listener[$event])) {
            array_unshift($this->listener[$event], $listener);
        } else {
            $this->listener[$event][] = $listener;
        }

        return $this;
    }

    /**
     * 注册事件订阅者
     * @param mixed $subscriber 订阅者
     * @return $this
     */
    public function subscribe($subscriber)
    {
        if (!$this->withEvent) {
            return $this;
        }

        $subscribers = (array)$subscriber;
        foreach ($subscribers as $subscriber) {
            if (is_string($subscriber)) {
                $subscriber = $this->app->make($subscriber);
            }
            if (method_exists($subscriber, 'subscribe')) {
                //自定义订阅
                $subscriber->subscribe($this);
            } else {
                //自动订阅
                $this->observe($subscriber);
            }
        }

        return $this;
    }

    /**
     * 自动注册事件观察者
     * @param string|object $observer 观察者
     * @return $this
     */
    public function observe($observer)
    {
        if (!$this->withEvent) {
            return $this;
        }

        if (is_string($observer)) {
            $observer = $this->app->make($observer);
        }

        $reflect = new ReflectionClass($observer);
        $methods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $name = $method->getName();
            if (0 === strpos($name, 'on')) {
                $this->listen(substr($name, 2), [$observer, $name]);
            }
        }

        return $this;
    }

    /**
     * 触发事件
     * @param string $event  事件名称
     * @param mixed  $params 传入参数
     * @param bool   $once   只获取一个有效返回值
     * @return mixed
     */
    public function trigger($event, $params = null, bool $once = false)
    {
        if (!$this->withEvent) {
            return;
        }

        $result = [];
        $listeners = $this->listener[$event] ?? [];
        if (!empty($listeners)) {
            $listeners = array_unique($listeners, SORT_REGULAR);
            foreach ($listeners as $key => $listener) {
                $result[$key] = $this->dispatch($listener, $params);
                if (false === $result[$key] || (!is_null($result[$key]) && $once)) {
                    break;
                }
            }
        }

        return $once ? end($result) : $result;
    }

    /**
     * 触发事件(只获取一个有效返回值)
     * @param string $event  事件名称
     * @param mixed  $params 传入参数
     * @return mixed
     */
    public function until($event, $params = null)
    {
        return $this->trigger($event, $params, true);
    }

    /**
     * 执行事件调度
     * @param mixed $listener 方法
     * @param mixed  $params  参数
     * @return mixed
     */
    protected function dispatch($listener, $params = null)
    {
        if (!is_string($listener)) {
            $call = $listener;
        } elseif (strpos($listener, '::')) {
            $call = $listener;
        } else {
            $call = [$this->app->make($listener), 'handle'];
        }
        return $this->app->invoke($call, [$params]);
    }

    /**
     * 是否存在事件监听
     * @param string $event 事件名称
     * @return bool
     */
    public function hasListener($event)
    {
        return isset($this->listener[$event]);
    }

    /**
     * 移除事件监听
     * @param string $event 事件名称
     * @return $this
     */
    public function remove($event)
    {
        unset($this->listener[$event]);
        return $this;
    }
}
