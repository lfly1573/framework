<?php

class App
{

    /**
     * 构造方法
     */
    public function __construct() {}

    /**
     * 加载默认配置
     * @return void
     */
    public static function loadDefaultConfig() {}

    /**
     * 开启应用调试模式
     * @param bool $debug 开启应用调试模式
     * @return $this
     */
    public static function debug($debug = true) {}

    /**
     * 是否为调试模式
     * @return bool
     */
    public static function isDebug() {}

    /**
     * 解析应用类的类名
     * @param string $name  类名
     * @param string $layer 层名 controller、model等
     * @return string
     */
    public static function parseClass($name, $layer = 'controller') {}

    /**
     * 类名转文件名剥去固定命名空间和文件后缀
     * @param string $name  类名
     * @param string $layer 层名 controller、model等
     * @return string
     */
    public static function stripClass($name, $layer = 'controller') {}

    /**
     * 解析应用类的类名和方法
     * @param string $name  类名@方法
     * @param string $layer 层名 controller、model等
     * @return array
     */
    public static function parseClassAndAction($name, $layer = 'controller') {}

    /**
     * 是否运行在CLI模式
     * @return bool
     */
    public static function runningInConsole() {}

    /**
     * 是否运行在WIN系统下
     * @return bool
     */
    public static function runningInWindows() {}

    /**
     * 生成当前请求的唯一id
     * @return string
     */
    public static function uniqid() {}

    /**
     * 计算此刻运行时间和内存
     * @return array ['time'=>'运行时间', 'memory'=>'内存']
     */
    public static function runtimeInfo() {}

    /**
     * 静态获取当前容器的实例
     * @return static
     */
    public static function getInstance() {}

    /**
     * 静态设置当前容器的实例
     * @param object|Closure $instance 类或匿名函数
     * @return void
     */
    public static function setInstance($instance) {}

    /**
     * 静态获取容器中的对象实例
     * @param string     $abstract    类名或者标识
     * @param array|true $vars        变量
     * @param bool       $newInstance 是否每次创建新的实例
     * @return object
     */
    public static function pull($abstract, $vars = [], $newInstance = false) {}

    /**
     * 根据标识获取真实类名
     * @param  string $abstract  类名或者标识
     * @param  bool   $recursion 是否递归查询
     * @return string
     */
    public static function getAlias($abstract, $recursion = true) {}

    /**
     * 绑定一个类、闭包、实例、接口实现到容器
     * @param string|array $abstract 类标识、接口
     * @param mixed        $concrete 要绑定的类、闭包或者实例
     * @return $this
     */
    public static function bind($abstract, $concrete = null) {}

    /**
     * 创建类的实例
     * @param string $abstract    类名或者标识
     * @param array  $vars        变量
     * @param bool   $newInstance 是否每次创建新的实例
     * @return mixed
     */
    public static function make($abstract, $vars = [], $newInstance = false) {}

    /**
     * 绑定一个类实例到容器
     * @param string $abstract 类名或者标识
     * @param object $instance 类的实例
     * @return $this
     */
    public static function instance($abstract, $instance) {}

    /**
     * 判断容器中是否存在类及标识
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public static function bound($abstract) {}

    /**
     * 判断容器中是否存在类及标识 PSR-11(精简版未继承ContainerInterface)
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public static function has($abstract) {}

    /**
     * 获取容器中的对象实例 PSR-11(精简版未继承ContainerInterface)
     * @param string $abstract 类名或者标识
     * @return object
     * 
     * @throws LogicException
     */
    public static function get($abstract) {}

    /**
     * 判断容器中是否存在对象实例
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public static function exists($abstract) {}

    /**
     * 删除容器中的对象实例
     * @param string $abstract 类名或者标识
     * @return void
     */
    public static function delete($abstract) {}

    /**
     * 调用反射执行函数或者闭包方法
     * @param string|Closure $function 函数或者闭包
     * @param array          $vars     参数
     * @return mixed
     * 
     * @throws LogicException
     */
    public static function invokeFunction($function, $vars = []) {}

    /**
     * 调用反射执行类的实例化
     * @param string $class 类名
     * @param array  $vars  参数
     * @return mixed
     * 
     * @throws LogicException
     */
    public static function invokeClass($class, $vars = []) {}

    /**
     * 调用反射执行函数或者方法
     * @param mixed $callable   函数或者方法
     * @param array $vars       参数
     * @param bool  $accessible 设置是否可访问
     * @return mixed
     */
    public static function invoke($callable, $vars = [], $accessible = false) {}

    /**
     * 调用反射执行类的方法
     * @param mixed $method     方法(数组或者字符)
     * @param array $vars       参数
     * @param bool  $accessible 设置是否可访问
     * @return mixed
     * 
     * @throws LogicException
     */
    public static function invokeMethod($method, $vars = [], $accessible = false) {}

    /**
     * 调用反射执行类的方法(直接传入ReflectionMethod类)
     * @param object            $instance 对象实例
     * @param ReflectionMethod  $reflect  反射类
     * @param array             $vars     参数
     * @return mixed
     */
    public static function invokeReflectMethod($instance, $reflect, $vars = []) {}

    /**
     * 注册一个容器对象实例化后回调函数
     * @param string|Closure $abstract 类名或者标识或者匿名函数
     * @param Closure|null   $callback 绑定到指定类的回调匿名函数 参数(当前对象,当前容器)
     * @return void
     */
    public static function resolving($abstract, $callback = null) {}

    /**
     * ArrayAccess
     */
    public static function offsetExists($key) {}

    
    public static function offsetGet($key) {}

    
    public static function offsetSet($key, $value) {}

    
    public static function offsetUnset($key) {}

    /**
     * Countable
     * count(obj)
     */
    public static function count() {}

    /**
     * IteratorAggregate
     * foreach(obj as $key => $value)
     */
    public static function getIterator() {}

}


class Cache
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * Facade初始化函数
     */
    public static function facade() {}

    /**
     * 获取和设置引擎
     * @param string $name 缓存类型
     * @return $this
     * 
     * @throws \InvalidArgumentException
     */
    public static function engine($name = null) {}

    /**
     * 获取当前引擎类型
     */
    public static function type() {}

    /**
     * 获取对象句柄
     */
    public static function handler() {}

    /**
     * 是否存在
     * @param  string $key 名称
     * @return bool
     */
    public static function has($key) {}

    /**
     * 取值并删除
     * @param  string  $key     名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public static function pull($key, $default = null) {}

    /**
     * 追加一个缓存数据
     * @param  string $key      名称
     * @param  mixed  $value    值
     * @param  string $paramKey 值下标
     * @return bool
     */
    public static function push($key, $value, $paramKey = '', $ttl = null) {}

    /**
     * 获取缓存数组中单个数据
     * @param  string $key      名称
     * @param  string $paramKey 值下标
     * @return bool
     */
    public static function getItem($key, $paramKey, $default = null) {}

    /**
     * 不存在写入返回
     * @param  string $key   名称
     * @param  mixed  $value 值
     * @return mixed
     */
    public static function remember($key, $value, $ttl = null) {}

    /**
     * 读取
     * @param  string  $key     名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public static function get($key, $default = null) {}

    /**
     * 写入
     * @param string       $key   名称
     * @param mixed        $value 存储数据
     * @param int|DateTime $ttl   有效时间 0为永久
     * @return bool
     */
    public static function set($key, $value, $ttl = null) {}

    /**
     * 自增缓存（针对数值缓存）
     * @param  string    $key  名称
     * @param  int       $step 步长
     * @return false|int
     */
    public static function inc($key, $step = 1) {}

    /**
     * 自减缓存（针对数值缓存）
     * @param  string    $key  名称
     * @param  int       $step 步长
     * @return false|int
     */
    public static function dec($key, $step = 1) {}

    /**
     * 删除
     * @param  string $key 名称
     * @return bool
     */
    public static function delete($key) {}

    /**
     * 清空
     * @return void
     */
    public static function clear() {}

    /**
     * 写入缓存文件
     * @param string $file      缓存文件
     * @param mixed  $cacheData 缓存数据
     * @param bool   $append    是否在原文件末尾追加内容
     * @return bool
     */
    public static function writeFile($file, $cacheData, $append = false) {}

    /**
     * php数组格式化
     */
    public static function getCacheVarsFormat($array, $level = 0) {}

    /**
     * php闭包格式化
     */
    public static function getCacheClosureFormat($func) {}

}


class Config
{

    /**
     * 构造方法 默认载入app的配置信息
     */
    public function __construct() {}

    /**
     * 载入配置文件
     * @param  string $file 配置文件名
     * @return array
     */
    public static function loadFile($file) {}

    /**
     * 检测配置是否存在
     * @param  string $name 配置参数名
     * @return bool
     */
    public static function has($name) {}

    /**
     * 获取配置参数
     * @param  string $name    配置参数名（支持多级配置 .号分割）
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public static function get($name = null, $default = null) {}

    /**
     * 设置配置参数
     * @param  array $config 配置参数
     * @return array
     */
    public static function set($config) {}

}


class Console
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 获取时间信息
     */
    public static function getTimeInfo() {}

    /**
     * 获取执行服务器ip
     */
    public static function getIp() {}

    /**
     * 获取参数
     */
    public static function getParam() {}

    /**
     * 执行应用程序
     */
    public static function run() {}

    
    public function __destruct() {}

}


class Cookie
{

    /**
     * 构造函数
     * @param \lfly\Config $obj 配置类
     */
    public function __construct($obj) {}

    /**
     * 是否存在Cookie参数
     * @param  string $name 名称
     * @return bool
     */
    public static function has($name) {}

    /**
     * 获取cookie
     * @param  string  $name    名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public static function get($name, $default = null) {}

    /**
     * 获取全部cookie
     * @return array
     */
    public static function getAll() {}

    /**
     * Cookie设置
     * @param  string                $name     名称
     * @param  mixed                 $value    内容
     * @param  int|DateTimeInterface $life     有效秒数
     * @param  bool                  $httponly 仅可通过HTTP协议访问
     * @param  bool                  $prefix   是否包含前缀
     * @return bool
     */
    public static function set($name, $value, $life = 0, $httponly = false, $prefix = true) {}

    /**
     * Cookie删除
     * @param  string $name   名称
     * @param  bool   $prefix 是否包含前缀
     * @return bool
     */
    public static function delete($name, $prefix = true) {}

}


class Db
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 创建/切换数据库
     * @param string|null $name  连接名称
     * @param bool        $force 强制重新连接
     * @return \lfly\db\Query
     */
    public static function connect($name = null, $force = false) {}

    /**
     * 更新查询次数
     * @return void
     */
    public static function updateQueryTimes() {}

    /**
     * 重置查询次数
     * @return void
     */
    public static function clearQueryTimes() {}

    /**
     * 获得查询次数
     * @return int
     */
    public static function getQueryTimes() {}

    /**
     * 设置日志
     * @param string       $op   操作
     * @param string|array $info 内容
     * @param string       $type 类型
     * @return void
     */
    public static function setLog($op, $info = '', $type = 'db') {}

    /**
     * 获取日志
     * @return array
     */
    public static function getLog() {}

    /**
     * 触发事件
     * @param string $event  事件名称
     * @param mixed  $params 传入参数
     * @param bool   $once   只获取一个有效返回值
     * @return mixed
     */
    public static function trigger($event, $params = null, $once = false) {}

    /**
     * 创建模型类
     * @param string $model  模型名称
     * @return object
     */
    public static function invokeModel($model) {}

}


class Event
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 设置是否开启事件响应
     * @param bool $status 是否需要事件响应
     * @return $this
     */
    public static function withEvent($status) {}

    /**
     * 载入配置
     * @param string $file 文件路径
     * @return $this
     */
    public static function loadFile($file = null) {}

    /**
     * 批量注册事件监听
     * @param array $events 事件定义
     * @return $this
     */
    public static function listenEvents($events) {}

    /**
     * 注册事件监听
     * @param string $event    事件名称
     * @param mixed  $listener 监听操作（或者类名）
     * @param bool   $first    是否优先执行
     * @return $this
     */
    public static function listen($event, $listener, $first = false) {}

    /**
     * 注册事件订阅者
     * @param mixed $subscriber 订阅者
     * @return $this
     */
    public static function subscribe($subscriber) {}

    /**
     * 自动注册事件观察者
     * @param string|object $observer 观察者
     * @return $this
     */
    public static function observe($observer) {}

    /**
     * 触发事件
     * @param string $event  事件名称
     * @param mixed  $params 传入参数
     * @param bool   $once   只获取一个有效返回值
     * @return mixed
     */
    public static function trigger($event, $params = null, $once = false) {}

    /**
     * 触发事件(只获取一个有效返回值)
     * @param string $event  事件名称
     * @param mixed  $params 传入参数
     * @return mixed
     */
    public static function until($event, $params = null) {}

    /**
     * 是否存在事件监听
     * @param string $event 事件名称
     * @return bool
     */
    public static function hasListener($event) {}

    /**
     * 移除事件监听
     * @param string $event 事件名称
     * @return $this
     */
    public static function remove($event) {}

}


class File
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 设置上传文件
     * @param string $inputName 类名
     * @param string $config    上传配置
     * @return $this
     */
    public static function init($inputName, $config = null) {}

    /**
     * 初始化引擎
     * @param string $engine 文件类型
     * @return \lfly\contract\FileHandlerInterface
     * 
     * @throws InvalidArgumentException
     */
    public static function engine($engine) {}

    /**
     * 获取临时引擎
     * @return string
     */
    public static function getEngine() {}

    /**
     * 获取检测结果
     * @return bool
     */
    public static function getResult() {}

    /**
     * 获取错误信息
     * @return string
     */
    public static function getError() {}

    /**
     * 获取上传文件个数
     * @return int
     */
    public static function getNum() {}

    /**
     * 获取上传文件数组
     * @return array
     */
    public static function getFile() {}

    /**
     * 自动保存文件
     * @param string $filePath  额外文件路径
     * @param bool   $isOldName 是否加入旧文件名
     * @return array
     */
    public static function save($filePath = '', $isOldName = false) {}

    /**
     * 设定引擎保存文件
     * @param string $engine    设置保存引擎
     * @param string $filePath  额外文件路径
     * @param bool   $isOldName 是否加入旧文件名
     * @return array
     */
    public static function saveAs($engine, $filePath = '', $isOldName = false) {}

    /**
     * 本地保存文件
     * @param  string $fromFile 原始文件
     * @param  string $toFile   新文件路径
     * @return string|array
     */
    public static function putFile($fromFile, $toFile) {}

    /**
     * 删除上传的文件
     * @param  string $file 完整文件
     * @return bool
     */
    public static function delFile($file = null) {}

    /**
     * 获取文件后缀
     */
    public static function getExt($file) {}

    /**
     * 生成新文件名
     */
    public static function newFilename($file) {}

    /**
     * 生成新文件夹名
     */
    public static function newFolder($strMode = 'Ym') {}

}


class Log
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 设置引擎
     * @param string $name 类型
     * @return $this
     */
    public static function engine($name) {}

    /**
     * 保存全部日志
     * @return void
     */
    public static function save() {}

    /**
     * 单独保存日志
     * @param array $log 日志二维数组 [['time'=>'时间戳', 'usec'=>'微秒小数', 'uuid'=>'一个连续请求的id', 'type'=>'类型', 'info'=>'日志内容']]
     * @return void
     */
    public static function saveLog($log = []) {}

    /**
     * 格式化日志
     * @param mixed  $msg  日志信息
     * @param string $type 日志级别
     * @return array
     */
    public static function formatLog($msg, $type) {}

    /**
     * 记录日志信息
     * @param mixed  $msg     日志信息
     * @param string $type    日志级别
     * @param array  $context 替换内容
     * @param bool   $lazy    是否内存记录
     * @return $this
     */
    public static function record($msg, $type = 'info', $context = [], $lazy = true) {}

    /**
     * 实时写入日志信息
     * @param mixed  $msg     调试信息
     * @param string $type    日志级别
     * @param array  $context 替换内容
     * @return $this
     */
    public static function write($msg, $type = 'info', $context = []) {}

    /**
     * 记录日志信息
     * @param string $level   日志级别
     * @param mixed  $message 日志信息
     * @param array  $context 替换内容
     * @return void
     */
    public static function log($level, $message, $context = []) {}

    /**
     * 记录emergency信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function emergency($message, $context = []) {}

    /**
     * 记录警报信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function alert($message, $context = []) {}

    /**
     * 记录紧急情况
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function critical($message, $context = []) {}

    /**
     * 记录错误信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function error($message, $context = []) {}

    /**
     * 记录warning信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function warning($message, $context = []) {}

    /**
     * 记录notice信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function notice($message, $context = []) {}

    /**
     * 记录一般信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function info($message, $context = []) {}

    /**
     * 记录调试信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function debug($message, $context = []) {}

    /**
     * 记录数据库信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function db($message, $context = []) {}

    /**
     * 记录数据库查询缓慢信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public static function querySlow($message, $context = []) {}

}


class Middleware
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 载入默认配置
     * @param string $file 文件路径
     * @return $this
     */
    public static function loadFile($file = null) {}

    /**
     * 载入全局中间件
     * @return $this
     */
    public static function importDefault() {}

    /**
     * 导入中间件
     * @param array  $middlewares 中间件数组
     * @param string $type        中间件类型
     * @return void
     */
    public static function import($middlewares = [], $type = 'global') {}

    /**
     * 注册中间件
     * @param mixed  $middleware 单个中间件
     * @param string $type       中间件类型
     * @return void
     */
    public static function add($middleware, $type = 'global') {}

    /**
     * 注册路由中间件
     * @param mixed $middleware 单个中间件
     * @return void
     */
    public static function route($middleware) {}

    /**
     * 注册控制器中间件
     * @param mixed $middleware 单个中间件
     * @return void
     */
    public static function controller($middleware) {}

    /**
     * 注册中间件到开始位置
     * @param mixed  $middleware 单个中间件
     * @param string $type       中间件类型
     */
    public static function unshift($middleware, $type = 'global') {}

    /**
     * 获取注册的中间件
     * @param string $type 中间件类型
     * @return array
     */
    public static function all($type = 'global') {}

    /**
     * 调度管道
     * @param string $type 中间件类型
     * @return \lfly\Pipeline
     */
    public static function pipeline($type = 'global') {}

    /**
     * 结束调度
     * @param \lfly\Response $response 输出对象
     */
    public static function end($response) {}

    /**
     * 异常处理
     * @param \lfly\Request  $passable
     * @param \Throwable     $e
     * @return \lfly\Response
     */
    public static function handleException($passable, $e) {}

}


class Request
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 设置或者获取当前的Header
     * @param  string $name     header名称
     * @param  string $default  默认值
     * @return string|array
     */
    public static function header($name = '', $default = null) {}

    /**
     * 获取当前时间
     * @return int
     */
    public static function getTime() {}

    /**
     * 设置包含协议的主域名
     * @param  string $mainDomain 域名
     * @return $this
     */
    public static function setMainDomain($mainDomain) {}

    /**
     * 设置当前包含协议的域名
     * @param  string $domain 域名
     * @return $this
     */
    public static function mainDomain() {}

    /**
     * 获取当前包含协议的域名
     * @param  bool $port 是否需要去除端口号
     * @return string
     */
    public static function domain($port = false) {}

    /**
     * 获取当前根域名
     * @return string
     */
    public static function rootDomain() {}

    /**
     * 设置当前子域名的值
     * @param  string $domain 域名
     * @return $this
     */
    public static function setSubDomain($domain) {}

    /**
     * 获取当前子域名
     * @return string
     */
    public static function subDomain() {}

    /**
     * 设置当前泛域名的值
     * @param  string $domain 域名
     * @return $this
     */
    public static function setPanDomain($domain) {}

    /**
     * 获取当前泛域名的值
     * @return string
     */
    public static function panDomain() {}

    /**
     * 设置当前完整URL 包括QUERY_STRING
     * @param  string $url URL地址
     * @return $this
     */
    public static function setUrl($url) {}

    /**
     * 获取当前完整URL 包括QUERY_STRING
     * @param  bool $complete 是否包含完整域名
     * @return string
     */
    public static function url($complete = false) {}

    /**
     * 设置当前URL 不含QUERY_STRING
     * @param  string $url URL地址
     * @return $this
     */
    public static function setBaseUrl($url) {}

    /**
     * 获取当前URL 不含QUERY_STRING
     * @param  bool $complete 是否包含完整域名
     * @return string
     */
    public static function baseUrl($complete = false) {}

    /**
     * 获取当前执行的文件 SCRIPT_NAME
     * @param  bool $complete 是否包含完整域名
     * @return string
     */
    public static function baseFile($complete = false) {}

    /**
     * 设置URL访问根地址
     * @param  string $url URL地址
     * @return $this
     */
    public static function setRoot($url) {}

    /**
     * 获取URL访问根地址
     * @param  bool $complete 是否包含完整域名
     * @return string
     */
    public static function root($complete = false) {}

    /**
     * 获取构建url的前缀
     * @return string
     */
    public static function pre() {}

    /**
     * 获取附件完整域名地址
     * @return string
     */
    public static function upfileUrl() {}

    /**
     * 获取当前兼容请求的参数变量
     * @return string
     */
    public static function varPathinfo() {}

    /**
     * 设置当前请求的pathinfo
     * @param  string $pathinfo
     * @return $this
     */
    public static function setPathinfo($pathinfo) {}

    /**
     * 获取当前请求URL的pathinfo信息（含URL后缀）
     * @return string
     */
    public static function pathinfo() {}

    /**
     * 获取当前请求URL的pathinfo信息（不含后缀）
     * @return string
     */
    public static function path() {}

    /**
     * 当前URL的访问后缀
     * @return string
     */
    public static function ext() {}

    /**
     * 获取当前请求的时间
     * @param  bool $float 是否使用浮点类型
     * @return integer|float
     */
    public static function time($float = false) {}

    /**
     * 当前请求的资源类型
     * @return string
     */
    public static function type() {}

    /**
     * 设置资源类型
     * @param  string|array $type 资源类型名
     * @param  string       $val  资源类型
     * @return void
     */
    public static function mimeType($type, $val = '') {}

    /**
     * 当前URL地址中的scheme参数
     * @return string
     */
    public static function scheme() {}

    /**
     * 当前请求URL地址中的query参数
     * @return string
     */
    public static function query() {}

    /**
     * 设置当前请求的host（包含端口）
     * @param  string $host 主机名（含端口）
     * @return $this
     */
    public static function setHost($host) {}

    /**
     * 当前请求的host
     * @param bool $strict  true 仅仅获取HOST
     * @return string
     */
    public static function host($strict = false) {}

    /**
     * 当前请求URL地址中的port参数
     * @return int
     */
    public static function port() {}

    /**
     * 当前请求 HTTP_CONTENT_TYPE
     * @return string
     */
    public static function contentType() {}

    /**
     * 当前请求 SERVER_PROTOCOL
     * @return string
     */
    public static function protocol() {}

    /**
     * 当前请求 REMOTE_PORT
     * @return int
     */
    public static function remotePort() {}

    /**
     * 设置请求类型
     * @param  string $method 请求类型
     * @return $this
     */
    public static function setMethod($method) {}

    /**
     * 当前的请求类型
     * @param  bool $origin 是否获取原始请求类型
     * @return string
     */
    public static function method($origin = false) {}

    /**
     * 来自地址 HTTP_REFERER
     * @param bool $checkSelf 判断是否来自本站根域名的地址
     * @return string
     */
    public static function comeUrl($checkSelf = false) {}

    /**
     * 生成请求令牌
     * @param int    $type 获取类型 0:直接获取 1:获取整个同名input 2:获取meta 3:获取js运算表达式
     * @param string $name 令牌名称
     * @return string
     */
    public static function buildToken($type = 0, $name = null) {}

    /**
     * 检查请求令牌
     * @param string $value 当前令牌值
     * @param bool   $isDel 校验后是否删除
     * @param string $name  令牌名称
     * @return string
     */
    public static function checkToken($value = null, $isDel = false, $name = null) {}

    /**
     * 删除请求令牌
     * @param string $name 令牌名称
     * @return void
     */
    public static function deleteToken($name = null) {}

    /**
     * 生成随机字符
     * @param int $length 长度
     * @param int $type 类型 0:数字字母 5:验证码字符 10:纯数字 16:仅16进制字符
     * @return string
     */
    public static function random($length, $type = 0) {}

    /**
     * 是否为GET请求
     * @return bool
     */
    public static function isGet() {}

    /**
     * 是否为POST请求
     * @return bool
     */
    public static function isPost() {}

    /**
     * 是否为PUT请求
     * @return bool
     */
    public static function isPut() {}

    /**
     * 是否为DELTE请求
     * @return bool
     */
    public static function isDelete() {}

    /**
     * 是否为HEAD请求
     * @return bool
     */
    public static function isHead() {}

    /**
     * 是否为PATCH请求
     * @return bool
     */
    public static function isPatch() {}

    /**
     * 是否为OPTIONS请求
     * @return bool
     */
    public static function isOptions() {}

    /**
     * 是否为cli
     * @return bool
     */
    public static function isCli() {}

    /**
     * 是否为cgi
     * @return bool
     */
    public static function isCgi() {}

    /**
     * 设置路由变量
     * @param  array $route 路由变量
     * @return $this
     */
    public static function setRoute($route) {}

    /**
     * 获取当前请求的php://input
     * @return string
     */
    public static function getInput() {}

    /**
     * 获取当前请求的参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public static function param($name = '', $default = null, $filter = '') {}

    /**
     * 获取路由参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public static function route($name = '', $default = null, $filter = '') {}

    /**
     * 获取GET参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public static function get($name = '', $default = null, $filter = '') {}

    /**
     * 获取POST参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public static function post($name = '', $default = null, $filter = '') {}

    /**
     * 获取PUT参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public static function put($name = '', $default = null, $filter = '') {}

    /**
     * 设置获取DELETE参数
     * @param  mixed        $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public static function delete($name = '', $default = null, $filter = '') {}

    /**
     * 设置获取PATCH参数
     * @param  mixed        $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public static function patch($name = '', $default = null, $filter = '') {}

    /**
     * 获取request变量
     * @param  string|array $name    数据名称
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public static function request($name = '', $default = null, $filter = '') {}

    /**
     * 获取中间件传递的参数
     * @param  mixed $name    变量名
     * @param  mixed $default 默认值
     * @return mixed
     */
    public static function middleware($name, $default = null) {}

    /**
     * 获取server参数
     * @param  string $name    数据名称
     * @param  string $default 默认值
     * @return mixed
     */
    public static function server($name = '', $default = '') {}

    /**
     * 是否存在某个请求参数
     * @param  string $name 变量名
     * @param  string $type 变量类型
     * @param  bool   $checkEmpty 是否检测空值
     * @return bool
     */
    public static function has($name, $type = 'param', $checkEmpty = false) {}

    /**
     * 获取变量 支持过滤和默认值
     * @param  array        $data    数据源
     * @param  string|false $name    字段名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤函数
     * @return mixed
     */
    public static function input($data = [], $name = '', $default = null, $filter = '') {}

    /**
     * 获取指定的参数
     * @param  array        $name 变量名
     * @param  mixed        $data 数据或者变量类型
     * @param  string|array $filter 过滤方法
     * @return array
     */
    public static function only($name, $data = 'param', $filter = '') {}

    /**
     * 排除指定参数获取
     * @param  array  $name 变量名
     * @param  string $type 变量类型
     * @return mixed
     */
    public static function except($name, $type = 'param') {}

    /**
     * 设置或获取当前的过滤规则
     * @access public
     * @param  mixed $filter 过滤规则
     * @return mixed
     */
    public static function filter($filter = null) {}

    /**
     * 递归过滤给定的值
     * @param  mixed $value 键值
     * @param  mixed $key 键名
     * @param  array $filters 过滤方法+默认值
     * @return mixed
     */
    public static function filterValue($value, $key, $filters) {}

    /**
     * 当前是否ssl
     * @return bool
     */
    public static function isSsl() {}

    /**
     * 当前是否JSON请求
     * @return bool
     */
    public static function isJson() {}

    /**
     * 当前是否Ajax请求
     * @param  bool $ajax true 获取原始ajax请求
     * @return bool
     */
    public static function isAjax($ajax = false) {}

    /**
     * 当前是否Pjax请求
     * @param  bool $pjax true 获取原始pjax请求
     * @return bool
     */
    public static function isPjax($pjax = false) {}

    /**
     * 设置代理IP地址
     * @param array|string $ip 代理ip地址
     * @return string
     */
    public static function setProxyServerIp($ip) {}

    /**
     * 获取客户端IP地址
     * @return string
     */
    public static function ip() {}

    /**
     * 检测是否是合法的IP地址
     * @param string $ip   IP地址
     * @param string $type IP地址类型 (ipv4, ipv6)
     * @return boolean
     */
    public static function isValidIP($ip, $type = '') {}

    /**
     * 将IP地址转换为二进制字符串
     * @param string $ip ip地址
     * @return string
     */
    public static function ip2bin($ip) {}

    /**
     * 检测是否使用手机访问
     * @param  string $type 手机类型
     * @return bool
     */
    public static function isMobile($type = '') {}

    /**
     * 设置当前的控制器名
     * @param  string $controller 控制器名
     * @return $this
     */
    public static function setController($controller) {}

    /**
     * 设置当前的操作名
     * @param  string $action 操作名
     * @return $this
     */
    public static function setAction($action) {}

    /**
     * 获取当前的控制器名
     * @access public
     * @param  bool $convert 转换为小写
     * @return string
     */
    public static function controller($convert = false) {}

    /**
     * 获取当前的操作名
     * @param  bool $convert 转换为小写
     * @return string
     */
    public static function action($convert = false) {}

    /**
     * 设置在中间件传递的数据
     * @param  array $middleware 数据
     * @return $this
     */
    public static function withMiddleware($middleware) {}

    /**
     * 设置GET数据
     * @param  array $get 数据
     * @param  bool $all  是否全部替换
     * @return $this
     */
    public static function withGet($get, $all = false) {}

    /**
     * 设置POST数据
     * @param  array $post 数据
     * @param  bool  $all  是否全部替换
     * @return $this
     */
    public static function withPost($post, $all = false) {}

    /**
     * 设置SERVER数据
     * @param  array $server 数据
     * @param  bool  $all    是否全部替换
     * @return $this
     */
    public static function withServer($server, $all = false) {}

    /**
     * 设置HEADER数据
     * @param  array $header 数据
     * @param  bool  $all    是否全部替换
     * @return $this
     */
    public static function withHeader($header, $all = false) {}

    /**
     * 设置php://input数据
     * @param string $input RAW数据
     * @return $this
     */
    public static function withInput($input) {}

    /**
     * 设置ROUTE变量
     * @param  array $route 数据
     * @param  bool $all  是否全部替换
     * @return $this
     */
    public static function withRoute($route, $all = false) {}

}


class Response
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 切换引擎(默认是html,切换需要最先调用)
     * @param  string  $type 输出类名
     * @return \lfly\Response
     *
     * @throws InvalidArgumentException
     */
    public static function engine($type) {}

    /**
     * 初始化
     * @param  mixed  $data 输出数据
     * @param  int    $code 状态码
     * @return $this
     */
    public static function init($data = '', $code = 200) {}

    /**
     * 设置模版
     * @param  string $template 模版名称
     * @return $this
     */
    public static function setTemplate($template = '') {}

    /**
     * 发送数据到客户端
     * @return void
     * 
     * @throws InvalidArgumentException
     */
    public static function send() {}

    /**
     * 获取输出数据
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    public static function getContent() {}

    /**
     * 设置显示数据
     * @param  string $content 输出数据
     * @return $this
     */
    public static function setContent($content) {}

    /**
     * 设置输出数据
     * @param  mixed $data 输出数据
     * @return $this
     */
    public static function setData($data) {}

    /**
     * 设置HTTP状态
     * @param  integer $code 状态码
     * @return $this
     */
    public static function setCode($code) {}

    /**
     * LastModified
     * @param  string $time
     * @return $this
     */
    public static function lastModified($time) {}

    /**
     * Expires
     * @param  string $time
     * @return $this
     */
    public static function expires($time) {}

    /**
     * ETag
     * @param  string $eTag
     * @return $this
     */
    public static function eTag($eTag) {}

    /**
     * 页面缓存控制
     * @param  string $cache 状态码
     * @return $this
     */
    public static function cacheControl($cache) {}

    /**
     * 页面输出类型
     * @param  string $contentType 输出类型
     * @param  string $charset     输出编码
     * @return $this
     */
    public static function contentType($contentType, $charset = 'utf-8') {}

    /**
     * 设置响应头
     * @param  array $header  参数
     * @return $this
     */
    public static function header($header = []) {}

    /**
     * 获取头部信息
     * @param  string $name 头部名称
     * @return mixed
     */
    public static function getHeader($name = '') {}

    /**
     * 输出的参数
     * @param  mixed $options 输出参数
     * @return $this
     */
    public static function options($options = []) {}

    /**
     * 获取原始数据
     * @return mixed
     */
    public static function getData() {}

    /**
     * 获取状态码
     * @return integer
     */
    public static function getCode() {}

    /**
     * 获取当前引擎
     * @return string
     */
    public static function getEngine() {}

    /**
     * 调试输出并中止
     * @return $this
     */
    public static function halt($data, $type = '') {}

}


class Route
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 获取当前参数
     * @return array
     */
    public static function getAttr() {}

    /**
     * 路由组设定
     * @param callable $callback 匿名函数
     * @return void
     */
    public static function group($callback) {}

    /**
     * get路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public static function get($uri, $callback) {}

    /**
     * post路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public static function post($uri, $callback) {}

    /**
     * put路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public static function put($uri, $callback) {}

    /**
     * patch路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public static function patch($uri, $callback) {}

    /**
     * delete路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public static function delete($uri, $callback) {}

    /**
     * options路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public static function options($uri, $callback) {}

    /**
     * 全匹配路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public static function any($uri, $callback) {}

    /**
     * 部分匹配路由
     * @param string          $uri         url地址格式
     * @param string|callable $callback    对应控制器或匿名函数
     * @param array           $methodArray 多个请求类型
     * @return void
     */
    public static function match($uri, $callback, $methodArray) {}

    /**
     * 跳转路由
     * @param string $uri   url地址格式
     * @param string $goUrl 跳转的地址
     * @return void
     */
    public static function redirect($uri, $goUrl) {}

    /**
     * 视图路由
     * @param string $uri      url地址格式
     * @param string $template 模版文件
     * @return void
     */
    public static function view($uri, $templateName) {}

    /**
     * 资源路由
     * @param string $uri        url地址格式
     * @param string $controller 控制器
     * @return void
     */
    public static function resource($uri, $controller) {}

    /**
     * 回退路由
     * @param string|callable $callback 对应控制器或匿名函数或指定标识(auto)
     * @return void
     */
    public static function fallback($callback) {}

    /**
     * 设置域名
     * @param string $domain 绑定域名
     * @return $this
     */
    public static function domain($domain) {}

    /**
     * 设置变量格式
     * @param array $args 地址中变量格式正则定义
     * @return $this
     */
    public static function where($args) {}

    /**
     * 设置路由中间件
     * @param array $args 路由中间件
     * @return $this
     */
    public static function middleware($args) {}

    /**
     * 设置额外参数
     * @param array $args 在route参数中增加额外参数
     * @return $this
     */
    public static function append($args) {}

    /**
     * 设置命名空间
     * @param string $namespace 命名空间前缀
     * @return $this
     */
    public static function namespace($namespace) {}

    /**
     * 设置uri前缀
     * @param string $prefix uri前缀
     * @return $this
     */
    public static function prefix($prefix) {}

    /**
     * 设置路由别名
     * @param string $name 路由别名
     * @return $this
     */
    public static function name($name) {}

    /**
     * 设置是否强制https
     * @return $this
     */
    public static function https() {}

    /**
     * 设置url扩展名后缀
     * @param string|array $ext 后缀名
     * @return $this
     */
    public static function ext($ext) {}

    /**
     * path解析
     * @param \lfly\Request $request 请求参数
     * @return array
     */
    public static function dispatch($request) {}

    /**
     * 生成url
     * @param string $name 控制器或别名
     * @param array  $args 传入参数
     * @return string
     */
    public static function buildUrl($name, $args = []) {}

    /**
     * 添加路由规则
     * @param string            $uri        url地址格式
     * @param string|callable   $callback   对应控制器或匿名函数
     * @param array|\lfly\Route $attributes 设置参数
     * @param array             $method     请求类型
     * @return string
     */
    public static function addRule($uri, $callback, $attributes, $method = []) {}

    /**
     * 执行路由解析
     */
    public static function loadFile() {}

    /**
     * 压入组参数
     * @param \lfly\Route $groupObj 组对象
     * @param callable    $callback 回调函数
     * @return array
     */
    public static function updateGroupStack($groupObj, $callback) {}

    /**
     * 添加额外路由
     * @param Closure $func 闭包函数
     * @return void
     */
    public static function extendRule($func) {}

    /**
     * 设置未匹配的默认操作
     * @param string|callable $callback 回调函数
     * @param \lfly\Route     $routeObj 调用的route类
     * @return array
     */
    public static function setDefault($callback, $routeObj = null) {}

}


class Session
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 获取和设置引擎
     * @param string $class 引擎类
     * @return \lfly\contract\SessionHandlerInterface
     */
    public static function engine($class = null) {}

    /**
     * 获取会话id
     * @return string
     */
    public static function getid() {}

    /**
     * 是否存在
     * @param  string $name 名称
     * @return bool
     */
    public static function has($name) {}

    /**
     * 取值并删除
     * @param  string  $name    名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public static function pull($name, $default = null) {}

    /**
     * 获取单个
     * @param  string  $name    名称
     * @param  mixed   $default 默认值
     * @return mixed
     */
    public static function get($name, $default = null) {}

    /**
     * 获取全部
     * @return array
     */
    public static function getAll() {}

    /**
     * 设置
     * @param  string|array $name  名称
     * @param  mixed        $value 内容
     * @return bool
     */
    public static function set($name, $value) {}

    /**
     * 删除
     * @param  string|array $name 名称
     * @return bool
     */
    public static function delete($name) {}

    /**
     * 清空
     * @return void
     */
    public static function clear() {}

    /**
     * 关闭
     * @return void
     */
    public static function close() {}

}


class Validate
{

    /**
     * 构造方法
     */
    public function __construct() {}

    /**
     * 添加扩展验证 静态调用
     * @param Closure $maker 格式如 function(验证对象实例) {}
     * @return void
     */
    public static function maker($maker) {}

    /**
     * 载入验证模型
     * @param string $class 验证类
     * @return object
     */
    public static function load($class) {}

    /**
     * 添加字段验证规则
     * @param string|array $name 字段名称或者规则数组
     * @param mixed        $rule 验证规则
     * @return $this
     */
    public static function rule($name, $rule = '') {}

    /**
     * 设置验证场景
     * @param string $name 场景名
     * @return $this
     */
    public static function scene($name) {}

    /**
     * 设置批量验证
     * @param bool $batch 是否批量验证
     * @return $this
     */
    public static function batch($batch = true) {}

    /**
     * 数据验证
     * @param array $data  数据
     * @param array $rules 验证规则
     * @return $this
     */
    public static function check($data, $rules = []) {}

    /**
     * Request数据验证
     * @param array $rules 验证规则
     * @return $this
     */
    public static function checkRequest($rules = []) {}

    /**
     * 获取检测结果
     * @return bool
     */
    public static function getResult() {}

    /**
     * 获取错误信息
     * @return array|string
     */
    public static function getError() {}

    /**
     * 获取原始检测数据
     * @return array
     */
    public static function getOriginalData() {}

    
    public static function getOldData() {}

    /**
     * 获取格式化后数据
     * @return array
     */
    public static function getFormattedData() {}

    
    public static function getNewData() {}

    /**
     * 扩展验证规则类型
     * @param string   $type           验证规则类型
     * @param mixed    $callback       验证的callback方法或正则字符
     * @param string   $message        验证失败提示信息
     * @param callable $formatCallback 验证后格式化callback方法
     * @return $this
     */
    public static function extend($type, $callback = null, $message = null, $formatCallback = null) {}

    /**
     * 添加正则规则
     * @param string $name 名称
     * @param string $rule 规则
     * @return $this
     */
    public static function regex($name, $rule) {}

    /**
     * 设置特别的提示信息
     * @param array $message 错误信息
     * @return $this
     */
    public static function message($message) {}

    /**
     * 设置验证规则的默认提示信息
     * @param string|array $type 验证规则类型名称或者数组
     * @param string       $msg  验证提示信息
     * @return $this
     */
    public static function setTypeMsg($type, $msg = '') {}

    /**
     * 判断类型 email
     * @param string $value 字段值
     * @return bool
     */
    public static function isEmail($value) {}

    /**
     * 判断类型 url
     * @param string $value 字段值
     * @return bool
     */
    public static function isUrl($value) {}

    /**
     * 判断类型 date
     * @param string $value 字段值
     * @param string $sep   分隔符
     * @return bool
     */
    public static function isDate($value, $sep = '-') {}

    /**
     * 判断类型 time
     * @param string $value 字段值
     * @param string $sep   分隔符
     * @return bool
     */
    public static function isTime($value, $sep = ':') {}

    /**
     * 判断类型 dateTime
     * @param string $value 字段值
     * @return bool
     */
    public static function isDateTime($value) {}

    /**
     * 判断类型 version
     * @param string $value 字段值
     * @return bool
     */
    public static function isVersion($value) {}

    /**
     * 判断in
     * @param mixed        $value 字段值
     * @param array|string $rule  验证规则
     * @return bool|string
     */
    public static function builtIn($value, $rule) {}

    /**
     * 判断notIn
     * @param mixed        $value 字段值
     * @param array|string $rule  验证规则
     * @return bool|string
     */
    public static function builtNotIn($value, $rule) {}

    /**
     * 判断between
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public static function builtBetween($value, $rule) {}

    /**
     * 判断notBetween
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public static function builtNotBetween($value, $rule) {}

    /**
     * 判断length
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public static function builtLength($value, $rule) {}

    /**
     * 判断after
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public static function builtAfter($value, $rule) {}

    /**
     * 判断before
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public static function builtBefore($value, $rule) {}

    /**
     * 判断confirm
     * @param mixed  $value 字段值
     * @param mixed  $rule  验证规则
     * @param array  $data  数据
     * @param string $field 字段名
     * @return bool|string
     */
    public static function builtConfirm($value, $rule, $data = [], $field = '') {}

    /**
     * 判断提交令牌
     * @param mixed  $value 字段值
     * @param mixed  $rule  令牌名称
     * @return bool
     */
    public static function builtSubmitToken($value, $rule = null) {}

    /**
     * 判断提交校验参数
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool
     */
    public static function builtCheckToken($value, $rule = '') {}

    /**
     * 判断egt >=
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public static function builtEgt($value, $rule, $data = []) {}

    /**
     * 判断gt >
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public static function builtGt($value, $rule, $data = []) {}

    /**
     * 判断elt <=
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public static function builtElt($value, $rule, $data = []) {}

    /**
     * 判断lt <
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public static function builtLt($value, $rule, $data = []) {}

    /**
     * 判断eq ==
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public static function builtEq($value, $rule, $data = []) {}

    /**
     * 判断ne !=
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public static function builtNe($value, $rule, $data = []) {}

    /**
     * 判断regex
     * @param mixed $value 字段值
     * @param string $rule 验证规则
     * @return bool|string
     */
    public static function builtRegex($value, $rule) {}

    /**
     * 生成校验字符
     * @param string $value 操作
     * @return string
     */
    public static function convCheckToken() {}

    /**
     * 转换图片地址
     * @param string $value 图片地址
     * @return string
     */
    public static function convImageUrl($value, $default = '') {}

    /**
     * 转换 bool
     * @param mixed $value 字段值
     * @return int
     */
    public static function convBool($value) {}

    /**
     * 转换 html
     * @param mixed $value   字段值
     * @param mixed $isSmart 是否智能转换
     * @return int
     */
    public static function convHtml($value, $isSmart = true) {}

    /**
     * 数字时间转换为自定义格式
     * @param int    $timestamp 时间戳
     * @param string $mode      格式化的模式
     * @return string
     */
    public static function convTime($timestamp, $mode = 'FS') {}

    /**
     * 格式化秒数
     * @param int $intsec 秒数
     * @return string
     */
    public static function convSecond($intsec) {}

    /**
     * 格式化文件大小
     * @param int $filesize 文件大小Byte
     * @return string
     */
    public static function convFileSize($filesize) {}

    /**
     * 驼峰转下划线
     * @param  string $value     值
     * @param  string $delimiter 分隔符
     * @return string
     */
    public static function convToSnake($value, $delimiter = '_') {}

    /**
     * 下划线转驼峰
     * @param string $value 值
     * @param bool   $lower 首字母是否小写
     * @return string
     */
    public static function convToCamel($value, $lower = false) {}

}


class View
{

    /**
     * 构造函数
     * @param \lfly\App $app 主容器
     */
    public function __construct($app) {}

    /**
     * 获取和设置模板引擎
     * @param string $class 模板引擎类
     * @return \lfly\contract\TemplateHandlerInterface
     */
    public static function engine($class = null) {}

    /**
     * 模板变量赋值
     * @param string|array $name  模板变量
     * @param mixed        $value 变量值
     * @return $this
     */
    public static function assign($name, $value = null) {}

    /**
     * 视图过滤
     * @param Callable $filter 过滤方法或闭包，参数为模版整体内容
     * @return $this
     */
    public static function filter($filter = null) {}

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template 模板文件名
     * @param array  $vars     模板变量
     * @return string
     * @throws Exception
     */
    public static function fetch($template = '', $vars = []) {}

    /**
     * 渲染内容输出
     * @param string $content 内容
     * @param array  $vars    模板变量
     * @return string
     */
    public static function display($content, $vars = []) {}

    /**
     * 获取解析后的模版文件 用于incude
     * @param string $template 模板文件名
     * @return string
     */
    public static function getFile($template) {}

}


