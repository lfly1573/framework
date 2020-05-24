<?php

/**
 * http服务
 */

namespace lfly;

use LogicException;
use Throwable;
use ReflectionClass;
use lfly\exception\HttpResponseException;

class Http
{
    /**
     * @var App
     */
    protected $app;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 执行应用程序
     * @param Request|null $request 请求实例
     * @return Response 输出实例
     */
    public function run(Request $request = null) : Response
    {
        //自动创建request对象
        $request = $request ?? $this->app->make('request', [], true);
        $this->app->instance('request', $request);

        //加载全局中间件
        $this->app->middleware->loadFile()->importDefault();

        //监听HttpRun事件
        $this->app->event->trigger('HttpRun', $this->app);

        try {
            return $this->app->middleware->pipeline()
                ->send($request)
                ->then(function ($request) {
                    return $this->dispatchToRoute($request);
                });
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        } catch (Throwable $e) {
            $handler = $this->app->make(Exception::class);
            $handler->report($e);
            return $handler->render($request, $e);
        }
    }

    /**
     * HttpEnd
     * @param Response $response 输出
     * @return void
     */
    public function end(Response $response)
    {
        //执行中间件
        $this->app->middleware->end($response);
        //监听HttpEnd事件
        $this->app->event->trigger('HttpEnd', $this->app);
        //写入日志
        if (!empty($this->app->config->get('log.level', []))) {
            $this->app->log->save();
        }
    }

    /**
     * 分发到路由解析
     */
    protected function dispatchToRoute(Request $request)
    {
        $this->app->route->loadFile();
        //监听路由加载结束事件
        $this->app->event->trigger('RouteLoaded', $this->app);
        //解析路由
        $routeData = $this->app->route->dispatch($request);

        if (!empty($routeData['middleware'])) {
            $this->app->middleware->import($routeData['middleware'], 'route');
        }
        return $this->app->middleware->pipeline('route')
            ->send($request)
            ->then(function () use ($routeData) {
                return $this->execute($routeData);
            });
    }

    /**
     * 执行最终结果
     */
    protected function execute($routeData)
    {
        if ($this->app->request->method == 'OPTIONS') {
            return $this->app->response->init('', 204);
        }
        $data = '';
        if (!empty($routeData['callback'])) {
            if (!is_callable($routeData['callback'], true)) {
                throw new LogicException('callback error');
            }
            $data = $this->app->invokeFunction($routeData['callback'], $routeData['args']);
        } elseif (!empty($routeData['controller']) && !empty($routeData['action'])) {
            $template = $this->app->stripClass($routeData['controller']) . '/' . $routeData['action'];
            if (!class_exists($routeData['controller'])) {
                if ($this->app->view->engine()->exists($template)) {
                    return $this->app->response->init('')->setTemplate($template);
                }
                if (!$this->app->isDebug()) {
                    return $this->app->response->init('', 404)->setTemplate('404');
                }
                throw new LogicException('class not exists: ' . $routeData['controller']);
            }
            $instance = $this->app->make($routeData['controller'], [], true);
            $action = $routeData['action'];
            $vars = $routeData['args'];
            $this->registerControllerMiddleware($instance, $action);
            $data = $this->app->middleware->pipeline('controller')
                ->send($this->app->request)
                ->then(function () use ($template, $instance, $action, $vars) {
                    if ($action[0] == '_' || !is_callable([$instance, $action])) {
                        if ($this->app->view->engine()->exists($template)) {
                            return $this->app->response->init('')->setTemplate($template);
                        }
                        if (!$this->app->isDebug()) {
                            return $this->app->response->init('', 404)->setTemplate('404');
                        }
                        throw new LogicException('method not exists: ' . get_class($instance) . '->' . $action . '()');
                    }
                    return $this->app->invokeMethod([$instance, $action], $vars);
                });
        } elseif (!empty($routeData['template'])) {
            return $this->app->response->setTemplate($routeData['template']);
        } elseif (!empty($routeData['redirect'])) {
            return $this->app->response->engine('redirect')->init($routeData['redirect']);
        } else {
            return $this->app->response->init('', 404)->setTemplate('404');
        }
        return $this->autoResponse($data);
    }

    /**
     * 反射机制获取控制器中间件
     */
    protected function registerControllerMiddleware($controller, $action)
    {
        $class = new ReflectionClass($controller);
        if ($class->hasProperty('middleware')) {
            $reflectionProperty = $class->getProperty('middleware');
            $reflectionProperty->setAccessible(true);

            $middlewares = $reflectionProperty->getValue($controller);
            foreach ($middlewares as $key => $val) {
                if (!is_int($key)) {
                    if (isset($val['only']) && !in_array($action, $val['only'])) {
                        continue;
                    } elseif (isset($val['except']) && in_array($action, $val['except'])) {
                        continue;
                    } else {
                        $val = $key;
                    }
                }
                //参数传递
                if (is_string($val) && strpos($val, ':')) {
                    $val = explode(':', $val, 2);
                }
                $this->app->middleware->controller($val);
            }
        }
    }

    /**
     * 自动格式化输出
     */
    protected function autoResponse($response)
    {
        $responseClassname = $this->app->getAlias('response');
        if ($response instanceof $responseClassname) {
            return $response;
        } else {
            if (is_null($response)) {
                $data = ob_get_clean();
                $response = (false === $data) ? '' : $data;
            }
            if ($this->app->request->isJson() || $this->app->request->isAjax() || $this->app->request->isPjax() || (is_string($response) && $response != '' && $response[0] == '{' && !is_null(json_decode($response)))) {
                $status = ('' === $response) ? 204 : 200;
                if ($this->app->request->param('callback', '') != '') {
                    return $this->app->response->engine('jsonp')->init($response, $status);
                }
                return $this->app->response->engine('json')->init($response, $status);
            } else {
                return $this->app->response->init($response)->setTemplate();
            }
        }
    }
}
