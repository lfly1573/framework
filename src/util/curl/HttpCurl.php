<?php

/**
 * 网络请求类
 */

namespace lfly\util\curl;

class HttpCurl
{
    private $ch = null;
    private $ca = LFLY_PATH . 'util/curl/cacert.pem';
    private $config = ['timeout' => 30, 'header' => [], 'ca' => '', 'userAgent' => '', 'showHeader' => 0, 'noBody' => 0, 'url' => '', 'param' => '', 'format' => 'text'];
    private $error = '';
    private $status = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->ch = curl_init();
        if (!empty($this->ca)) {
            $this->setCa($this->ca);
        }
    }

    /**
     * 获取当前资源
     * @return resource
     */
    public function getRes()
    {
        return $this->ch;
    }

    /**
     * 获取错误
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取状态
     * @param $string $key 键，为空返回全部
     * @return mixed
     */
    public function getStatus($key = '')
    {
        if ($key == '') {
            return $this->status;
        }
        return $this->status[$key] ?? null;
    }

    /**
     * 设置header
     * @param array $header 头部数据
     * @return $this
     */
    public function setHeader(array $header)
    {
        $this->config['header'] = array_merge($this->config['header'], $header);
        return $this;
    }

    /**
     * 设置超时时间
     * @param int $timeout 超时秒数
     * @return $this
     */
    public function setTimeout(int $timeout)
    {
        $this->config['timeout'] = max(3, $timeout);
        return $this;
    }

    /**
     * 设置用户代理
     * @param string $agent
     * @return $this
     */
    public function setUserAgent(string $agent)
    {
        $this->config['userAgent'] = $agent;
        return $this;
    }

    /**
     * 响应是否显示header
     * @param int $show 是否显示
     * @return $this
     */
    public function setShowHeader(int $show = 1)
    {
        $this->config['showHeader'] = $show;
        return $this;
    }

    /**
     * 使用head请求不返回body
     * @param int $value 是否不返回
     * @return $this
     */
    public function setNoBody(int $value = 1)
    {
        $this->config['noBody'] = $value;
        return $this;
    }

    /**
     * 设置请求地址
     * @param string $url 地址
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->config['url'] = $url;
        return $this;
    }

    /**
     * 设置请求参数
     * @param array|string $param 参数
     * @return $this
     */
    public function setParam($param)
    {
        $this->config['param'] = $param;
        return $this;
    }

    /**
     * 设置输出格式
     * @param string $format 输出格式
     * @return $this
     */
    public function setFormat(string $format)
    {
        $this->config['format'] = $format;
        return $this;
    }

    /**
     * 设置证书路径
     * @param string $file 文件路径
     * @return $this
     */
    public function setCa($file)
    {
        $this->config['ca'] = $file;
        return $this;
    }

    /**
     * 设置代理
     * @param string $proxy 代理地址
     * @return $this
     */
    public function setProxy(string $proxy)
    {
        curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
        return $this;
    }

    /**
     * 设置代理端口
     * @param int $port 代理端口
     * @return $this
     */
    public function setProxyPort(int $port)
    {
        curl_setopt($this->ch, CURLOPT_PROXYPORT, $port);
        return $this;
    }

    /**
     * 设置来源页面
     * @param string $referer 来源页面
     * @return $this
     */
    public function setReferer(string $referer)
    {
        curl_setopt($this->ch, CURLOPT_REFERER, $referer);
        return $this;
    }

    /**
     * 模拟GET请求
     * @param string       $url    地址
     * @param string|array $param  参数
     * @param string       $format 格式化
     * @return mixed
     */
    public function get($url = '', $param = '', $format = '')
    {
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($param)) {
            $this->setParam($param);
        }
        if (!empty($format)) {
            $this->setFormat($format);
        }
        if (!empty($this->config['param'])) {
            $this->config['url'] .= ((strpos($this->config['url'], '?') !== false) ? '&' : '?') . (is_array($this->config['param']) ? http_build_query($this->config['param']) : $this->config['param']);
        }
        $this->init();
        curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
        return $this->run();
    }

    /**
     * 模拟POST请求
     * @param string       $url    地址
     * @param string|array $param  参数
     * @param string       $format 格式化
     * @return mixed
     */
    public function post($url = '', $param = '', $format = '')
    {
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($param)) {
            $this->setParam($param);
        }
        if (!empty($format)) {
            $this->setFormat($format);
        }
        $this->init();
        curl_setopt($this->ch, CURLOPT_POST, true);
        $this->setField();
        return $this->run();
    }

    /**
     * 模拟PUT请求
     * @param string       $url    地址
     * @param string|array $param  参数
     * @param string       $format 格式化
     * @return mixed
     */
    public function put($url = '', $param = '', $format = '')
    {
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($param)) {
            $this->setParam($param);
        }
        if (!empty($format)) {
            $this->setFormat($format);
        }
        $this->init();
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->setField();
        return $this->run();
    }

    /**
     * 模拟DELETE请求
     * @param string       $url    地址
     * @param string|array $param  参数
     * @param string       $format 格式化
     * @return mixed
     */
    public function delete($url = '', $param = '', $format = '')
    {
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($param)) {
            $this->setParam($param);
        }
        if (!empty($format)) {
            $this->setFormat($format);
        }
        $this->init();
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->setField();
        return $this->run();
    }

    private function init()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->config['url']);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, min(10, $this->config['timeout']));
        if (stripos($this->config['url'], 'https://') === 0) {
            if (!empty($this->config['ca'])) {
                curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);	//只信任CA颁布的证书
                curl_setopt($this->ch, CURLOPT_CAINFO, $this->config['ca']);	//CA根证书（用来验证的网站证书是否是CA颁布）
                curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);	//检查证书中是否设置域名，并且是否与提供的主机名匹配
            } else {
                curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);	//信任任何证书
                curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);	//不检查证书
            }
        }
        curl_setopt($this->ch, CURLOPT_USERAGENT, empty($this->config['userAgent']) ? $_SERVER['HTTP_USER_AGENT'] : $this->config['userAgent']);	//模拟用户使用的浏览器
        if ($this->config['noBody']) {
            curl_setopt($this->ch, CURLOPT_HEADER, true);
            curl_setopt($this->ch, CURLOPT_NOBODY, true);
        } else {
            curl_setopt($this->ch, CURLOPT_HEADER, $this->config['showHeader']); // 显示返回的Header区域内容
        }
        if ($this->config['showHeader']) {
            curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
        }
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);	//获取的信息以文件流的形式返回
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array_merge($this->config['header'], ['Expect:']));
    }

    private function setField()
    {
        if (!empty($this->config['param'])) {
            if (is_array($this->config['param'])) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->config['param']));
            } else if (is_string($this->config['param'])) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->config['param']);
            }
        }
    }

    private function run()
    {
        $content = curl_exec($this->ch);
        $this->status = curl_getinfo($this->ch);
        if ($content === false) {
            $this->error = curl_error($this->ch);
        }
        curl_close($this->ch);
        if ($content !== false) {
            if ($this->config['format'] == 'json') {
                $content = @json_decode($content, true);
            }
            return $content;
        }
        return false;
    }
}
