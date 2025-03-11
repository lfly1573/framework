<?php

/**
 * 请求
 */

namespace lfly;

class Request
{
    /**
     * 兼容PATH_INFO获取
     * @var array
     */
    protected $pathinfoFetch = ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'];

    /**
     * PATHINFO变量名 用于兼容模式
     * @var string
     */
    protected $varPathinfo = 's';

    /**
     * 请求类型
     * @var string
     */
    protected $varMethod = '_method';

    /**
     * 表单ajax伪装变量
     * @var string
     */
    protected $varAjax = '_ajax';

    /**
     * 表单pjax伪装变量
     * @var string
     */
    protected $varPjax = '_pjax';

    /**
     * HTTPS代理标识
     * @var string
     */
    protected $httpsAgentName = '';

    /**
     * 前端代理服务器IP
     * @var array
     */
    protected $proxyServerIp = [];

    /**
     * 前端代理服务器真实IP头
     * @var array
     */
    protected $proxyServerIpHeader = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'];

    /**
     * 请求类型
     * @var string
     */
    protected $method;

    /**
     * 主域名（含协议及端口）
     * @var string
     */
    protected $mainDomain;

    /**
     * HOST（含端口）
     * @var string
     */
    protected $host;

    /**
     * 域名根
     * @var string
     */
    protected $rootDomain = '';

    /**
     * 子域名
     * @var string
     */
    protected $subDomain;

    /**
     * 泛域名
     * @var string
     */
    protected $panDomain;

    /**
     * 当前URL地址
     * @var string
     */
    protected $url;

    /**
     * 基础URL
     * @var string
     */
    protected $baseUrl;

    /**
     * 当前执行的文件
     * @var string
     */
    protected $baseFile;

    /**
     * 当前public目录对应的url地址(前后有斜线)
     * @var string
     */
    protected $root;

    /**
     * 附件完整url路径
     * @var string
     */
    protected $upfileUrl;

    /**
     * pathinfo
     * @var string
     */
    protected $pathinfo;

    /**
     * pathinfo（不含后缀）
     * @var string
     */
    protected $path;

    /**
     * 当前请求的IP地址
     * @var string
     */
    protected $realIP;

    /**
     * 当前时间
     * @var int
     */
    protected $curTime;

    /**
     * 当前控制器名
     * @var string
     */
    protected $controller;

    /**
     * 当前操作名
     * @var string
     */
    protected $action;

    /**
     * 当前HEADER参数
     * @var array
     */
    protected $header = [];

    /**
     * 当前SERVER参数
     * @var array
     */
    protected $server = [];

    /**
     * 当前请求参数
     * @var array
     */
    protected $param = [];

    /**
     * 当前GET参数
     * @var array
     */
    protected $get = [];

    /**
     * 当前POST参数
     * @var array
     */
    protected $post = [];

    /**
     * 当前REQUEST参数
     * @var array
     */
    protected $request = [];

    /**
     * 当前ROUTE参数
     * @var array
     */
    protected $route = [];

    /**
     * 中间件传递的参数
     * @var array
     */
    protected $middleware = [];

    /**
     * 当前PUT参数
     * @var array
     */
    protected $put;

    /**
     * php://input内容
     * @var string
     */
    protected $input;

    /**
     * 是否合并Param
     * @var bool
     */
    protected $mergeParam = false;

    /**
     * 资源类型定义
     * @var array
     */
    protected $mimeType = [
        'xml' => 'application/xml,text/xml,application/x-xml',
        'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js' => 'text/javascript,application/javascript,application/x-javascript',
        'css' => 'text/css',
        'rss' => 'application/rss+xml',
        'yaml' => 'application/x-yaml,text/yaml',
        'atom' => 'application/atom+xml',
        'pdf' => 'application/pdf',
        'text' => 'text/plain',
        'image' => 'image/png,image/jpg,image/jpeg,image/pjpeg,image/gif,image/webp,image/*',
        'csv' => 'text/csv',
        'html' => 'text/html,application/xhtml+xml,*/*',
    ];

    /**
     * 全局过滤规则
     * @var array
     */
    protected $filter;

    /**
     * 请求token变量
     * @var array
     */
    protected $submitTokenVar = ['name' => 'submitToken', 'pre' => 'st_', 'saveType' => 'session'];

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
        //设置参数
        $webRoot = $this->app->config->get('web_root', '');
        if ($webRoot != '') {
            $this->setRoot($webRoot);
        }
        $webDomain = $this->app->config->get('web_domain', '');
        if ($webDomain != '') {
            $this->setMainDomain($webDomain);
        }
        $submitTokenValue = $this->app->config->get('web_submit_token', []);
        if (!empty($submitTokenValue)) {
            $this->setSubmitTokenVar($submitTokenValue);
        }
        $proxyServerIp = $this->app->config->get('proxy_server_ip', []);
        if (!empty($proxyServerIp)) {
            $this->setProxyServerIp($proxyServerIp);
        }
        //请求获取
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $value) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $value;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }

        $this->header = array_change_key_case($header);
        $this->input = file_get_contents('php://input');
        $inputData = $this->getInputData($this->input);
        $this->server = $_SERVER;
        $this->get = $_GET;
        $this->post = $_POST ?: $inputData;
        $this->put = $inputData;
        $this->request = $_REQUEST;
    }

    /**
     * 设置或者获取当前的Header
     * @param  string $name     header名称
     * @param  string $default  默认值
     * @return string|array
     */
    public function header($name = '', $default = null)
    {
        if ('' === $name) {
            return $this->header;
        }

        $name = str_replace('_', '-', strtolower($name));
        return $this->header[$name] ?? $default;
    }

    /**
     * 获取当前时间
     * @return int
     */
    public function getTime()
    {
        if (empty($this->curTime)) {
            $this->curTime = time();
        }
        return $this->curTime;
    }

    /**
     * 设置包含协议的主域名
     * @param  string $mainDomain 域名
     * @return $this
     */
    public function setMainDomain(string $mainDomain)
    {
        $this->mainDomain = $mainDomain;
        return $this;
    }

    /**
     * 设置当前包含协议的域名
     * @param  string $domain 域名
     * @return $this
     */
    public function mainDomain()
    {
        return $this->mainDomain ?? '';
    }

    /**
     * 获取当前包含协议的域名
     * @param  bool $port 是否需要去除端口号
     * @return string
     */
    public function domain(bool $port = false)
    {
        return $this->scheme() . '://' . $this->host($port);
    }

    /**
     * 获取当前根域名
     * @return string
     */
    public function rootDomain()
    {
        if (empty($this->rootDomain)) {
            $item = explode('.', $this->host());
            $count = count($item);
            if ($count > 2 && strtolower($item[$count - 1]) == 'cn' && in_array(strtolower($item[$count - 2]), array('ac', 'com', 'net', 'gov', 'org', 'edu', 'mil'))) {
                $root = $item[$count - 3] . '.' . $item[$count - 2] . '.' . $item[$count - 1];
            } elseif ($count > 1) {
                $root = $item[$count - 2] . '.' . $item[$count - 1];
            } else {
                $root = $item[0];
            }
            $this->rootDomain = $root;
        }
        return $this->rootDomain;
    }

    /**
     * 设置当前子域名的值
     * @param  string $domain 域名
     * @return $this
     */
    public function setSubDomain(string $domain)
    {
        $this->subDomain = $domain;
        return $this;
    }

    /**
     * 获取当前子域名
     * @return string
     */
    public function subDomain()
    {
        if (is_null($this->subDomain)) {
            // 获取当前主域名
            $rootDomain = $this->rootDomain();

            if ($rootDomain) {
                $this->subDomain = rtrim(stristr($this->host(), $rootDomain, true), '.');
            } else {
                $this->subDomain = '';
            }
        }

        return $this->subDomain;
    }

    /**
     * 设置当前泛域名的值
     * @param  string $domain 域名
     * @return $this
     */
    public function setPanDomain(string $domain)
    {
        $this->panDomain = $domain;
        return $this;
    }

    /**
     * 获取当前泛域名的值
     * @return string
     */
    public function panDomain()
    {
        return $this->panDomain ? : '';
    }

    /**
     * 设置当前完整URL 包括QUERY_STRING
     * @param  string $url URL地址
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 获取当前完整URL 包括QUERY_STRING
     * @param  bool $complete 是否包含完整域名
     * @return string
     */
    public function url(bool $complete = false)
    {
        if (is_null($this->url)) {
            if ($this->url) {
                $url = $this->url;
            } elseif ($this->server('HTTP_X_REWRITE_URL')) {
                $url = $this->server('HTTP_X_REWRITE_URL');
            } elseif ($this->server('REQUEST_URI')) {
                $url = $this->server('REQUEST_URI');
            } elseif ($this->server('ORIG_PATH_INFO')) {
                $url = $this->server('ORIG_PATH_INFO') . (!empty($this->server('QUERY_STRING')) ? '?' . $this->server('QUERY_STRING') : '');
            } elseif (isset($_SERVER['argv'][1])) {
                $url = $_SERVER['argv'][1];
            } else {
                $url = '';
            }
            $this->url = $url;
        }

        return $complete ? $this->domain() . $this->url : $this->url;
    }

    /**
     * 设置当前URL 不含QUERY_STRING
     * @param  string $url URL地址
     * @return $this
     */
    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * 获取当前URL 不含QUERY_STRING
     * @param  bool $complete 是否包含完整域名
     * @return string
     */
    public function baseUrl(bool $complete = false)
    {
        if (!$this->baseUrl) {
            $str = $this->url();
            $this->baseUrl = strpos($str, '?') ? strstr($str, '?', true) : $str;
        }

        return $complete ? $this->domain() . $this->baseUrl : $this->baseUrl;
    }

    /**
     * 获取当前执行的文件 SCRIPT_NAME
     * @param  bool $complete 是否包含完整域名
     * @return string
     */
    public function baseFile(bool $complete = false)
    {
        if (!$this->baseFile) {
            $url = '';
            if (!$this->isCli()) {
                $script_name = basename($this->server('SCRIPT_FILENAME'));
                if (basename($this->server('SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('SCRIPT_NAME');
                } elseif (basename($this->server('PHP_SELF')) === $script_name) {
                    $url = $this->server('PHP_SELF');
                } elseif (basename($this->server('ORIG_SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('ORIG_SCRIPT_NAME');
                } elseif (($pos = strpos($this->server('PHP_SELF'), '/' . $script_name)) !== false) {
                    $url = substr($this->server('SCRIPT_NAME'), 0, $pos) . '/' . $script_name;
                } elseif ($this->server('DOCUMENT_ROOT') && strpos($this->server('SCRIPT_FILENAME'), $this->server('DOCUMENT_ROOT')) === 0) {
                    $url = str_replace('\\', '/', str_replace($this->server('DOCUMENT_ROOT'), '', $this->server('SCRIPT_FILENAME')));
                }
            }
            $this->baseFile = $url;
        }

        return $complete ? $this->domain() . $this->baseFile : $this->baseFile;
    }

    /**
     * 设置URL访问根地址
     * @param  string $url URL地址
     * @return $this
     */
    public function setRoot(string $url)
    {
        $this->root = $url;
        return $this;
    }

    /**
     * 获取URL访问根地址
     * @param  bool $complete 是否包含完整域名
     * @return string
     */
    public function root(bool $complete = false)
    {
        if (!$this->root) {
            $this->root = strtr(substr(WEB_PATH, strlen($this->server('DOCUMENT_ROOT'))), DS, '/');
        }

        return $complete ? $this->domain() . $this->root : $this->root;
    }

    /**
     * 获取构建url的前缀
     * @return string
     */
    public function pre()
    {
        return $this->mainDomain() . $this->root();
    }

    /**
     * 获取附件完整域名地址
     * @return string
     */
    public function upfileUrl()
    {
        if (empty($this->upfileUrl)) {
            $upfileUrl = $this->app->config->get('web_upfile_url', '');
            if (empty($upfileUrl)) {
                if ($this->mainDomain() != '') {
                    $upfileUrl = $this->pre();
                } else {
                    $upfileUrl = $this->root(true);
                }
            }
            $this->upfileUrl = $upfileUrl;
        }
        return $this->upfileUrl;
    }

    /**
     * 获取当前兼容请求的参数变量
     * @return string
     */
    public function varPathinfo()
    {
        return $this->varPathinfo;
    }

    /**
     * 设置当前请求的pathinfo
     * @param  string $pathinfo
     * @return $this
     */
    public function setPathinfo(string $pathinfo)
    {
        $this->pathinfo = $pathinfo;
        return $this;
    }

    /**
     * 获取当前请求URL的pathinfo信息（含URL后缀）
     * @return string
     */
    public function pathinfo()
    {
        if (is_null($this->pathinfo)) {
            if (isset($_GET[$this->varPathinfo])) {
                // 判断URL里面是否有兼容模式参数
                $pathinfo = $_GET[$this->varPathinfo];
                unset($_GET[$this->varPathinfo]);
                unset($this->get[$this->varPathinfo]);
            } elseif ($this->server('PATH_INFO')) {
                $pathinfo = $this->server('PATH_INFO');
            } elseif ($this->server('REQUEST_URI')) {
                $pathinfo = strpos($this->server('REQUEST_URI'), '?') ? strstr($this->server('REQUEST_URI'), '?', true) : $this->server('REQUEST_URI');
            }

            // 分析PATHINFO信息
            if (!isset($pathinfo)) {
                foreach ($this->pathinfoFetch as $type) {
                    if ($this->server($type)) {
                        $pathinfo = (0 === strpos($this->server($type), $this->server('SCRIPT_NAME'))) ?
                            substr($this->server($type), strlen($this->server('SCRIPT_NAME'))) : $this->server($type);
                        break;
                    }
                }
            }

            if (!empty($pathinfo)) {
                unset($this->get[$pathinfo], $this->request[$pathinfo]);
            }

            $root = $this->root();
            if (substr($pathinfo, 0, strlen($root)) == $root) {
                $pathinfo = substr($pathinfo, strlen($root));
            }
            $pathinfo = str_replace('index' . EXT, '', $pathinfo);
            $this->pathinfo = empty($pathinfo) || '/' == $pathinfo ? '' : ltrim($pathinfo, '/');
            $this->path = preg_replace('/\.[a-zA-Z0-9]+$/', '', $this->pathinfo);
            if ($this->path != '' && $this->root() != '' && substr($this->path, 0, strlen($this->root()) - 1) == ltrim($this->root(), '/')) {
                $this->path = substr($this->path, strlen($this->root()) - 1);
            }
        }

        return $this->pathinfo;
    }

    /**
     * 获取当前请求URL的pathinfo信息（不含后缀）
     * @return string
     */
    public function path()
    {
        if (is_null($this->path)) {
            $this->pathinfo();
        }
        return $this->path;
    }

    /**
     * 当前URL的访问后缀
     * @return string
     */
    public function ext()
    {
        return pathinfo($this->pathinfo(), PATHINFO_EXTENSION);
    }

    /**
     * 获取当前请求的时间
     * @param  bool $float 是否使用浮点类型
     * @return integer|float
     */
    public function time(bool $float = false)
    {
        return $float ? $this->server('REQUEST_TIME_FLOAT') : $this->server('REQUEST_TIME');
    }

    /**
     * 当前请求的资源类型
     * @return string
     */
    public function type()
    {
        $accept = $this->server('HTTP_ACCEPT');

        if (empty($accept)) {
            return '';
        }

        foreach ($this->mimeType as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($accept, $v)) {
                    return $key;
                }
            }
        }

        return '';
    }

    /**
     * 设置资源类型
     * @param  string|array $type 资源类型名
     * @param  string       $val  资源类型
     * @return void
     */
    public function mimeType($type, $val = '')
    {
        if (is_array($type)) {
            $this->mimeType = array_merge($this->mimeType, $type);
        } else {
            $this->mimeType[$type] = $val;
        }
    }

    /**
     * 当前URL地址中的scheme参数
     * @return string
     */
    public function scheme()
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     * 当前请求URL地址中的query参数
     * @return string
     */
    public function query()
    {
        return $this->server('QUERY_STRING', '');
    }

    /**
     * 设置当前请求的host（包含端口）
     * @param  string $host 主机名（含端口）
     * @return $this
     */
    public function setHost(string $host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * 当前请求的host
     * @param bool $strict  true 仅仅获取HOST
     * @return string
     */
    public function host(bool $strict = false)
    {
        if ($this->host) {
            $host = $this->host;
        } else {
            $host = strval($this->server('HTTP_X_REAL_HOST') ? : $this->server('HTTP_HOST'));
        }

        return true === $strict && strpos($host, ':') ? strstr($host, ':', true) : $host;
    }

    /**
     * 当前请求URL地址中的port参数
     * @return int
     */
    public function port()
    {
        return (int)$this->server('SERVER_PORT', '');
    }

    /**
     * 当前请求 HTTP_CONTENT_TYPE
     * @return string
     */
    public function contentType()
    {
        $contentType = $this->header('Content-Type');

        if ($contentType) {
            if (strpos($contentType, ';')) {
                [$type] = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }

        return '';
    }

    /**
     * 当前请求 SERVER_PROTOCOL
     * @return string
     */
    public function protocol()
    {
        return $this->server('SERVER_PROTOCOL', '');
    }

    /**
     * 当前请求 REMOTE_PORT
     * @return int
     */
    public function remotePort()
    {
        return (int)$this->server('REMOTE_PORT', '');
    }

    /**
     * 设置请求类型
     * @param  string $method 请求类型
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * 当前的请求类型
     * @param  bool $origin 是否获取原始请求类型
     * @return string
     */
    public function method(bool $origin = false)
    {
        if ($origin) {
            return $this->server('REQUEST_METHOD') ? $this->server('REQUEST_METHOD') : 'GET';
        } elseif (!$this->method) {
            if (isset($this->post[$this->varMethod])) {
                $method = strtolower($this->post[$this->varMethod]);
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    $this->method = strtoupper($method);
                    $this->{$method} = $this->post;
                } else {
                    $this->method = 'POST';
                }
                unset($this->post[$this->varMethod]);
            } elseif ($this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                $this->method = strtoupper($this->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
            } else {
                $this->method = $this->server('REQUEST_METHOD') ? $this->server('REQUEST_METHOD') : 'GET';
            }
        }

        return $this->method;
    }

    /**
     * 来自地址 HTTP_REFERER
     * @param bool $checkSelf 判断是否来自本站根域名的地址
     * @return string
     */
    public function comeUrl(bool $checkSelf = false)
    {
        $url = $this->server('HTTP_REFERER');
        if ($checkSelf && (empty($url) || substr($url, 0 - strlen($this->rootDomain())) != $this->rootDomain())) {
            return '';
        }
        return $url;
    }

    /**
     * 生成请求令牌
     * @param int    $type 获取类型 0:直接获取 1:获取整个同名input 2:获取meta 3:获取js运算表达式
     * @param string $name 令牌名称
     * @return string
     */
    public function buildToken($type = 0, $name = null)
    {
        if (empty($name)) {
            $name = $this->submitTokenVar['name'];
        }
        $token = substr(md5($this->server('REQUEST_TIME_FLOAT') . $this->server('HTTP_USER_AGENT')), 0, rand(20, 32));

        if ($this->submitTokenVar['saveType'] == 'cache') {
            $myCacheId = $this->app->cookie->get($this->submitTokenVar['pre'] . $this->submitTokenVar['name'], '');
            if (empty($myCacheId)) {
                $myCacheId = $this->app->uniqid();
                $this->app->cookie->set($this->submitTokenVar['pre'] . $this->submitTokenVar['name'], $myCacheId, 0, true);
            }
            $this->app->cache->push($this->submitTokenVar['pre'] . $this->submitTokenVar['name'] . '_' . $myCacheId, md5($name . '*LFLY#' . $token), $name, 36000);
        } elseif ($this->submitTokenVar['saveType'] == 'session') {
            $this->app->session->set($this->submitTokenVar['pre'] . $name, md5($name . '*LFLY#' . $token));
        } else {
            $this->app->cookie->set($this->submitTokenVar['pre'] . $name, md5($name . '*LFLY#' . $token), 0, true);
        }

        if ($type == 0) {
            return $token;
        } elseif ($type == 1) {
            return '<input type="hidden" name="' . $name . '" id="token_' . $name . '" value="' . $token . '" />';
        } elseif ($type == 2) {
            return '<meta name="csrf-token" content="' . $token . '" />';
        } elseif ($type == 3) {
            if (rand(0, 1)) {
                $randStr = substr(md5($token), 0, rand(3, 18));
                return "'" . $randStr . $token . "'.substr(" . strlen($randStr) . ")";
            } else {
                $randPos = rand(3, 18);
                return "'" . substr($token, 0, $randPos) . "'+'" . substr($token, $randPos) . "'";
            }
        }
        return $token;
    }

    /**
     * 检查请求令牌
     * @param string $value 当前令牌值
     * @param bool   $isDel 校验后是否删除
     * @param string $name  令牌名称
     * @return string
     */
    public function checkToken($value = null, bool $isDel = false, $name = null)
    {
        if (empty($name)) {
            $name = $this->submitTokenVar['name'];
        }

        //默认GET没有忽略！
        if (in_array($this->method(), ['HEAD', 'OPTIONS'], true)) {
            return true;
        }

        if ($this->submitTokenVar['saveType'] == 'cache') {
            $myCacheId = $this->app->cookie->get($this->submitTokenVar['pre'] . $this->submitTokenVar['name'], '');
            $setValue = !empty($myCacheId) ? $this->app->cache->getItem($this->submitTokenVar['pre'] . $this->submitTokenVar['name'] . '_' . $myCacheId, $name, '') : '';
        } elseif ($this->submitTokenVar['saveType'] == 'session') {
            $setValue = $this->app->session->get($this->submitTokenVar['pre'] . $name, '');
        } else {
            $setValue = $this->app->cookie->get($this->submitTokenVar['pre'] . $name, '');
        }

        if (empty($setValue)) {
            return false;
        }

        if ($isDel) {
            $this->deleteToken($name);
        }

        if (empty($value)) {
            $value = $this->header('X-CSRF-TOKEN');
            if (empty($value)) {
                $value = $this->param($name);
            }
        }
        if (!empty($value) && $setValue == md5($name . '*LFLY#' . $value)) {
            return true;
        }
        return false;
    }

    /**
     * 删除请求令牌
     * @param string $name 令牌名称
     * @return void
     */
    public function deleteToken($name = null)
    {
        if (empty($name)) {
            $name = $this->submitTokenVar['name'];
        }
        if ($this->submitTokenVar['saveType'] == 'cache') {
            $myCacheId = $this->app->cookie->get($this->submitTokenVar['pre'] . $this->submitTokenVar['name'], '');
            if (!empty($myCacheId)) {
                $this->app->cache->push($this->submitTokenVar['pre'] . $this->submitTokenVar['name'] . '_' . $myCacheId, null, $name, 36000);
            }
        } elseif ($this->submitTokenVar['saveType'] == 'session') {
            $this->app->session->delete($this->submitTokenVar['pre'] . $name);
        } else {
            $this->app->cookie->delete($this->submitTokenVar['pre'] . $name);
        }
    }

    /**
     * 生成随机字符
     * @param int $length 长度
     * @param int $type 类型 0:数字字母 5:验证码字符 10:纯数字 16:仅16进制字符
     * @return string
     */
    public function random(int $length, int $type = 0)
    {
        $chars = $hash = '';
        if ($type == 16) {
            $chars = 'ABCDEF0123456789';
        } elseif ($type == 10) {
            $chars = '0123456789';
        } elseif ($type == 5) {
            $chars = 'jpyacemnrsuvwxzbdfhktABCDEFGHJKLMNPQRSTUVWXYZ2345678';
        } else {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        }
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * 是否为GET请求
     * @return bool
     */
    public function isGet()
    {
        return $this->method() == 'GET';
    }

    /**
     * 是否为POST请求
     * @return bool
     */
    public function isPost()
    {
        return $this->method() == 'POST';
    }

    /**
     * 是否为PUT请求
     * @return bool
     */
    public function isPut()
    {
        return $this->method() == 'PUT';
    }

    /**
     * 是否为DELTE请求
     * @return bool
     */
    public function isDelete()
    {
        return $this->method() == 'DELETE';
    }

    /**
     * 是否为HEAD请求
     * @return bool
     */
    public function isHead()
    {
        return $this->method() == 'HEAD';
    }

    /**
     * 是否为PATCH请求
     * @return bool
     */
    public function isPatch()
    {
        return $this->method() == 'PATCH';
    }

    /**
     * 是否为OPTIONS请求
     * @return bool
     */
    public function isOptions()
    {
        return $this->method() == 'OPTIONS';
    }

    /**
     * 是否为cli
     * @return bool
     */
    public function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * 是否为cgi
     * @return bool
     */
    public function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }


    /**
     * 设置提交令牌参数
     * @param  array $tokenVar 提交令牌参数
     * @return $this
     */
    public function setSubmitTokenVar(array $tokenVar)
    {
        $this->submitTokenVar = array_merge($this->submitTokenVar, $tokenVar);
        return $this;
    }

    /**
     * 设置路由变量
     * @param  array $route 路由变量
     * @return $this
     */
    public function setRoute(array $route)
    {
        $this->route = array_merge($this->route, $route);
        return $this;
    }

    /**
     * 获取当前请求的php://input
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * 获取当前请求的参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public function param($name = '', $default = null, $filter = '')
    {
        if (empty($this->mergeParam)) {
            $method = $this->method(true);

            // 自动获取请求变量
            switch ($method) {
                case 'POST':
                    $vars = $this->post(false);
                    break;
                case 'PUT':
                case 'DELETE':
                case 'PATCH':
                    $vars = $this->put(false);
                    break;
                default:
                    $vars = [];
            }

            // 当前请求参数和URL地址中的参数合并
            $this->param = array_merge($this->param, $this->get(false), $vars, $this->route(false));

            $this->mergeParam = true;
        }

        if (is_array($name)) {
            return $this->only($name, $this->param, $filter);
        }

        return $this->input($this->param, $name, $default, $filter);
    }

    /**
     * 获取路由参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public function route($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->route, $filter);
        }

        return $this->input($this->route, $name, $default, $filter);
    }

    /**
     * 获取GET参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public function get($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->get, $filter);
        }

        return $this->input($this->get, $name, $default, $filter);
    }

    /**
     * 获取POST参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public function post($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->post, $filter);
        }

        return $this->input($this->post, $name, $default, $filter);
    }

    /**
     * 获取PUT参数
     * @param  string|array $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public function put($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->put, $filter);
        }

        return $this->input($this->put, $name, $default, $filter);
    }

    /**
     * 设置获取DELETE参数
     * @param  mixed        $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public function delete($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * 设置获取PATCH参数
     * @param  mixed        $name    变量名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public function patch($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * 获取request变量
     * @param  string|array $name    数据名称
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤方法
     * @return mixed
     */
    public function request($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->request, $filter);
        }

        return $this->input($this->request, $name, $default, $filter);
    }

    /**
     * 获取中间件传递的参数
     * @param  mixed $name    变量名
     * @param  mixed $default 默认值
     * @return mixed
     */
    public function middleware($name, $default = null)
    {
        return $this->middleware[$name] ?? $default;
    }

    /**
     * 获取server参数
     * @param  string $name    数据名称
     * @param  string $default 默认值
     * @return mixed
     */
    public function server(string $name = '', string $default = '')
    {
        if (empty($name)) {
            return $this->server;
        } else {
            $name = strtoupper($name);
        }

        return $this->server[$name] ?? $default;
    }

    /**
     * 是否存在某个请求参数
     * @param  string $name 变量名
     * @param  string $type 变量类型
     * @param  bool   $checkEmpty 是否检测空值
     * @return bool
     */
    public function has(string $name, string $type = 'param', bool $checkEmpty = false)
    {
        if (!in_array($type, ['param', 'get', 'post', 'put', 'patch', 'route', 'delete', 'request', 'server', 'header'])) {
            return false;
        }

        $param = empty($this->$type) ? $this->$type() : $this->$type;

        if (is_object($param)) {
            return $param->has($name);
        }

        // 按.拆分成多维数组进行判断
        foreach (explode('.', $name) as $val) {
            if (isset($param[$val])) {
                $param = $param[$val];
            } else {
                return false;
            }
        }

        return ($checkEmpty && '' === $param) ? false : true;
    }

    /**
     * 获取变量 支持过滤和默认值
     * @param  array        $data    数据源
     * @param  string|false $name    字段名
     * @param  mixed        $default 默认值
     * @param  string|array $filter  过滤函数
     * @return mixed
     */
    public function input(array $data = [], $name = '', $default = null, $filter = '')
    {
        if (false === $name) {
            // 获取原始数据
            return $data;
        }

        $name = (string)$name;
        if ('' != $name) {
            // 解析name
            if (strpos($name, '/')) {
                [$name, $type] = explode('/', $name);
            }

            $data = $this->getData($data, $name);

            if (is_null($data)) {
                return $default;
            }

            if (is_object($data)) {
                return $data;
            }
        }

        $data = $this->filterData($data, $filter, $name, $default);

        if (isset($type) && $data !== $default) {
            // 强制类型转换
            $this->typeCast($data, $type);
        }

        return $data;
    }

    /**
     * 获取指定的参数
     * @param  array        $name 变量名
     * @param  mixed        $data 数据或者变量类型
     * @param  string|array $filter 过滤方法
     * @return array
     */
    public function only(array $name, $data = 'param', $filter = '')
    {
        $data = is_array($data) ? $data : $this->$data();

        $item = [];
        foreach ($name as $key => $val) {
            if (is_int($key)) {
                $default = null;
                $key = $val;
                if (!isset($data[$key])) {
                    continue;
                }
            } else {
                $default = $val;
            }

            $item[$key] = $this->filterData($data[$key] ?? $default, $filter, $key, $default);
        }

        return $item;
    }

    /**
     * 排除指定参数获取
     * @param  array  $name 变量名
     * @param  string $type 变量类型
     * @return mixed
     */
    public function except(array $name, string $type = 'param')
    {
        $param = $this->$type();

        foreach ($name as $key) {
            if (isset($param[$key])) {
                unset($param[$key]);
            }
        }

        return $param;
    }

    /**
     * 设置或获取当前的过滤规则
     * @access public
     * @param  mixed $filter 过滤规则
     * @return mixed
     */
    public function filter($filter = null)
    {
        if (is_null($filter)) {
            return $this->filter;
        }

        $this->filter = $filter;

        return $this;
    }

    /**
     * 递归过滤给定的值
     * @param  mixed $value 键值
     * @param  mixed $key 键名
     * @param  array $filters 过滤方法+默认值
     * @return mixed
     */
    public function filterValue(&$value, $key, $filters)
    {
        $default = array_pop($filters);

        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                // 调用函数或者方法过滤
                $value = call_user_func($filter, $value);
            } elseif (is_scalar($value)) {
                if (is_string($filter) && false !== strpos($filter, '/')) {
                    // 正则过滤
                    if (!preg_match($filter, $value)) {
                        // 匹配不成功返回默认值
                        $value = $default;
                        break;
                    }
                } elseif (!empty($filter)) {
                    // filter函数不存在时, 则使用filter_var进行过滤
                    // filter为非整形值时, 调用filter_id取得过滤id
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $value) {
                        $value = $default;
                        break;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * 当前是否ssl
     * @return bool
     */
    public function isSsl()
    {
        if (!empty($this->server('HTTPS')) && strtolower($this->server('HTTPS')) != 'off') {
            return true;
        } elseif ('https' == $this->server('REQUEST_SCHEME')) {
            return true;
        } elseif ('443' == $this->server('SERVER_PORT')) {
            return true;
        } elseif ('https' == $this->server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        } elseif ($this->httpsAgentName && $this->server($this->httpsAgentName)) {
            return true;
        }

        return false;
    }

    /**
     * 当前是否JSON请求
     * @return bool
     */
    public function isJson()
    {
        $acceptType = $this->type();
        return false !== strpos($acceptType, 'json');
    }

    /**
     * 当前是否Ajax请求
     * @param  bool $ajax true 获取原始ajax请求
     * @return bool
     */
    public function isAjax(bool $ajax = false)
    {
        $value = $this->server('HTTP_X_REQUESTED_WITH');
        $result = $value && 'xmlhttprequest' == strtolower($value) ? true : false;

        if (true === $ajax) {
            return $result;
        }

        return $this->param($this->varAjax) ? true : $result;
    }

    /**
     * 当前是否Pjax请求
     * @param  bool $pjax true 获取原始pjax请求
     * @return bool
     */
    public function isPjax(bool $pjax = false)
    {
        $result = !empty($this->server('HTTP_X_PJAX')) ? true : false;

        if (true === $pjax) {
            return $result;
        }

        return $this->param($this->varPjax) ? true : $result;
    }

    /**
     * 设置代理IP地址
     * @param array|string $ip 代理ip地址
     * @return string
     */
    public function setProxyServerIp($ip)
    {
        $this->proxyServerIp = (array)$ip;
        return $this;
    }

    /**
     * 获取客户端IP地址
     * @return string
     */
    public function ip()
    {
        if (!empty($this->realIP)) {
            return $this->realIP;
        }

        $this->realIP = $this->server('REMOTE_ADDR', '');

        //尝试获取前端代理服务器发送过来的真实IP
        $proxyIp = $this->proxyServerIp;
        $proxyIpHeader = $this->proxyServerIpHeader;

        if (count($proxyIpHeader) > 0) {
            //从指定的HTTP头中依次尝试获取IP地址
            foreach ($proxyIpHeader as $header) {
                $tempIP = $this->server($header);
                if (empty($tempIP)) {
                    continue;
                }
                $tempIP = trim(explode(',', $tempIP)[0]);
                if (!$this->isValidIP($tempIP)) {
                    $tempIP = null;
                } else {
                    break;
                }
            }
            //判断代理ip是否合法
            if (!empty($tempIP)) {
                if (count($proxyIp) > 0) {
                    $realIPBin = $this->ip2bin($this->realIP);
                    foreach ($proxyIp as $ip) {
                        $serverIPElements = explode('/', $ip);
                        $serverIP = $serverIPElements[0];
                        $serverIPPrefix = $serverIPElements[1] ?? 128;
                        $serverIPBin = $this->ip2bin($serverIP);
                        //IP类型不符
                        if (strlen($realIPBin) !== strlen($serverIPBin)) {
                            continue;
                        }
                        if (strncmp($realIPBin, $serverIPBin, (int)$serverIPPrefix) === 0) {
                            $this->realIP = $tempIP;
                            break;
                        }
                    }
                } else {
                    $this->realIP = $tempIP;
                }
            }
        }

        if (!$this->isValidIP($this->realIP)) {
            $this->realIP = '0.0.0.0';
        }

        return $this->realIP;
    }

    /**
     * 检测是否是合法的IP地址
     * @param string $ip   IP地址
     * @param string $type IP地址类型 (ipv4, ipv6)
     * @return boolean
     */
    public function isValidIP(string $ip, string $type = '')
    {
        switch (strtolower($type)) {
            case 'ipv4':
                $flag = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $flag = FILTER_FLAG_IPV6;
                break;
            default:
                $flag = null;
                break;
        }

        return boolval(filter_var($ip, FILTER_VALIDATE_IP, $flag));
    }

    /**
     * 将IP地址转换为二进制字符串
     * @param string $ip ip地址
     * @return string
     */
    public function ip2bin(string $ip)
    {
        if ($this->isValidIP($ip, 'ipv6')) {
            $IPHex = str_split(bin2hex(inet_pton($ip)), 4);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', $IPHex);
        } else {
            $IPHex = str_split(bin2hex(inet_pton($ip)), 2);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%08b%08b%08b%08b', $IPHex);
        }

        return $IPBin;
    }

    /**
     * 检测是否使用手机访问
     * @param  string $type 手机类型
     * @return bool
     */
    public function isMobile($type = '')
    {
        $userAgent = $this->server('HTTP_USER_AGENT');
        switch ($type) {
            case 'ios':
                if ($userAgent && preg_match('/(iphone|ipod|ipad)/i', $userAgent)) {
                    return true;
                }
                break;
            case 'android':
                if ($userAgent && preg_match('/android/i', $userAgent)) {
                    return true;
                }
                break;
            default:
                if ($this->server('HTTP_VIA') && stristr($this->server('HTTP_VIA'), "wap")) {
                    return true;
                } elseif ($this->server('HTTP_ACCEPT') && strpos(strtoupper($this->server('HTTP_ACCEPT')), "VND.WAP.WML")) {
                    return true;
                } elseif ($this->server('HTTP_X_WAP_PROFILE') || $this->server('HTTP_PROFILE')) {
                    return true;
                } elseif ($userAgent && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $userAgent)) {
                    return true;
                }
        }
        return false;
    }

    /**
     * 设置当前的控制器名
     * @param  string $controller 控制器名
     * @return $this
     */
    public function setController(string $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * 设置当前的操作名
     * @param  string $action 操作名
     * @return $this
     */
    public function setAction(string $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 获取当前的控制器名
     * @access public
     * @param  bool $convert 转换为小写
     * @return string
     */
    public function controller(bool $convert = false)
    {
        $name = $this->controller ? : '';
        return $convert ? strtolower($name) : $name;
    }

    /**
     * 获取当前的操作名
     * @param  bool $convert 转换为小写
     * @return string
     */
    public function action(bool $convert = false)
    {
        $name = $this->action ? : '';
        return $convert ? strtolower($name) : $name;
    }

    /**
     * 设置在中间件传递的数据
     * @param  array $middleware 数据
     * @return $this
     */
    public function withMiddleware(array $middleware)
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * 设置GET数据
     * @param  array $get 数据
     * @param  bool $all  是否全部替换
     * @return $this
     */
    public function withGet(array $get, bool $all = false)
    {
        $this->get = $all ? $get : array_merge($this->get, $get);
        return $this;
    }

    /**
     * 设置POST数据
     * @param  array $post 数据
     * @param  bool  $all  是否全部替换
     * @return $this
     */
    public function withPost(array $post, $all = false)
    {
        $this->post = $all ? $post : array_merge($this->post, $post);
        return $this;
    }

    /**
     * 设置SERVER数据
     * @param  array $server 数据
     * @param  bool  $all    是否全部替换
     * @return $this
     */
    public function withServer(array $server, $all = false)
    {
        $this->server = $all ? array_change_key_case($server, CASE_UPPER) : array_merge($this->server, array_change_key_case($server, CASE_UPPER));
        return $this;
    }

    /**
     * 设置HEADER数据
     * @param  array $header 数据
     * @param  bool  $all    是否全部替换
     * @return $this
     */
    public function withHeader(array $header, $all = false)
    {
        $this->header = $all ? array_change_key_case($header) : array_merge($this->header, array_change_key_case($header));
        return $this;
    }

    /**
     * 设置php://input数据
     * @param string $input RAW数据
     * @return $this
     */
    public function withInput(string $input)
    {
        $this->input = $input;
        if (!empty($input)) {
            $inputData = $this->getInputData($input);
            if (!empty($inputData)) {
                $this->post = $inputData;
                $this->put = $inputData;
            }
        }
        return $this;
    }

    /**
     * 设置ROUTE变量
     * @param  array $route 数据
     * @param  bool $all  是否全部替换
     * @return $this
     */
    public function withRoute(array $route, $all = false)
    {
        $this->route = $all ? $route : array_merge($this->route, $route);
        return $this;
    }

    /**
     * 设置中间传递数据
     * @param  string    $name  参数名
     * @param  mixed     $value 值
     */
    public function __set(string $name, $value)
    {
        $this->middleware[$name] = $value;
    }

    /**
     * 获取中间传递数据的值
     * @param  string $name 名称
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->middleware($name);
    }

    /**
     * 检测中间传递数据的值
     * @param  string $name 名称
     * @return boolean
     */
    public function __isset(string $name)
    {
        return isset($this->middleware[$name]);
    }

    /**
     * 解析input值
     */
    protected function getInputData($content)
    {
        $contentType = $this->contentType();
        if ($contentType == 'application/x-www-form-urlencoded') {
            parse_str($content, $data);
            return $data;
        } elseif (false !== strpos($contentType, 'json')) {
            return (array)json_decode($content, true);
        }
        return [];
    }

    /**
     * 获取数据
     * @param  array  $data    数据源
     * @param  string $name    字段名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    protected function getData(array $data, string $name, $default = null)
    {
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * 过滤值
     */
    protected function filterData($data, $filter, $name, $default)
    {
        // 解析过滤器
        $filter = $this->getFilter($filter, $default);

        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
        } else {
            $this->filterValue($data, $name, $filter);
        }

        return $data;
    }

    /**
     * 过滤值
     */
    protected function getFilter($filter, $default) : array
    {
        if (is_null($filter)) {
            $filter = [];
        } else {
            $filter = $filter ? : $this->filter;
            if (is_string($filter) && false === strpos($filter, '/')) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array)$filter;
            }
        }

        $filter[] = $default;

        return $filter;
    }

    /**
     * 强制类型转换
     * @param  mixed  $data
     * @param  string $type
     * @return mixed
     */
    private function typeCast(&$data, string $type)
    {
        switch (strtolower($type)) {
            // 数组
            case 'a':
                $data = (array)$data;
                break;
            // 数字
            case 'd':
                $data = (int)$data;
                break;
            // 浮点
            case 'f':
                $data = (float)$data;
                break;
            // 布尔
            case 'b':
                $data = (boolean)$data;
                break;
            // html格式化
            case 'h':
                $data = is_scalar($data) ? htmlspecialchars(trim((string)$data), ENT_QUOTES) : '';
                break;
            // 字符串
            case 's':
                $data = is_scalar($data) ? (string)$data : '';
                break;
        }
    }
}
