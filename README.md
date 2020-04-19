# 精简版 framework 框架

## 安装方法：
1. 在项目目录内创建 composer.json 文件。
```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://gitee.com/lfly1573/framework.git"
        }
    ],
    "require": {
        "lfly1573/framework": "dev-master"
    }
}
```
然后执行：
```
composer install
```

2. 如果不使用composer安装，可以直接下载zip包，将解压后的 framework 目录放到 项目目录/vendor/lfly1573/ 下。

2. 将 项目目录/vendor/lfly1573/framework/_ROOT_PATH 目录内的全部文件拷贝到项目目录。修改 cache 和 public/upfiles 目录权限为可读写。

3. 将网站目录设定为 public 目录，参考下列nginx配置。
```
location / {
	try_files $uri $uri/ /index.php?$query_string;
}
```

4. 使用手册还在编写中，如果你熟悉其他框架，此精简版很容易看懂且使用方式基本一致。

5. 本框架并没有依赖composer的自动加载，如果你需要同时使用其他包，请自行加载autoload文件。

## License
The MIT License(http://opensource.org/licenses/MIT)

## 鸣谢
实现方式参考了 Laravel 和 thinkphp ，特此感谢。