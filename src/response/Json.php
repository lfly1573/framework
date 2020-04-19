<?php

/**
 * json输出
 */

namespace lfly\response;

use lfly\Response;

class Json extends Response
{
    protected $options = [
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
    ];
    protected $engine = 'json';
    protected $contentType = 'application/json';

    protected function output($data)
    {
        try {
            if (!is_string($data) || $data[0] != '{') {
                $data = json_encode($data, $this->options['json_encode_param']);
            }

            if (false === $data) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }

            return $data;
        } catch (\Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }
}
