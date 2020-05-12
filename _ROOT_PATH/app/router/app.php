<?php

/**
 * 路由配置
 */

/*
可用参数配置：
Route::打头
->group(function () {})
->domain($domain)
->where(['id' => '[0-9]+'])
->middleware(['first', 'second'])
->namespace('命名空间')
->prefix('uri前缀')
->name('别名')
->https()
->ext('html')
->append(['otherParam' => 1])

以下结尾：
->get($uri, $callback);
->post($uri, $callback);
->put($uri, $callback);
->patch($uri, $callback);
->delete($uri, $callback);
->options($uri, $callback);
部分匹配：
->match($uri, $callback, ['get', 'post']);
全匹配：
->any($uri, $callback);

路由跳转：
Route::redirect($uri, $url);
路由模版：
Route::view($uri, $template);
资源路由：
Route::resource($uri, $controller);
未匹配默认配置('auto'：表示path格式为 控制器/方法)：
Route::fallback($callback);

变量格式：{name}
可不存在变量：{name?}
完全匹配结尾：$
 */

Route::get('$', 'Index@index');
Route::fallback(function () {
    return \App::getInstance()->response->init(['code' => 0, 'message' => '功能不存在或正在建设中……', 'extra' => ['url' => \Request::pre(), 'ctrl' => 'stop']], 404)->setTemplate('message');
});
