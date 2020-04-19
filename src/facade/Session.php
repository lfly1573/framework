<?php

/**
 * 继承门面类
 */

namespace lfly\facade;

use lfly\Facade;

class Session extends Facade
{
    protected static function getFacadeClass()
    {
        return 'session';
    }
}
