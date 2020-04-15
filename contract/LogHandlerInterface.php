<?php

/**
 * 日志驱动接口
 */

namespace lfly\contract;

interface LogHandlerInterface
{
    /**
     * 初始化
     * @param  array $config 配置
     * @return void
     */
    public function init($config);

    /**
     * 保存日志
     * @param  array $log 日志二维数组 [['time'=>'时间戳', 'usec'=>'微秒小数', 'uuid'=>'一个连续请求的id', 'type'=>'类型', 'info'=>'日志内容']]
     * @return void
     */
    public function save(array $log = []);

}
