<?php

/**
 * 整体系统配置
 */

return [
    //是否开启调试模式
    'debug' => false,
    //是否开启事件
    'with_event' => true,
    //是否使用兼容url模式
    'var_pathinfo' => false,
    //指定public目录对应的相对url，首尾有斜线，为空自动计算
    'web_root' => '',
    //当前主域名用于url生成，完整格式如http://lfly.cn:80，默认端口80可不设定
    'web_domain' => '',
    //设置当前public目录上传的附件使用的完整域名地址，结尾有斜线（用于编辑器插入或接口返回完整地址）
    'web_upfile_url' => '',
    //全局加密密钥
    'web_secret_key' => '',
    //前端代理ip地址数组，用于获取真实ip
    'proxy_server_ip' => [],
    //默认时区
    'default_timezone' => 'Asia/Shanghai',
    //log设置
    'log' => [
        //保存类型，file文件
        'type' => 'file',
        //保存级别['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'http', 'db', 'querySlow']
        'level' => ['error', 'querySlow'],
    ],
    //cookie设置
    'cookie' => [
        //cookie默认前缀
        'prefix' => 'lf_',
        //cookie保存路径
        'path' => '/',
        //cookie有效域名
        'domain' => '',
        //cookie启用安全传输
        'secure' => false,
    ],
    //session设置
    'session' => [
        //类型 支持php和cache
        'type' => 'php',
        //指定会话名以用做cookie的名字
        'name' => 'FCSESSID',
        //以秒数指定了发送到浏览器的cookie的生命周期
        'cookie_lifetime' => 0,
        //数据的有效期秒数
        'gc_maxlifetime' => 1440,
    ],
    //文件上传设置
    'file' => [
        'default' => 'local',
        'engine' => [
            'local' => [
                //类型
                'type' => 'local',
                //执行完整类名实现 FileHandlerInterface
                //'class' => '类名',
                //默认文件保存目录，最后有斜线
                'root' => WEB_PATH . 'upfiles' . DS,
                //对应的url路径，最后有斜线
                'url' => 'upfiles/',
            ],
        ],
    ],
    //数据库配置
    'database' => [
        'default' => 'mysql',
        'engine' => [
            'mysql' => [
                //类型
                'type' => 'mysql',
                //服务器地址，多个用数组
                'host' => '127.0.0.1',
                //连接端口
                'port' => '3306',
                //库名
                'database' => '',
                //用户名
                'username' => 'root',
                //密码
                'password' => '',
                //连接参数
                'params' => [
                    //使用长链接
                    PDO::ATTR_PERSISTENT => true,
                ],
                //编码默认采用utf8mb4
                'charset' => 'utf8mb4',
                //表前缀
                'prefix' => '',
                //设置主服务器数量
                'master_num' => 1,
                //读写分离
                'rw_separate' => false,
                //记录sql日志
                'log' => true,
                //慢查询秒数设定(配合专门日志记录，不设定注释掉)
                'slow_time' => 1,
            ],
        ],
    ],
    //缓存配置
    'cache' => [
        'default' => 'redis',
        'engine' => [
            'redis' => [
                //类型
                'type' => 'redis',
                //服务器地址
                'host' => '127.0.0.1',
                //连接端口
                'port' => '6379',
                //密码
                'password' => '',
                //库编号
                'database' => 0,
                //数据前缀
                'prefix' => '',
            ],
        ],
    ]
];
