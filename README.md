# 这是 lfly1573 提供的精简版 framework 框架。

## 安装方法：
1. 在项目目录通过 composer 安装。（暂未提交到Packagist）
```
composer require lfly1573/framework
```

2. 将 _ROOT_PATH 目录内的全部文件拷贝到项目目录。修改cache目录权限为可读写。

3. 将网站目录设定为 public 目录，参考下列nginx配置。
```
location / {
	try_files $uri $uri/ /index.php?$query_string;
}
```

## License
The MIT License(http://opensource.org/licenses/MIT)

## 鸣谢
实现方式参考了 Laravel 和 thinkphp ，特此感谢。