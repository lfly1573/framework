<?php

/**
 * app容器
 */

namespace lfly;

class App extends Container
{
    /**
     * 应用调试模式
     * @var bool
     */
    protected $appDebug = false;

    /**
     * 当前应用类库命名空间
     * @var string
     */
    protected $namespace = 'app';

    /**
     * 当前请求的唯一id
     * @var string
     */
    protected $uniqid;

    /**
     * 容器绑定标识
     * @var array
     */
    protected $bind = [
        'app' => App::class,
        'cache' => Cache::class,
        'config' => Config::class,
        'console' => Console::class,
        'controller' => Controller::class,
        'cookie' => Cookie::class,
        'db' => Db::class,
        'event' => Event::class,
        'exception' => Exception::class,
        'file' => File::class,
        'http' => Http::class,
        'log' => Log::class,
        'middleware' => Middleware::class,
        'model' => Model::class,
        'request' => Request::class,
        'response' => Response::class,
        'route' => Route::class,
        'service' => Service::class,
        'session' => Session::class,
        'validate' => Validate::class,
        'view' => View::class,
        'template' => __NAMESPACE__ . '\\contract\\TemplateHandlerInterface',
    ];

    /**
     * 构造方法
     */
    public function __construct()
    {
        $providerFile = CONFIG_PATH . 'provider' . EXT;
        if (is_file($providerFile)) {
            $this->bind(include_once $providerFile);
        }

        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance('lfly\Container', $this);

        //加载全局配置
        $this->loadDefaultConfig();
        //注册事件
        $this->event->loadFile();
        //监听AppInit事件
        $this->event->trigger('AppInit', $this);
        //注册服务
        $this->service->loadFile();
        //启动服务
        $this->service->boot();
    }

    /**
     * 加载默认配置
     * @return void
     */
    public function loadDefaultConfig()
    {
        $config = $this->config;
        $this->debug($config->get('debug', false));
        if ($this->isDebug()) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }
        date_default_timezone_set($config->get('default_timezone', 'Asia/Shanghai'));
    }

    /**
     * 开启应用调试模式
     * @param bool $debug 开启应用调试模式
     * @return $this
     */
    public function debug($debug = true)
    {
        $this->appDebug = $debug;
        return $this;
    }

    /**
     * 是否为调试模式
     * @return bool
     */
    public function isDebug()
    {
        return $this->appDebug;
    }

    /**
     * 解析应用类的类名
     * @param string $name  类名
     * @param string $layer 层名 controller、model等
     * @return string
     */
    public function parseClass($name, $layer = 'controller')
    {
        $name = str_replace(['/', '.'], '\\', $name);
        if (false === strpos($name, '\\') || substr($name, 0, strlen($this->namespace) + 1) != $this->namespace . '\\') {
            return $this->namespace . '\\' . $layer . '\\' . $name;
        }
        return $name;
    }

    /**
     * 类名转文件名剥去固定命名空间和文件后缀
     * @param string $name  类名
     * @param string $layer 层名 controller、model等
     * @return string
     */
    public function stripClass($name, $layer = 'controller')
    {
        return str_replace('\\', DS, preg_replace('/^' . $this->namespace . '\\\\' . $layer . '\\\\?/i', '', $name));
    }

    /**
     * 解析应用类的类名和方法
     * @param string $name  类名@方法
     * @param string $layer 层名 controller、model等
     * @return array
     */
    public function parseClassAndAction($name, $layer = 'controller')
    {
        $nameArray = explode('@', $name);
        if (count($nameArray) == 2) {
            return [$this->parseClass($nameArray[0], $layer), $nameArray[1]];
        }
        return [];
    }

    /**
     * 是否运行在CLI模式
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }

    /**
     * 是否运行在WIN系统下
     * @return bool
     */
    public function runningInWindows()
    {
        return strpos(PHP_OS, 'WIN') !== false;
    }

    /**
     * 生成当前请求的唯一id
     * @return string
     */
    public function uniqid()
    {
        if (empty($this->uniqid)) {
            $this->uniqid = md5(uniqid(mt_rand(), true));
        }
        return $this->uniqid;
    }

    /**
     * 计算此刻运行时间和内存
     * @return array ['time'=>'运行时间', 'memory'=>'内存']
     */
    public function runtimeInfo()
    {
        $return = [];
        $return['time'] = number_format(microtime(true) - LFLY_START_TIME, 6);
        $return['memory'] = $this->validate->convFileSize(memory_get_usage() - LFLY_START_MEMORY);
        return $return;
    }
}
