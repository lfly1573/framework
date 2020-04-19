<?php

/**
 * 命令行类
 */

namespace lfly;

class Console
{
    /**
     * 获取到的ip
     * @var string
     */
    protected $ip;

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
     * 获取时间信息
     */
    public function getTimeInfo()
    {
        $return = [];
        list($return['year'], $return['month'], $return['day'], $return['weekNum'], $return['weekDay']) = array_map('intval', explode('-', date('Y-n-j-W-N', time())));
        $return['beginTime'] = strtotime("{$return['year']}-{$return['month']}-{$return['day']} 00:00:00");
        return $return;
    }

    /**
     * 获取执行服务器ip
     */
    public function getIp()
    {
        if (empty($this->ip)) {
            $ip = gethostbyname(php_uname('n'));
            if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip) || $ip == '127.0.0.1') {
                $serverIp = @exec('/sbin/ifconfig eth0 | sed -n \'s/^ *.*addr:\\([0-9.]\\{7,\\}\\) .*$/\\1/p\'', $array);
                $ip = preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $array[0]) ? $array[0] : '127.0.0.1';
            }
            $this->ip = $ip;
        }
        return $this->ip;
    }

    /**
     * 获取参数
     */
    public function getParam()
    {
        $return = ['controller' => '', 'action' => '', 'param' => []];
        if ($_SERVER['argc'] > 1) {
            $controller = $this->app->parseClassAndAction($_SERVER['argv'][1], 'command');
            if (!empty($controller)) {
                $return['controller'] = $controller[0];
                $return['action'] = $controller[1];
                if (isset($_SERVER['argv'][2])) {
                    $return['param'] = array_slice($_SERVER['argv'], 2);
                }
            }
        }
        return $return;
    }

    /**
     * 执行应用程序
     */
    public function run()
    {
        echo "\n================Start================\n\n";
        $runData = $this->getParam();
        if (!empty($runData['controller'])) {
            if (!is_callable([$runData['controller'], $runData['action']])) {
                exit('method not exists: ' . $runData['controller'] . '->' . $runData['action'] . '()');
            }
            $this->app->invokeMethod([$runData['controller'], $runData['action']], $runData['param']);
        } else {
            exit('file not exists');
        }
    }

    public function __destruct()
    {
        $debugInfo = $this->app->runtimeInfo();
        echo "\n\n=================End=================\n";
        exit("Costed {$debugInfo['time']} seconds, memory used {$debugInfo['memory']}.\n\n");
    }
}
