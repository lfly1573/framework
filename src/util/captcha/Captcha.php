<?php

/**
 * 验证码类
 */

namespace lfly\util\captcha;

class Captcha
{
    protected $param = 'captcha';

    /**
     * 构造函数
     */
    public function __construct($param = null)
    {
        $this->config($param);
    }

    /**
     * 配置参数方便实现一页多个验证码
     * @param int $param 验证码变量1-5
     * @return void
     */
    public function config($param = null)
    {
        if (!empty($param) && is_numeric($param)) {
            $param = intval($param);
            if ($param > 0 && $param <= 5) {
                $this->param = 'captcha' . $param;
            }
        }
    }

    /**
     * 验证码显示
     * @return Response
     */
    public function create()
    {
        //判断刷新次数
        $tryCount = $this->formatKey();
        if ($tryCount[0] > 4 && time() - $tryCount[1] <= $tryCount[0]) {
            return \Response::engine('file')->init(WEB_PATH . 'images/common/verify_busy.png')->display()->mimeType('image/png');
        }

        //设定验证码字符
        $chars = 'jpyacemnrsuvwxzbdfhktABCDEFGHJKLMNPQRSTUVWXYZ2345678';
        $charslen = strlen($chars) - 1;

        //基本设置
        $setting = ['width' => 130, 'height' => 40, 'length' => 4];

        //创建图像
        $tempimage = imagecreate($setting['width'], $setting['height']);
        $tempbgnum = rand(204, 255);
        $tempcolornum = rand(0, 136);
        $tempbackground = imagecolorallocate($tempimage, $tempbgnum, $tempbgnum, $tempbgnum);
        $tempcolor = imagecolorallocate($tempimage, $tempcolornum, $tempcolornum, $tempcolornum);

        //画杂点
        $tempnoisenum = rand($setting['height'] / 2, $setting['width'] * 2);
        for ($i = 0; $i < $tempnoisenum; $i++) {
            imagesetpixel($tempimage, rand(0, $setting['width']), rand(0, $setting['height']), $tempcolor);
        }
        //画干扰线
        $templinenum = rand(1, $setting['length']);
        for ($i = 0; $i < $templinenum; $i++) {
            $this->thickLine($tempimage, rand(0, $setting['width']), rand(0, $setting['height']), rand(0, $setting['width']), rand(0, $setting['height']), $tempcolor, rand(1, ceil($setting['height'] / 15)));
        }

        //画验证字符
        $tempcode = '';
        $tempx = 5;
        for ($i = 0; $i < $setting['length']; $i++) {
            $tempchar = rand(0, $charslen);
            $temptext = $chars[$tempchar];
            $tempcode .= $temptext;
            $tempfont = WEB_PATH . 'images/font/' . rand(1, 4) . '.ttf';
            $tempsize = rand($setting['height'] * 4 / 7, $setting['height'] * 7 / 9);
            $tempangle = rand(-20, 20);
            $tempplacex = $i * $setting['width'] / $setting['length'];
            $tempplacex = ($tempx < $tempplacex) ? rand($tempx, $tempplacex) : $tempx;
            $tempx = $tempplacex + $tempsize;
            if ($tempchar <= 4) {
                $tempyrate = 5 / 9;
            } elseif ($tempchar <= 16) {
                $tempyrate = 3 / 5;
            } else {
                $tempyrate = 7 / 9;
            }
            $tempplacey = $setting['height'] * $tempyrate;
            imagettftext($tempimage, $tempsize * 3 / 4, $tempangle, $tempplacex, $tempplacey, $tempcolor, $tempfont, $temptext);
        }

        //写入信息
        \Session::set($this->param, ($tryCount[0] + 1) . "\n" . time() . "\n" . $tempcode);

        //获得图像内容
        ob_start();
        imagepng($tempimage);
        $content = ob_get_clean();
        imagedestroy($tempimage);

        return \Response::engine('file')->init($content)->isContent()->display()->mimeType('image/png')->name($this->param);
    }

    /**
     * 校验验证码
     * @param string $value 输入的验证码值
     * @return bool
     */
    public function check($value)
    {
        $curValue = $this->formatKey();
        \Session::delete($this->param);
        if (!empty($curValue[2]) && !empty($value) && strtoupper($curValue[2]) == strtoupper($value)) {
            return true;
        }
        return false;
    }

    /**
     * 格式化已存储验证码值
     */
    private function formatKey()
    {
        return explode("\n", \Session::get($this->param, "0\n0\n0"));
    }

    /**
     * 画线条
     */
    private function thickLine($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
    {
        if ($thick == 1) {
            return imageline($image, $x1, $y1, $x2, $y2, $color);
        }
        $t = $thick / 2 - 0.5;
        if ($x1 == $x2 || $y1 == $y2) {
            return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
        }
        $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
        $a = $t / sqrt(1 + pow($k, 2));
        $points = array(
            round($x1 - (1 + $k) * $a), round($y1 + (1 - $k) * $a),
            round($x1 - (1 - $k) * $a), round($y1 - (1 + $k) * $a),
            round($x2 + (1 + $k) * $a), round($y2 - (1 - $k) * $a),
            round($x2 + (1 - $k) * $a), round($y2 + (1 + $k) * $a)
        );
        imagefilledpolygon($image, $points, 4, $color);
        return imagepolygon($image, $points, 4, $color);
    }
}
