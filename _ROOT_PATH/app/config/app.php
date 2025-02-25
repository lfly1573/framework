<?php

/**
 * 整体系统配置
 */

return [
    //是否开启调试模式
    'debug' => Env::get('APP_DEBUG', false),
    //是否开启事件
    'with_event' => true,
    //是否使用兼容url模式
    'var_pathinfo' => false,
    //指定public目录对应的相对url，首尾有斜线，为空自动计算
    'web_root' => Env::get('WEB_ROOT', ''),
    //当前主域名用于url生成，完整格式如http://lfly.cn:80，默认端口80可不设定
    'web_domain' => Env::get('WEB_DOMAIN', ''),
    //设置当前public目录上传的附件使用的完整域名地址，结尾有斜线（用于编辑器插入或接口返回完整地址）
    'web_upfile_url' => Env::get('WEB_UPFILE_URL', ''),
    //全局加密密钥
    'web_secret_key' => '',
    //设置表单提交令牌保存参数(默认session，分布式部署请额外设定)
    //'web_submit_token' => ['name' => '默认变量名: submitToken', 'pre' => '变量前缀: st_', 'saveType' => '保存类型: cache/cookie/session'],
    //前端代理ip地址数组，用于获取真实ip
    'proxy_server_ip' => [],
    //默认时区
    'default_timezone' => Env::get('DEFAULT_TIMEZONE', 'Asia/Shanghai'),
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
        //是否自动刷新字段缓存，开启需要缓存模块支持
        'refreshCacheField' => false,
        'engine' => [
            'mysql' => [
                //类型
                'type' => 'mysql',
                //服务器地址，多个用数组
                'host' => Env::get('DATABASE.HOST', '127.0.0.1'),
                //连接端口
                'port' => Env::get('DATABASE.PORT', '3306'),
                //库名
                'database' => Env::get('DATABASE.DATABASE', ''),
                //用户名
                'username' => Env::get('DATABASE.USERNAME', 'root'),
                //密码
                'password' => Env::get('DATABASE.PASSWORD', ''),
                //连接参数
                'params' => [
                    //使用长链接
                    PDO::ATTR_PERSISTENT => true,
                ],
                //编码默认采用utf8mb4
                'charset' => Env::get('DATABASE.CHARSET', 'utf8mb4'),
                //表前缀
                'prefix' => '',
                //设置主服务器数量
                'master_num' => 1,
                //读写分离
                'rw_separate' => false,
                //记录sql日志
                'log' => Env::get('DATABASE.LOG', true),
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
                'host' => Env::get('CACHE.HOST', '127.0.0.1'),
                //连接端口
                'port' => Env::get('CACHE.PORT', '6379'),
                //密码
                'password' => Env::get('CACHE.PASSWORD', ''),
                //库编号
                'database' => Env::get('CACHE.DATABASE', 0),
                //数据前缀
                'prefix' => Env::get('CACHE.PREFIX', ''),
            ],
        ],
    ]
];
