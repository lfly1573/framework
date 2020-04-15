<?php

/**
 * 继承门面类
 */

namespace lfly\facade;

use lfly\Facade;

class File extends Facade
{
    protected static $alwaysNewInstance = true;

    protected static function getFacadeClass()
    {
        return 'file';
    }
}
