<?php

/**
 * 继承门面类
 */

namespace lfly\facade;

use lfly\Facade;

class Response extends Facade
{
    protected static function getFacadeClass()
    {
        return 'response';
    }
}
