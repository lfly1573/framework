<?php

/**
 * 数据库类
 */

namespace lfly;

class Db
{
    /**
     * 数据库连接实例
     * @var array
     */
    protected $instance = [];

    /**
     * 配置
     */
    protected $config;

    /**
     * 查询次数
     * @var int
     */
    protected $queryTimes = 0;

    /**
     * 数据库操作日志
     * @var array
     */
    protected $log = [];

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
        $this->config = $this->app->config->get('database');
    }

    /**
     * 创建/切换数据库
     * @param string|null $name  连接名称
     * @param bool        $force 强制重新连接
     * @return \lfly\db\Query
     */
    public function connect($name = null, bool $force = false)
    {
        if (!empty($name) && is_string($this->config['engine'][$name])) {
            //数据库别名方便未来扩展
            $name = $this->config['engine'][$name];
        }
        if (empty($name)) {
            $name = $this->config['default'];
        }
        if ($force || !isset($this->instance[$name])) {
            $this->instance[$name] = $this->createConnection($name);
        }
        $connection = $this->instance[$name];
        $class = $connection->getQueryClass();
        $query = new $class($connection);
        return $query;
    }

    /**
     * 更新查询次数
     * @return void
     */
    public function updateQueryTimes()
    {
        $this->queryTimes++;
    }

    /**
     * 重置查询次数
     * @return void
     */
    public function clearQueryTimes()
    {
        $this->queryTimes = 0;
    }

    /**
     * 获得查询次数
     * @return int
     */
    public function getQueryTimes()
    {
        return $this->queryTimes;
    }

    /**
     * 设置日志
     * @param string       $op   操作
     * @param string|array $info 内容
     * @param string       $type 类型
     * @return void
     */
    public function setLog($op, $info = '', $type = 'db')
    {
        $this->log[] = [$op, $info, $type];
    }

    /**
     * 获取日志
     * @return array
     */
    public function getLog()
    {
        return $this->log;
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
        $this->app->event->trigger('Db' . $event, $params, $once);
    }

    /**
     * 创建模型类
     * @param string $model  模型名称
     * @return object
     */
    public function invokeModel($model)
    {
        return $this->app->make($this->app->parseClass($model, 'model'));
    }

    /**
     * 创建连接
     * @param $name
     * @return \lfly\contract\ConnectionHandlerInterface
     */
    protected function createConnection($name)
    {
        $config = $this->config['engine'][$name];
        if (empty($config)) {
            throw new \InvalidArgumentException('database engine error: ' . $name);
        }
        $type = !empty($config['type']) ? $config['type'] : 'mysql';
        $class = (false !== strpos($type, '\\')) ? $type : __NAMESPACE__ . '\\db\\connector\\' . ucfirst(strtolower($type));
        $connection = new $class($config);
        $connection->setDb($this);
        return $connection;
    }

    /**
     * 魔术调用
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->connect(), $method], $args);
    }
}
