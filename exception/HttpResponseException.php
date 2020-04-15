<?php

/**
 * 中断后续运行立即输出
 */

namespace lfly\exception;

use RuntimeException;
use lfly\Response;

class HttpResponseException extends RuntimeException
{
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
