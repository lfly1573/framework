<?php

/**
 * 路由类
 */

namespace lfly;

use LogicException;
use Closure;

class Route
{
    /**
     * REST定义
     * @var array
     */
    protected $rest = [
        'index' => ['get', '', 'index'],
        'create' => ['get', '/create', 'create'],
        'edit' => ['get', '/{id}/edit', 'edit'],
        'read' => ['get', '/{id}', 'read'],
        'save' => ['post', '', 'save'],
        'update' => ['put', '/{id}', 'update'],
        'delete' => ['delete', '/{id}', 'delete'],
    ];

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        // 默认的路由变量规则
        'default_route_pattern' => '[\w\.\-]+',
    ];

    /**
     * 路由编号
     * @var int
     */
    protected $ruleid = 0;

    /**
     * 路由规则
     * ['preg'=>'', 'replace'=>'', 'callback'=>'', 'controller'=>'', 'method'=>'', 'attr'=>
     * ['where'=>[], 'middleware'=>[], 'append'=>[], 'namespace'=>'', 'prefix'=>'', 'name'=>'', 'https'=>true, 'ext'=>'', 'domain'=>'','template'=>'','redirect'=>'']]
     * @var array
     */
    protected $rules = [];

    /**
     * 规则索引
     * ['method'=>[], 'alias'=>[], 'path'=>[], 'default'=>'']
     * @var array
     */
    protected $indexes = [];

    /**
     * 组参数栈
     * @var array
     */
    private $groupStack = [];

    /**
     * 临时属性
     * @var array
     */
    private $attributes = [];

    /**
     * 额外规则
     * @var array
     */
    private $otherRules = [];

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
    }

    /**
     * 获取当前参数
     * @return array
     */
    public function getAttr()
    {
        return $this->attributes;
    }

    //---以下设置分组和单个method等，务必在Route设置中最后调用---------------

    /**
     * 路由组设定
     * @param callable $callback 匿名函数
     * @return void
     */
    public function group(callable $callback)
    {
        $this->app->route->updateGroupStack($this, $callback);
    }

    /**
     * get路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public function get($uri, $callback)
    {
        $this->app->route->addRule($uri, $callback, $this, ['get']);
    }

    /**
     * post路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public function post($uri, $callback)
    {
        $this->app->route->addRule($uri, $callback, $this, ['post']);
    }

    /**
     * put路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public function put($uri, $callback)
    {
        $this->app->route->addRule($uri, $callback, $this, ['put']);
    }

    /**
     * patch路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public function patch($uri, $callback)
    {
        $this->app->route->addRule($uri, $callback, $this, ['patch']);
    }

    /**
     * delete路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public function delete($uri, $callback)
    {
        $this->app->route->addRule($uri, $callback, $this, ['delete']);
    }

    /**
     * options路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public function options($uri, $callback)
    {
        $this->app->route->addRule($uri, $callback, $this, ['options']);
    }

    /**
     * 全匹配路由
     * @param string          $uri      url地址格式
     * @param string|callable $callback 对应控制器或匿名函数
     * @return void
     */
    public function any($uri, $callback)
    {
        $this->app->route->addRule($uri, $callback, $this, ['*']);
    }

    /**
     * 部分匹配路由
     * @param string          $uri         url地址格式
     * @param string|callable $callback    对应控制器或匿名函数
     * @param array           $methodArray 多个请求类型
     * @return void
     */
    public function match($uri, $callback, array $methodArray)
    {
        $this->app->route->addRule($uri, $callback, $this, $methodArray);
    }

    /**
     * 跳转路由
     * @param string $uri   url地址格式
     * @param string $goUrl 跳转的地址
     * @return void
     */
    public function redirect($uri, $goUrl)
    {
        $this->attributes['redirect'] = $goUrl;
        $this->app->route->addRule($uri, '', $this, ['get']);
    }

    /**
     * 视图路由
     * @param string $uri      url地址格式
     * @param string $template 模版文件
     * @return void
     */
    public function view($uri, $templateName)
    {
        $this->attributes['template'] = $templateName;
        $this->app->route->addRule($uri, '', $this, ['get']);
    }

    /**
     * 资源路由
     * @param string $uri        url地址格式
     * @param string $controller 控制器
     * @return void
     */
    public function resource($uri, $controller)
    {
        foreach ($this->rest as $value) {
            $this->app->route->addRule($uri . $value[1], $controller . '@' . $value[2], ('delete' == $value[2] ? $this : $this->getAttr()), [$value[0]]);
        }
    }

    /**
     * 回退路由
     * @param string|callable $callback 对应控制器或匿名函数或指定标识(auto)
     * @return void
     */
    public function fallback($callback)
    {
        $this->app->route->setDefault($callback, $this);
    }

    //---以下设置参数，分组通用------------------------------------

    /**
     * 设置域名
     * @param string $domain 绑定域名
     * @return $this
     */
    public function domain($domain)
    {
        $this->attributes['domain'] = $domain;
        return $this;
    }

    /**
     * 设置变量格式
     * @param array $args 地址中变量格式正则定义
     * @return $this
     */
    public function where(array $args)
    {
        $this->attributes['where'] = isset($this->attributes['where']) ? array_merge($this->attributes['where'], $args) : $args;
        return $this;
    }

    /**
     * 设置路由中间件
     * @param array $args 路由中间件
     * @return $this
     */
    public function middleware(array $args)
    {
        if (!is_array($args)) {
            $args = (array)$args;
        }
        $this->attributes['middleware'] = isset($this->attributes['middleware']) ? array_merge($this->attributes['middleware'], $args) : $args;
        return $this;
    }

    /**
     * 设置额外参数
     * @param array $args 在route参数中增加额外参数
     * @return $this
     */
    public function append(array $args)
    {
        $this->attributes['append'] = isset($this->attributes['append']) ? array_merge($this->attributes['append'], $args) : $args;
        return $this;
    }

    /**
     * 设置命名空间
     * @param string $namespace 命名空间前缀
     * @return $this
     */
    public function namespace($namespace)
    {
        $this->attributes['namespace'] = $namespace;
        return $this;
    }

    /**
     * 设置uri前缀
     * @param string $prefix uri前缀
     * @return $this
     */
    public function prefix($prefix)
    {
        $this->attributes['prefix'] = $prefix;
        return $this;
    }

    /**
     * 设置路由别名
     * @param string $name 路由别名
     * @return $this
     */
    public function name($name)
    {
        $this->attributes['name'] = $name;
        return $this;
    }

    /**
     * 设置是否强制https
     * @return $this
     */
    public function https()
    {
        $this->attributes['https'] = true;
        return $this;
    }

    /**
     * 设置url扩展名后缀
     * @param string|array $ext 后缀名
     * @return $this
     */
    public function ext($ext)
    {
        $this->attributes['ext'] = $ext;
        return $this;
    }

    //---以下为单例调用------------------------------------

    /**
     * path解析
     * @param \lfly\Request $request 请求参数
     * @return array
     */
    public function dispatch(Request $request)
    {
        $scheme = $request->scheme();
        $domain = $request->host();
        $method = strtolower($request->method());
        $path = $request->path();
        $ext = $request->ext();
        $uriArray = explode('/', $path);
        $rules = array_intersect(
            array_merge(
                (!empty($this->indexes['method'][$method]) ? $this->indexes['method'][$method] : []),
                (!empty($this->indexes['method']['*']) ? $this->indexes['method']['*'] : [])
            ),
            array_merge(
                (!empty($uriArray[1]) && !empty($this->indexes['path'][$uriArray[0]][$uriArray[1]]) ? $this->indexes['path'][$uriArray[0]][$uriArray[1]] : []),
                (!empty($uriArray[0]) && !empty($this->indexes['path'][$uriArray[0]]['*']) ? $this->indexes['path'][$uriArray[0]]['*'] : []),
                (!empty($this->indexes['path']['*']) ? $this->indexes['path']['*'] : [])
            )
        );
        foreach ($rules as $value) {
            $curRule = $this->rules[$value];
            $matchNum = preg_match_all($curRule['preg'], $path, $matches);
            if ($matchNum) {
                if (!empty($curRule['attr']['https']) && strtolower($scheme) != 'https' && !$this->app->isDebug()) {
                    continue;
                }
                if (!empty($curRule['attr']['ext']) && !in_array($ext, (array)$curRule['attr']['ext'])) {
                    continue;
                }
                if (!empty($curRule['attr']['domain']) && $domain != $curRule['attr']['domain'] && !$this->app->isDebug()) {
                    continue;
                }

                $routeArgs = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $routeArgs[$key] = $value[0];
                    }
                }
                if (!empty($curRule['attr']['append'])) {
                    $routeArgs += $curRule['attr']['append'];
                }

                $this->app->request->setRoute($routeArgs);
                $routeData = ['args' => $routeArgs];
                if (!empty($curRule['callback'])) {
                    $routeData['callback'] = $curRule['callback'];
                }
                if (!empty($curRule['controller'])) {
                    if (strpos($curRule['controller'], '{') !== false) {
                        foreach ($routeArgs as $key => $value) {
                            $curRule['controller'] = str_replace('{' . $key . '}', $value, $curRule['controller']);
                        }
                    }
                    $controller = $this->app->parseClassAndAction($curRule['controller']);
                    if (empty($controller)) {
                        throw new LogicException('invalid route param: ' . $curRule['controller']);
                    }
                    $routeData['controller'] = $controller[0];
                    $routeData['action'] = $controller[1];
                    $this->app->request->setController($routeData['controller']);
                    $this->app->request->setAction($routeData['action']);
                }
                if (!empty($curRule['attr']['template'])) {
                    $routeData['template'] = $curRule['attr']['template'];
                }
                if (!empty($curRule['attr']['redirect'])) {
                    $routeData['redirect'] = $curRule['attr']['redirect'];
                }
                if (!empty($curRule['attr']['middleware'])) {
                    $routeData['middleware'] = $curRule['attr']['middleware'];
                }
                return $routeData;
            }
        }
        return $this->defaultRoute($request);
    }

    /**
     * 生成url
     * @param string $name 控制器或别名
     * @param array  $args 传入参数
     * @return string
     */
    public function buildUrl($name, $args = [])
    {
        $url = '';
        $getArgs = [];
        $pre = $this->app->request->pre();
        $varPath = $this->app->config->get('var_pathinfo', false);

        if (isset($this->indexes['alias'][$name])) {
            $curRule = $this->rules[$this->indexes['alias'][$name]];
            $url = $curRule['replace'];
            if (!empty($args)) {
                $url = str_replace(array_map(function ($value) {
                    return '{' . $value . '}';
                }, array_keys($args)), array_values($args), $url);
                if (!empty($curRule['attr']['where'])) {
                    $getArgs = array_diff_key($args, $curRule['attr']['where']);
                }
            }
            $url = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $url);
            if (!empty($curRule['attr']['ext'])) {
                $url .= '.' . $curRule['attr']['ext'];
            }
            if (!empty($curRule['attr']['domain'])) {
                $pre = (!empty($curRule['attr']['https']) ? 'https://' : 'http://') . $curRule['attr']['domain'] . $this->app->request->root();
            } elseif ($this->app->request->mainDomain() == '') {
                if (!empty($curRule['attr']['https']) && $this->app->request->scheme() != 'https') {
                    $pre = 'https://' . $this->app->request->host() . $this->app->request->root();
                } else {
                    $pre = $this->app->request->root();
                }
            } else {
                if (!empty($curRule['attr']['https']) && substr($pre, 0, 5) !== 'https') {
                    $pre = str_replace('http://', 'https://', $pre);
                }
            }
        } else {
            $getArgs = $args;
            $url = str_replace(array('\\', '@'), array('.', '/'), $name);
        }

        if ($varPath) {
            return $pre . '?' . $this->app->request->varPathinfo() . '=' . urlencode($url) . (!empty($getArgs) ? '&' . http_build_query($getArgs) : '');
        } else {
            return $pre . $url . (!empty($getArgs) ? '?' . http_build_query($getArgs) : '');
        }
    }

    /**
     * 添加路由规则
     * @param string            $uri        url地址格式
     * @param string|callable   $callback   对应控制器或匿名函数
     * @param array|\lfly\Route $attributes 设置参数
     * @param array             $method     请求类型
     * @return string
     */
    public function addRule($uri, $callback, $attributes, $method = ['*'])
    {
        if (!is_array($method)) {
            $method = [$method];
        }
        if (is_array($attributes)) {
            $attr = $attributes;
        } else {
            $attr = $attributes->getAttr();
            unset($attributes);
        }

        $ruleArray = [];
        if (!empty($this->groupStack)) {
            $attr = $this->mergeAttribute(end($this->groupStack), $attr);
        }
        $isEnd = substr($uri, -1) == '$' ? true : false;
        if ($isEnd) {
            $uri = substr($uri, 0, -1);
        }
        if (!empty($attr['prefix'])) {
            $uri = $attr['prefix'] . '/' . $uri;
        }
        $matchNum = preg_match_all('/\{([a-zA-Z_]\w*\??)\}/', $uri, $matches);
        if ($matchNum) {
            $pregArray = $replaceArray = [];
            foreach ($matches[0] as $key => $value) {
                $isEmpty = substr($matches[1][$key], -1) == '?' ? true : false;
                $curParam = $isEmpty ? substr($matches[1][$key], 0, -1) : $matches[1][$key];
                if (!isset($attr['where'][$curParam])) {
                    $attr['where'][$curParam] = $this->config['default_route_pattern'];
                }
                $pregArray[preg_quote($value, '/')] = '(?<' . $curParam . '>' . $attr['where'][$curParam] . ')' . ($isEmpty ? '?' : '');
                $replaceArray[$value] = '{' . $curParam . '}';
            }
            $preg = str_replace(array_keys($pregArray), array_values($pregArray), preg_quote($uri, '/'));
            $replace = str_replace(array_keys($replaceArray), array_values($replaceArray), $uri);
        } else {
            $preg = preg_quote($uri, '/');
            $replace = $uri;
        }

        $ruleArray['preg'] = '/^' . $preg . ($isEnd ? '$' : '') . '/';
        $ruleArray['replace'] = $replace;
        if ($callback instanceof Closure) {
            $ruleArray['callback'] = $callback;
        } elseif ($callback != '') {
            $ruleArray['controller'] = (!empty($attr['namespace']) ? $attr['namespace'] . '\\' : '') . $callback;
        }
        if (!empty($attr)) {
            $ruleArray['attr'] = $attr;
        }
        foreach ($method as $value) {
            $ruleArray['method'] = $value;
            $this->saveRule($ruleArray);
        }
    }

    /**
     * 执行路由解析
     */
    public function loadFile()
    {
        $cacheFile = CACHE_PATH . 'route' . EXT;
        $cacheTime = is_file($cacheFile) ? filemtime($cacheFile) : 0;
        $maxTime = 0;
        $files = [];
        foreach (glob(ROUTER_PATH . '*' . EXT) as $filename) {
            $maxTime = max($maxTime, filemtime($filename));
            $files[] = $filename;
        }
        if ($cacheTime > 0 && $cacheTime >= $maxTime) {
            $config = (include $cacheFile);
            $this->ruleid = $config['ruleid'];
            $this->rules = $config['rules'];
            $this->indexes = $config['indexes'];
        } else {
            foreach ($files as $filename) {
                include_once $filename;
            }
            Cache::writeFile($cacheFile, ['ruleid' => $this->ruleid, 'rules' => $this->rules, 'indexes' => $this->indexes]);
        }
        foreach ($this->otherRules as $value) {
            call_user_func($value);
        }
    }

    /**
     * 压入组参数
     * @param \lfly\Route $groupObj 组对象
     * @param callable    $callback 回调函数
     * @return array
     */
    public function updateGroupStack(Route $groupObj, callable $callback)
    {
        if (!empty($this->groupStack)) {
            $this->groupStack[] = $this->mergeAttribute(end($this->groupStack), $groupObj->getAttr());
        } else {
            $this->groupStack[] = $groupObj->getAttr();
        }
        unset($groupObj);
        $callback();
        array_pop($this->groupStack);
    }

    /**
     * 添加额外路由
     * @param Closure $func 闭包函数
     * @return void
     */
    public function extendRule(Closure $func)
    {
        $this->otherRules[] = $func;
    }

    /**
     * 设置未匹配的默认操作
     * @param string|callable $callback 回调函数
     * @param \lfly\Route     $routeObj 调用的route类
     * @return array
     */
    public function setDefault($callback, Route $routeObj = null)
    {
        if (!empty($routeObj)) {
            unset($routeObj);
        }
        if (empty($this->indexes['default'])) {
            $this->indexes['default'] = $callback;
        }
    }

    //---以下为内部调用------------------------------------

    /**
     * 默认路由解析
     */
    protected function defaultRoute(Request $request)
    {
        if (!empty($this->indexes['default'])) {
            if (is_string($this->indexes['default'])) {
                if ($this->indexes['default'] == 'auto') {
                    $path = $request->path();
                    $uriArray = explode('/', $path);
                    if (isset($uriArray[1]) && preg_match('/^([a-zA-Z][a-zA-Z0-9]*)(\.[a-zA-Z][a-zA-Z0-9]*)*$/', $uriArray[0]) && preg_match('/^([a-zA-Z][a-zA-Z0-9]*)$/', $uriArray[1])) {
                        return ['controller' => $this->app->parseClass($uriArray[0]), 'action' => $uriArray[1], 'args' => []];
                    }
                } else {
                    $controller = $this->app->parseClassAndAction($this->indexes['default']);
                    if (!empty($controller)) {
                        return ['controller' => $controller[0], 'action' => $controller[1], 'args' => []];
                    }
                }
            } elseif ($this->indexes['default'] instanceof Closure) {
                return ['callback' => $this->indexes['default'], 'args' => []];
            }
        }
        return [];
    }

    /**
     * 参数合并
     * @param array  $oldAttr 旧参数
     * @param array  $newAttr 新参数
     * @return array
     */
    protected function mergeAttribute(array $oldAttr, array $newAttr)
    {
        $return = $oldAttr;
        if (!empty($newAttr['where'])) {
            $return['where'] = isset($return['where']) ? array_merge($return['where'], $newAttr['where']) : $newAttr['where'];
        }
        if (!empty($newAttr['middleware'])) {
            $return['middleware'] = isset($return['middleware']) ? array_merge($return['middleware'], $newAttr['middleware']) : $newAttr['middleware'];
        }
        if (!empty($newAttr['append'])) {
            $return['append'] = isset($return['append']) ? array_merge($return['append'], $newAttr['append']) : $newAttr['append'];
        }
        if (!empty($newAttr['namespace'])) {
            $return['namespace'] = isset($return['namespace']) ? $return['namespace'] . '\\' . $newAttr['namespace'] : $newAttr['namespace'];
        }
        if (!empty($newAttr['prefix'])) {
            $return['prefix'] = isset($return['prefix']) ? $return['prefix'] . '/' . $newAttr['prefix'] : $newAttr['prefix'];
        }
        if (!empty($newAttr['name'])) {
            $return['name'] = isset($return['name']) ? $return['name'] . '.' . $newAttr['name'] : $newAttr['name'];
        } else {
            $return['name'] = '';
        }
        if (!empty($newAttr['https'])) {
            $return['https'] = $newAttr['https'];
        }
        if (!empty($newAttr['ext'])) {
            $return['ext'] = $newAttr['ext'];
        }
        if (!empty($newAttr['domain'])) {
            $return['domain'] = $newAttr['domain'];
        }
        return $return;
    }

    /**
     * 保存规则
     * @param array  $rule 规则
     * @return void
     */
    protected function saveRule(array $ruleArray)
    {
        $this->ruleid++;
        $this->rules[$this->ruleid] = $ruleArray;
        $this->indexes['method'][$ruleArray['method']][] = $this->ruleid;
        if (in_array($ruleArray['method'], ['post', 'get', '*'])) {
            if (!empty($ruleArray['controller']) && !isset($this->indexes['alias'][$ruleArray['controller']])) {
                $this->indexes['alias'][$ruleArray['controller']] = $this->ruleid;
            }
            if (!empty($ruleArray['attr']['name']) && !isset($this->indexes['alias'][$ruleArray['attr']['name']])) {
                $this->indexes['alias'][$ruleArray['attr']['name']] = $this->ruleid;
            }
        }
        $uriArray = explode('/', $ruleArray['replace']);
        if (!empty($uriArray[0]) && strpos($uriArray[0], '{') === false) {
            if (!empty($uriArray[1]) && strpos($uriArray[1], '{') === false) {
                $this->indexes['path'][$uriArray[0]][$uriArray[1]][] = $this->ruleid;
            } else {
                $this->indexes['path'][$uriArray[0]]['*'][] = $this->ruleid;
            }
        } else {
            $this->indexes['path']['*'][] = $this->ruleid;
        }
    }
}
