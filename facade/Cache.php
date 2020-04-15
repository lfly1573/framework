<?php

/**
 * 继承门面类
 */

namespace lfly\facade;

use lfly\Facade;

class Cache extends Facade
{
    protected static $init = 'facade';

    protected static function getFacadeClass()
    {
        return 'cache';
    }
}
