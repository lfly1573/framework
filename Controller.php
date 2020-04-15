<?php

/**
 * 控制器基类
 */

namespace lfly;

use lfly\exception\HttpResponseException;

class Controller
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * Request实例
     * @var Request
     */
    protected $request;

    /**
     * 控制器中间件定义
     */
    //protected $middleware = ['类名1', '类名2:参数', '类名3' => ['except' => ['方法3']], '类名4' => ['only' => ['方法4']]];

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        // 控制器初始化
        $this->init();
    }

    /**
     * 提示信息
     * @param  int    $code    状态码 0为正确，其他值为错误 100-600为http状态码使用
     * @param  mixed  $message 消息内容
     * @param  array  $data    详细数据
     * @param  array  $extra   扩展设定 ['url'=>'跳转地址', 'ctrl'=>'stop/back', 'tpl'=>'设定模版', 'link'=>[['更多链接标题','更多链接地址']]]
     * @param  string $type    数据类型 json
     * @return void
     * 
     * @throws HttpResponseException
     */
    public function showMessage(int $code, $message, array $data = [], array $extra = [], $type = '')
    {
        $return = ['code' => $code, 'message' => $message, 'data' => $data, 'extra' => $extra];
        $pageCode = ($code >= 100 && $code <= 600) ? $code : 200;
        if ($type == 'json' || (empty($type) && ($this->request->isJson() || $this->request->isAjax() || $this->request->isPjax()))) {
            unset($return['extra']);
            if (is_array($message)) {
                $return['message'] = implode("\n", $message);
            }
            if ($this->request->param('callback', '') != '') {
                $response = $this->app->response->engine('jsonp')->init($this->formatMessage($return, 'jsonp'), $pageCode);
            } else {
                $response = $this->app->response->engine('json')->init($this->formatMessage($return, 'json'), $pageCode);
            }
        } else {
            if ($pageCode != 200) {
                $response = $this->app->response->init($this->formatMessage($return), $pageCode)->setTemplate($pageCode);
            } else {
                $response = $this->app->response->init($this->formatMessage($return), $pageCode)->setTemplate(isset($return['extra']['tpl']) ? $return['extra']['tpl'] : 'show_message');
            }
        }
        throw new HttpResponseException($response);
    }

    /**
     * 提示信息json版
     * @param int   $code    状态码
     * @param mixed $message 消息内容
     * @param array $data    详细数据
     */
    public function json(int $code, $message, array $data = [])
    {
        $this->showMessage($code, $message, $data, [], 'json');
    }

    /**
     * 分页计算
     * @param int    $allNum  总数
     * @param int    $perNum  每页显示数
     * @param int    $curPage 当前页数
     * @param string $linkUrl 链接地址
     * @param int    $linkNum 链接页数个数
     * @param int    $maxPage 最大页数
     * @param string $sign    链接地址中替换符号
     * @return array
     */
    public function page(int $allNum, int $perNum, int $curPage, string $linkUrl = '', int $linkNum = 7, int $maxPage = 0, string $sign = '@@')
    {
        $allNum = abs(intval($allNum));
        $perNum = max(1, intval($perNum));
        $curPage = max(1, intval($curPage));
        $linkNum = max(1, intval($linkNum));
        $maxPage = abs(intval($maxPage));
        $return = array();

        if ($allNum > 0) {
            $allAbsPages = $allPages = $startPage = $endPage = 0;
            $allAbsPages = ceil($allNum / $perNum);
            $allPages = ($maxPage > 0 && $maxPage < $allAbsPages) ? $maxPage : $allAbsPages;
            $curPage = ($curPage > $allPages) ? $allPages : $curPage;

            $startPage = $curPage - floor($linkNum / 2);
            $endPage = $curPage + floor($linkNum / 2);
            if ($startPage > 0 && $endPage > $allPages) {
                $startPage = ($endPage - $allPages < $startPage) ? $startPage - $endPage + $allPages : 1;
                $endPage = $allPages;
            } elseif ($startPage <= 0 && $endPage <= $allPages) {
                $endPage = ($endPage - $startPage + 1 <= $allPages) ? $endPage - $startPage + 1 : $allPages;
                $startPage = 1;
            } elseif ($startPage <= 0 && $endPage > $allPages) {
                $startPage = 1;
                $endPage = $allPages;
            }

            $return['allNum'] = $allNum;
            $return['perNum'] = $perNum;
            $return['pageNum'] = $allPages;
            $return['curPage'] = $curPage;
            $return['page'] = array();

            if ($linkUrl != '') {
                $return['prev'] = ($curPage > 1) ? str_replace($sign, $curPage - 1, $linkUrl) : '';
                $return['first'] = ($startPage > 1) ? str_replace($sign, 1, $linkUrl) : '';
                $return['front'] = ($startPage > 2) ? str_replace($sign, ceil((1 + $startPage) / 2), $linkUrl) : '';
                for ($i = $startPage; $i <= $endPage; $i++) {
                    $return['page'][$i] = str_replace($sign, $i, $linkUrl);
                }
                $return['back'] = ($endPage < $allPages - 1) ? str_replace($sign, ceil(($endPage + $allPages) / 2), $linkUrl) : '';
                $return['last'] = ($endPage < $allPages) ? str_replace($sign, $allPages, $linkUrl) : '';
                $return['next'] = ($curPage < $allPages) ? str_replace($sign, $curPage + 1, $linkUrl) : '';
                $return['input'] = ($startPage > 2 || $endPage < $allPages - 1) ? 'onKeyDown="javascript:if(window.event.keyCode==13 && this.value!=\'\'){window.location=\'' . str_replace($sign, '\'+this.value+\'', $linkUrl) . '\';}"' : '';
            } else {
                $return['prev'] = ($curPage > 1) ? $curPage - 1 : 0;
                $return['first'] = ($startPage > 1) ? 1 : 0;
                $return['front'] = ($startPage > 2) ? ceil((1 + $startPage) / 2) : 0;
                for ($i = $startPage; $i <= $endPage; $i++) {
                    $return['page'][$i] = $i;
                }
                $return['back'] = ($endPage < $allPages - 1) ? ceil(($endPage + $allPages) / 2) : 0;
                $return['last'] = ($endPage < $allPages) ? $allPages : 0;
                $return['next'] = ($curPage < $allPages) ? $curPage + 1 : 0;
                $return['input'] = ($startPage > 2 || $endPage < $allPages - 1) ? 1 : 0;
            }
        }

        return $return;
    }

    /**
     * 后台运行脚本
     * @param string $file  运行文件
     * @param string $param 参数
     * @return string|bool
     */
    public function runPhp($file = '', $param = '')
    {
        static $realPath = '';
        if ($realPath == '') {
            if ($this->app->runningInWindows()) {
                $extensionDir = @ini_get('extension_dir');
                if (!empty($extensionDir)) {
                    $phpPath = str_replace('\\', '/', $extensionDir);
                    $phpPath = preg_replace('/\/ext\/?$/i', '', $phpPath);
                    $realPath = $phpPath . '/php.exe';
                } else {
                    $realPath = 'php';
                }
            } else {
                $realPath = PHP_BINDIR . '/php';
            }
        }
        if ($file != '') {
            pclose(popen(($this->app->runningInWindows() ? 'start ' : '') . "{$realPath} -f {$file}" . ($param != '' ? " {$param}" : '') . ' &', 'r'));
            return true;
        } else {
            return $realPath;
        }
    }

    /**
     * 自定义初始化
     */
    protected function init()
    {
    }

    /**
     * 对提示信息数据进行二次处理
     * @param  array $data  数据
     * @param  string $type 数据类型 json/jsonp
     * @return array
     */
    protected function formatMessage($data, $type = '')
    {
        return $data;
    }
}
