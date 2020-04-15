<?php

/**
 * 事件注册
 */

/**
 * 系统内置事件，括号中为传入参数：
 * AppInit($app) 容器加载全局配置和事件后执行(服务类还未加载)
 * HttpRun($app) http执行全局中间件前
 * HttpEnd($app) http执行结束时执行
 * RouteLoaded($app) 路由加载完毕后执行
 * ResponseSend($app) 结果输出前执行
 * 
 * DbConnectError(['info' => '错误信息', 'dsn' => '连接信息', 'serverID' => '服务器id']) 数据库连接失败
 * DbQuerySlow(['sql' => '语句', 'costTime' => '花费时间', 'serverID' => '服务器id']) 数据库语句执行缓慢
 * DbQueryError(['sql' => '语句', 'info' => '错误信息', 'serverID' => '服务器id']) 数据库语句执行失败
 * DbExecute(['sql' => '语句', 'numRows' => '影响行数', 'serverID' => '服务器id']) 数据库执行插入更新删除等操作成功后
 */

return [
    //监听者，一个类只做一件事情，执行类中的 handle 方法
    'listen' => [
        //'事件标记' => ['监听类名1', '监听类名2']
    ],
    //订阅者，一个类订阅多个事件，定义方法 subscribe 手动订阅，或者方法名为 on+事件标记 自动订阅
    'subscribe' => [
        //'订阅类名'
    ],
];
