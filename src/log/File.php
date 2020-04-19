<?php

/**
 * 文本日志
 */

namespace lfly\log;

use lfly\contract\LogHandlerInterface;
use lfly\Cache;

class File implements LogHandlerInterface
{
    /**
     * 配置参数
     * @var array
     */
    protected $config;

    /**
     * 初始化
     * @param  array $config 配置
     * @return void
     */
    public function init($config)
    {
        $this->config = $config;
    }

    /**
     * 保存日志
     * @param  array $log 日志二维数组 [['time'=>'时间戳', 'usec'=>'微秒小数', 'uuid'=>'一个连续请求的id', 'type'=>'类型', 'info'=>'日志内容']]
     * @return void
     */
    public function save(array $log = [])
    {
        $content = [];
        foreach ($log as $value) {
            if (!isset($content[$value['type']])) {
                $content[$value['type']] = '';
            }
            $content[$value['type']] .= date('H:i:s', $value['time']) . '.' . round($value['usec'] * 1000000) . "\t{$value['uuid']}\t{$value['info']}" . PHP_EOL;
        }
        foreach ($content as $key => $value) {
            $file = CACHE_PATH . 'log' . DS . date('Y-m-d') . '_' . $key;
            Cache::writeFile($file, $value, true);
        }
    }
}
