<?php

/**
 * 服务提供者
 */

namespace lfly\contract;

use lfly\App;

abstract class ServiceHandler
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 服务注册
     * @return void
     */
    public function register()
    {
    }

    /**
     * 服务启动
     * @return void
     */
    public function boot()
    {
    }
}
