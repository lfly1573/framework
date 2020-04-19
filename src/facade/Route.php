<?php

/**
 * 继承门面类
 */

namespace lfly\facade;

use lfly\Facade;

class Route extends Facade
{
    protected static $alwaysNewInstance = true;
    protected static $specialMethod = ['dispatch', 'buildUrl', 'addRule', 'updateGroupStack', 'setDefault', 'loadFile'];

    protected static function getFacadeClass()
    {
        return 'route';
    }
}
