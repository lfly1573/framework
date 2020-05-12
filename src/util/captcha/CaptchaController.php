<?php

/**
 * 验证码控制器类
 */

namespace lfly\util\captcha;

use lfly\Controller;

class CaptchaController extends Controller
{
    public function index(Captcha $code, $param = null)
    {
        if (empty($this->request->comeUrl(true))) {
            //$this->showMessage(404);
        }
        $code->config($param);
        return $code->create();
    }
}
