<?php

/**
 * 验证码注册服务类
 * 在 APP_PATH/config/service.php 中增加 '\\lfly\\util\\captcha\\CaptchaService' 启用验证码
 */

namespace lfly\util\captcha;

class CaptchaService
{
    public function boot()
    {
        \Route::extendRule(function () {
            \Route::name('captcha')->get('captcha/{param?}$', "\\lfly\\util\\captcha\\CaptchaController@index");
        });

        \Validate::maker(function ($validate) {
            $validate->extend('captcha', function ($value, $rule) {
                $captcha = new \lfly\util\captcha\Captcha($rule);
                return $captcha->check($value);
            }, ':attribute输入错误');
        });
    }
}
