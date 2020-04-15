<?php

/**
 * 日志类
 */

namespace lfly;

class Log
{
    /**
     * 配置参数
     * @var array
     */
    protected $config;

    /**
     * 当前驱动
     */
    protected $curDriver;

    /**
     * 日志信息二维数组
     * @var array
     */
    protected $log = [];

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
        $this->config = $this->app->config->get('log');
        if (empty($this->config['type'])) {
            $this->config['type'] = 'file';
        }
        $this->engine($this->config['type']);
    }

    /**
     * 设置引擎
     * @param string $name 类型
     * @return $this
     */
    public function engine($name)
    {
        $class = (false !== strpos($this->config['type'], '\\')) ? $class : __NAMESPACE__ . '\\log\\' . ucfirst(strtolower($this->config['type']));
        $this->curDriver = $this->app->invokeClass($class);
        $this->curDriver->init($this->config);
        return $this;
    }

    /**
     * 保存全部日志
     * @return void
     */
    public function save()
    {
        if (empty($this->config['level'])) {
            return;
        }
        $dbLog = $this->app->db->getLog();
        foreach ($dbLog as $value) {
            $msg = $this->formatDbLog($value);
            if (in_array('db', $this->config['level'])) {
                $this->record($msg, 'db');
            }
            if ($value[2] != 'db' && in_array($value[2], $this->config['level'])) {
                $this->record($msg, $value[2]);
            }
        }
        if (in_array('http', $this->config['level'])) {
            $msg = $this->app->runtimeInfo();
            $msg['query'] = $this->app->db->getQueryTimes();
            $msg['file'] = count(get_included_files());
            $msg['code'] = $this->app->response->getCode();
            $msg['url'] = $this->app->request->url();
            $msg['indata'] = $this->app->request->param();
            $msg['outdata'] = $this->app->response->getData();
            $this->record(json_encode($msg), 'http');
        }
        if (!empty($this->log)) {
            $this->saveLog($this->log);
        }
    }

    /**
     * 单独保存日志
     * @param array $log 日志二维数组 [['time'=>'时间戳', 'usec'=>'微秒小数', 'uuid'=>'一个连续请求的id', 'type'=>'类型', 'info'=>'日志内容']]
     * @return void
     */
    public function saveLog(array $log = [])
    {
        $this->curDriver->save($log);
    }

    /**
     * 格式化日志
     * @param mixed  $msg  日志信息
     * @param string $type 日志级别
     * @return array
     */
    public function formatLog($msg, $type)
    {
        list($usec, $sec) = explode(' ', microtime());
        return ['time' => $sec, 'usec' => $usec, 'uuid' => $this->app->uniqid(), 'type' => $type, 'info' => $msg];
    }

    /**
     * 记录日志信息
     * @param mixed  $msg     日志信息
     * @param string $type    日志级别
     * @param array  $context 替换内容
     * @param bool   $lazy    是否内存记录
     * @return $this
     */
    public function record($msg, $type = 'info', array $context = [], bool $lazy = true)
    {
        if (!empty($this->config['level']) && in_array($type, $this->config['level'])) {
            if (!empty($context)) {
                $replace = [];
                foreach ($context as $key => $val) {
                    $replace['{' . $key . '}'] = $val;
                }
                $msg = strtr($msg, $replace);
            }
            $log = $this->formatLog($msg, $type);
            if ($lazy) {
                $this->log[] = $log;
            } else {
                $this->saveLog([$log]);
            }
        }
        return $this;
    }

    /**
     * 实时写入日志信息
     * @param mixed  $msg     调试信息
     * @param string $type    日志级别
     * @param array  $context 替换内容
     * @return $this
     */
    public function write($msg, $type = 'info', array $context = [])
    {
        return $this->record($msg, $type, $context, false);
    }

    /**
     * 记录日志信息
     * @param string $level   日志级别
     * @param mixed  $message 日志信息
     * @param array  $context 替换内容
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->record($message, $level, $context);
    }

    /**
     * 记录emergency信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录警报信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录紧急情况
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录错误信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录warning信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录notice信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录一般信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录调试信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录数据库信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function db($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * 记录数据库查询缓慢信息
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function querySlow($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function __call($method, $parameters)
    {
        $this->log($method, ...$parameters);
    }

    protected function formatDbLog($log)
    {
        if (is_array($log[1])) {
            $log[1] = array_merge(['op' => $log[0]], $log[1]);
        } else {
            $log[1] = ['op' => $log[0], 'msg' => $log[1]];
        }
        return json_encode($log[1]);
    }
}
