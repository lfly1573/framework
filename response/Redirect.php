<?php

/**
 * json输出
 */

namespace lfly\response;

use lfly\Response;

class Redirect extends Response
{
    protected $engine = 'redirect';

    protected function output($data)
    {
        if (!in_array($this->getCode(), array(301, 302))) {
            $this->setCode(301);
        }
        $this->cacheControl('no-cache,must-revalidate');
        $this->header['Location'] = $data;
        return '';
    }
}
