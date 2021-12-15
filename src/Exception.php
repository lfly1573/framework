<?php

/**
 * 错误处理类
 */

namespace lfly;

use Throwable;
use ErrorException;

class Exception
{
    /**
     * 是否json请求
     * @var bool
     */
    protected $isJson = false;

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
     * 错误报告
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        $data = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $this->getCode($exception),
            'message' => $exception->getMessage(),
        ];
        try {
            $this->app->log->record(json_encode($data), 'error');
        } catch (\Exception $e) {
        }
    }

    /**
     * 显示错误信息
     * @param Request   $request
     * @param Throwable $e
     * @return \lfly\Response
     */
    public function render($request, Throwable $e) : Response
    {
        $this->isJson = $request->isJson();
        $data = $this->convertExceptionToArray($e);
        if ($this->isJson) {
            return $this->app->response->engine('json')->init($data, 500);
        } else {
            return $this->app->response->init($data, 500)->setTemplate('error');
        }
    }

    /**
     * 获取调试信息
     * @param bool $more 获取更多
     * @return array
     */
    public function debug($more = false)
    {
        $return = [
            'Route Data' => $this->app->request->route(),
            'GET Data' => $this->app->request->get(),
            'POST Data' => $this->app->request->post(),
            'Files' => $this->app->file->getFile(),
            'Cookies' => $this->app->cookie->getAll(),
            'Session' => $this->app->session->getAll(),
            'Db' => $this->app->db->getLog(),
        ];
        if ($more) {
            $return['Server Data'] = $this->app->request->server();
            $return['Constants'] = $this->getConst();
        }
        $return['Runtime'] = $this->app->runtimeInfo();
        $return['Runtime']['controller'] = $this->app->request->controller();
        $return['Runtime']['action'] = $this->app->request->action();
        return $return;
    }

    /**
     * 收集异常数据
     * @param Throwable $exception
     * @return array
     */
    protected function convertExceptionToArray(Throwable $exception)
    {
        if ($this->app->isDebug()) {
            $traces = [];
            $nextException = $exception;
            do {
                $traces[] = [
                    'name' => get_class($nextException),
                    'file' => $nextException->getFile(),
                    'line' => $nextException->getLine(),
                    'code' => $this->getCode($nextException),
                    'message' => $nextException->getMessage(),
                    'trace' => $nextException->getTrace(),
                    'source' => $this->getSourceCode($nextException),
                ];
            } while ($nextException = $nextException->getPrevious());
            $data = [
                'code' => $this->getCode($exception),
                'message' => $exception->getMessage(),
                'traces' => $traces,
                'tables' => $this->debug(true),
            ];
        } else {
            // 部署模式仅显示 Code 和 Message
            $data = [
                'code' => $this->getCode($exception),
                'message' => $exception->getMessage(),
            ];
        }
        return $data;
    }

    /**
     * 获取错误编码
     * @param Throwable $exception
     * @return integer 错误编码
     */
    protected function getCode(Throwable $exception)
    {
        $code = $exception->getCode();
        if (!$code && $exception instanceof ErrorException) {
            $code = $exception->getSeverity();
        }
        if ($code == 0) {
            $code = -1;
        }
        return $code;
    }

    /**
     * 获取出错文件内容
     * 获取错误的前9行和后9行
     * @param Throwable $exception
     * @return array  错误文件内容
     */
    protected function getSourceCode(Throwable $exception)
    {
        $line = $exception->getLine();
        $first = ($line - 9 > 0) ? $line - 9 : 1;
        try {
            $contents = file($exception->getFile()) ? : [];
            $source = [
                'first' => $first,
                'source' => array_slice($contents, $first - 1, 19),
            ];
        } catch (Exception $e) {
            $source = [];
        }
        return $source;
    }

    /**
     * 获取常量列表
     * @return array 常量列表
     */
    protected function getConst()
    {
        $const = get_defined_constants(true);
        return $const['user'] ?? [];
    }
}
