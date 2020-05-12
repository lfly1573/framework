<?php

/**
 * 数据验证类
 */

namespace lfly;

use Closure;
use lfly\util\Crypto;

/**
 * 数据验证类
 * 对输入变量直接调用方法：\Validate::load(验证器类)->batch(是否批量)->scene(场景值)->checkRequest([验证规则])
 * 自定义数据验证: \Validate::load(验证器类)->batch(是否批量)->scene(场景值)->check([数据], [验证规则])
 * 验证器类默认放在 app\validate\ 目录下，继承该类设定 $rule 以及 $scene 的值，或者自定义验证方法等
 * $scene场景值设定示例：
 * protected $scene = ['edit' => ['ruleKey(复用rule中的定义)', 'age'=>'自定义验证']];
 * 验证示例（批量验证错误返回数组，否则返回字符串）：
 * $v = \Validate::batch()->checkRequest(['userName,user_name|用户名' => 'require|alphaDash|length:5,20']);
 * if (!$v->getResult()) { $error = $v->getError(); } else { $data = $v->getFormattedData(); }
 */
class Validate
{
    /**
     * 当前验证规则 ['param,formattedParam|文字描述'=>'require(必须)|int(类型)|>:100(额外条件))']
     * @var array
     */
    protected $rule = [];

    /**
     * 验证提示信息
     * @var array
     */
    protected $message = [];

    /**
     * 验证正则定义
     * @var array
     */
    protected $regex = [];

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batch = false;

    /**
     * 当前验证场景
     * @var string
     */
    protected $currentScene;

    /**
     * 验证场景定义
     * @var array
     */
    protected $scene = [];

    /**
     * 验证失败错误信息
     * @var array
     */
    protected $error = [];

    /**
     * 验证数据初始值
     * @var array
     */
    protected $originalData = [];

    /**
     * 验证数据格式化值
     * @var array
     */
    protected $formattedData = [];

    /**
     * 验证类型别名
     * @var array
     */
    protected $alias = [
        '>' => 'gt', '>=' => 'egt', '<' => 'lt', '<=' => 'elt', '=' => 'eq', '!=' => 'ne',
    ];

    /**
     * 类型验证规则
     * @var array
     */
    protected $type = [
        'int' => ['/^(-?[1-9][0-9]*|0)$/', '\\intval'],
        'number' => ['\\is_numeric', '\\floatval'],
        'alpha' => ['/^[A-Za-z]+$/', null],
        'alphaNum' => ['/^[A-Za-z0-9]+$/', null],
        'alphaDash' => ['/^[A-Za-z0-9\-\_]+$/', null],
        'alphaSign' => ['/^[A-Z]+[0-9A-Z_]*$/i', null],
        'enChar' => ['/^[\x21-\x7E]+$/', null],
        'chs' => ['/^[\x{4e00}-\x{9fa5}]+$/u', null],
        'chsAlpha' => ['/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u', null],
        'chsAlphaNum' => ['/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u', null],
        'chsAlphaDash' => ['/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u', null],
        'mobile' => ['/^1[3-9]\d{9}$/', null],
        'price' => ['/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/', null],
        'email' => ['isEmail', null],
        'bool' => [null, 'convBool'],
        'trim' => [null, '\\trim'],
        'str' => [null, 'convHtml'],
        'url' => ['isUrl', null],
        'date' => ['isDate', null],
        'dateTime' => ['isDateTime', null],
    ];

    /**
     * 默认规则错误信息
     * @var array
     */
    protected $typeMsg = [
        'require' => ':attribute必须设定',      //require (必填)
        'default' => ':attribute格式不正确',    //default:0 (默认值)

        'int' => ':attribute必须为整数',
        'number' => ':attribute必须为数字',
        'alpha' => ':attribute必须为字母组成',
        'alphaNum' => ':attribute必须为数字或字母组成',
        'alphaDash' => ':attribute必须为数字、字母、中横线或下划线组成',
        'alphaSign' => ':attribute必须为字母开头且由字母、数字或下划线组成',
        'enChar' => ':attribute必须为英文字符组成',
        'chs' => ':attribute必须为汉字组成',
        'chsAlpha' => ':attribute必须为汉字或字母组成',
        'chsAlphaNum' => ':attribute必须为汉字、字母或数字组成',
        'chsAlphaDash' => ':attribute必须为汉字、字母、数字、中横线或下划线组成',
        'mobile' => ':attribute格式错误',
        'price' => ':attribute不是一个有效的价格',
        'email' => ':attribute不是一个有效的格式',
        'url' => ':attribute不是一个合法的url地址',
        'date' => ':attribute不是一个正确的日期',
        'dateTime' => ':attribute不是一个正确的日期时间',

        'in' => ':attribute必须在取值范围 :rule 内',            //in:1,2,3
        'notIn' => ':attribute不能在 :rule 范围内',            //notIn:1,2,3
        'between' => ':attribute取值必须在 :rule 范围内',      //between:1,10
        'notBetween' => ':attribute取值不能在 :rule 范围内',   //notBetween:1,10
        'length' => ':attribute的长度只能为 :rule',           //length:4,25 或者 length:4
        'after' => ':attribute不能早于 :rule',               //after:2020-03-16 00:00:00
        'before' => ':attribute不能晚于 :rule',              //before:2020-03-16 00:00:00
        'confirm' => ':attribute两次验证不一致',              //confirm:password 或者 confirm (自动关联 field 和 field_confirm)
        'submitToken' => '请求令牌已失效，请返回刷新重试。',     //submitToken:name
        'checkToken' => '校验令牌已失效，请返回刷新重试。',      //checkToken:有效时间秒数
        'egt' => ':attribute必须大于等于 :rule',              //>=:100
        'gt' => ':attribute必须大于 :rule',                  //>:100
        'elt' => ':attribute必须小于等于 :rule',              //<=:100
        'lt' => ':attribute必须小于 :rule',                  //<:100
        'eq' => ':attribute必须等于 :rule',                  //=:100
        'ne' => ':attribute必须不等于 :rule',                //!=:100
        'regex' => ':attribute格式不正确',                   //regex:\d{6} 或者 regex:name (先 \Validate::regex('name', '\d{6}') 添加规则)
    ];
    /**
     * 闭包判断或者类型闭包判断
     * 传入参数：当前值, 当前规则, 全部值, 当前字段名
     * 返回：true表示通过，返回字符串表示错误信息
     * 'name' => function($value, $rule, $data, $field) { return 'lfly' == strtolower($value) ? true : false; }
     */

    /**
     * 自定义方法判断
     * 传入参数：当前值, 当前规则, 全部值, 当前字段名
     * 提前定义错误提示可链式操作: \Validate::setTypeMsg('checkName', '错误信息')
     * $rule = ['name' => 'checkName:rule']
     * public function checkName($value, $rule, $data = [], $field = '')
     */

    /**
     * 扩展验证
     * @var Closure[]
     */
    protected static $maker = [];

    /**
     * 构造方法
     */
    public function __construct()
    {
        if (!empty(static::$maker)) {
            foreach (static::$maker as $maker) {
                call_user_func($maker, $this);
            }
        }
    }

    /**
     * 添加扩展验证 静态调用
     * @param Closure $maker 格式如 function(验证对象实例) {}
     * @return void
     */
    public static function maker(Closure $maker)
    {
        static::$maker[] = $maker;
    }

    /**
     * 载入验证模型
     * @param string $class 验证类
     * @return object
     */
    public static function load($class)
    {
        $class = Container::getInstance()->make(App::class)->parseClass($class, 'validate');
        return new $class();
    }

    /**
     * 添加字段验证规则
     * @param string|array $name 字段名称或者规则数组
     * @param mixed        $rule 验证规则
     * @return $this
     */
    public function rule($name, $rule = '')
    {
        if (is_array($name)) {
            $this->rule = $name + $this->rule;
        } else {
            $this->rule[$name] = $rule;
        }
        return $this;
    }

    /**
     * 设置验证场景
     * @param string $name 场景名
     * @return $this
     */
    public function scene(string $name)
    {
        $this->currentScene = $name;
        return $this;
    }

    /**
     * 设置批量验证
     * @param bool $batch 是否批量验证
     * @return $this
     */
    public function batch(bool $batch = true)
    {
        $this->batch = $batch;
        return $this;
    }

    /**
     * 数据验证
     * @param array $data  数据
     * @param array $rules 验证规则
     * @return $this
     */
    public function check(array $data, array $rules = [])
    {
        $this->error = $this->originalData = $this->formattedData = [];
        if (empty($rules)) {
            $rules = $this->rule;
        }

        $rulesArray = $this->parseRule($rules, (!empty($this->currentScene) && isset($this->scene[$this->currentScene]) ? $this->scene[$this->currentScene] : []));
        foreach ($rulesArray as $rule) {
            $value = isset($data[$rule['param']]) ? $data[$rule['param']] : null;
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $result = $this->checkItem($rule, $val, $data);
                    $status = $this->saveResult($rule, $result, $key);
                    if (!$status) {
                        if (!$this->batch) {
                            break 2;
                        } else {
                            break;
                        }
                    }
                }
            } else {
                $result = $this->checkItem($rule, $value, $data);
                $status = $this->saveResult($rule, $result);
                if (!$status && !$this->batch) {
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Request数据验证
     * @param array $rules 验证规则
     * @return $this
     */
    public function checkRequest(array $rules = [])
    {
        return $this->check(Container::getInstance()->request->param(false), $rules);
    }

    /**
     * 获取检测结果
     * @return bool
     */
    public function getResult()
    {
        return !empty($this->error) ? false : true;
    }

    /**
     * 获取错误信息
     * @return array|string
     */
    public function getError()
    {
        if (!empty($this->error)) {
            return $this->batch ? $this->error : reset($this->error);
        }
        return $this->batch ? [] : '';
    }

    /**
     * 获取原始检测数据
     * @return array
     */
    public function getOriginalData()
    {
        return $this->originalData;
    }

    public function getOldData()
    {
        return $this->getOriginalData();
    }

    /**
     * 获取格式化后数据
     * @return array
     */
    public function getFormattedData()
    {
        return $this->formattedData;
    }

    public function getNewData()
    {
        return $this->getFormattedData();
    }

    /**
     * 扩展验证规则类型
     * @param string   $type           验证规则类型
     * @param mixed    $callback       验证的callback方法或正则字符
     * @param string   $message        验证失败提示信息
     * @param callable $formatCallback 验证后格式化callback方法
     * @return $this
     */
    public function extend(string $type, $callback = null, string $message = null, callable $formatCallback = null)
    {
        $this->type[$type] = [$callback, $formatCallback];
        if ($message) {
            $this->setTypeMsg($type, $message);
        }
        return $this;
    }

    /**
     * 添加正则规则
     * @param string $name 名称
     * @param string $rule 规则
     * @return $this
     */
    public function regex($name, $rule)
    {
        $this->regex[$name] = $rule;
        return $this;
    }

    /**
     * 设置特别的提示信息
     * @param array $message 错误信息
     * @return $this
     */
    public function message(array $message)
    {
        $this->message = array_merge($this->message, $message);
        return $this;
    }

    /**
     * 设置验证规则的默认提示信息
     * @param string|array $type 验证规则类型名称或者数组
     * @param string       $msg  验证提示信息
     * @return $this
     */
    public function setTypeMsg($type, $msg = '')
    {
        if (is_array($type)) {
            $this->typeMsg = array_merge($this->typeMsg, $type);
        } else {
            $this->typeMsg[$type] = $msg;
        }
        return $this;
    }

    //---以下为判断用---------------------------------------------

    /**
     * 判断类型 email
     * @param string $value 字段值
     * @return bool
     */
    public function isEmail($value)
    {
        return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * 判断类型 url
     * @param string $value 字段值
     * @return bool
     */
    public function isUrl($value)
    {
        return false !== filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * 判断类型 date
     * @param string $value 字段值
     * @param string $sep   分隔符
     * @return bool
     */
    public function isDate($value, $sep = '-')
    {
        if (!empty($value)) {
            $dateArray = explode($sep, $value);
            if (count($dateArray) == 3) {
                return checkdate(intval($dateArray[1]), intval($dateArray[2]), intval($dateArray[0]));
            }
        }
        return false;
    }

    /**
     * 判断类型 time
     * @param string $value 字段值
     * @param string $sep   分隔符
     * @return bool
     */
    public function isTime($value, $sep = ':')
    {
        return preg_match("/^([0-1]?[0-9]|2[0-3]){$sep}([0-5]?[0-9]){$sep}([0-5]?[0-9])$/", $value);
    }

    /**
     * 判断类型 dateTime
     * @param string $value 字段值
     * @return bool
     */
    public function isDateTime($value)
    {
        if (preg_match("/^(\d{4}-\d{1,2}-\d{1,2}) (\d{1,2}:\d{1,2}:\d{1,2})$/", $value, $getStr)) {
            if ($this->isDate($getStr[1]) && $this->isTime($getStr[2])) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断in
     * @param mixed        $value 字段值
     * @param array|string $rule  验证规则
     * @return bool|string
     */
    public function builtIn($value, $rule)
    {
        if (!is_array($rule)) {
            $rule = explode(',', $rule);
        }
        if (is_scalar($value) && in_array($value, $rule)) {
            return true;
        }
        return '( ' . implode(', ', $rule) . ' )';
    }

    /**
     * 判断notIn
     * @param mixed        $value 字段值
     * @param array|string $rule  验证规则
     * @return bool|string
     */
    public function builtNotIn($value, $rule)
    {
        if (!is_array($rule)) {
            $rule = explode(',', $rule);
        }
        if (is_scalar($value) && !in_array($value, $rule)) {
            return true;
        }
        return '( ' . implode(', ', $rule) . ' )';
    }

    /**
     * 判断between
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public function builtBetween($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        [$min, $max] = $rule;
        if ($value >= $min && $value <= $max) {
            return true;
        }
        return $min . '-' . $max;
    }

    /**
     * 判断notBetween
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public function builtNotBetween($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        [$min, $max] = $rule;
        if ($value < $min || $value > $max) {
            return true;
        }
        return $min . '-' . $max;
    }

    /**
     * 判断length
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public function builtLength($value, $rule)
    {
        $length = mb_strlen((string)$value);
        if (is_string($rule) && strpos($rule, ',')) {
            $rule = explode(',', $rule);
        }
        if (is_array($rule)) {
            [$min, $max] = $rule;
            if ($length >= $min && $length <= $max) {
                return true;
            }
            return $min . '-' . $max;
        } else {
            if ($length == $rule) {
                return true;
            }
            return $rule;
        }
    }

    /**
     * 判断after
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public function builtAfter($value, $rule)
    {
        if (strtotime($value) >= strtotime($rule)) {
            return true;
        }
        return $rule;
    }

    /**
     * 判断before
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool|string
     */
    public function builtBefore($value, $rule)
    {
        if (strtotime($value) <= strtotime($rule)) {
            return true;
        }
        return $rule;
    }

    /**
     * 判断confirm
     * @param mixed  $value 字段值
     * @param mixed  $rule  验证规则
     * @param array  $data  数据
     * @param string $field 字段名
     * @return bool|string
     */
    public function builtConfirm($value, $rule, array $data = [], string $field = '')
    {
        if ('' == $rule) {
            if (strpos($field, '_confirm')) {
                $rule = strstr($field, '_confirm', true);
            } else {
                $rule = $field . '_confirm';
            }
        }
        $rule = $this->getDataValue($data, $rule);
        if ($value == $rule) {
            return true;
        }
        return '';
    }

    /**
     * 判断提交令牌
     * @param mixed  $value 字段值
     * @param mixed  $rule  令牌名称
     * @return bool
     */
    public function builtSubmitToken($value, $rule = null)
    {
        return Container::getInstance()->request->checkToken($value, false, $rule);
    }

    /**
     * 判断提交校验参数
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @return bool
     */
    public function builtCheckToken($value, $rule = '')
    {
        $rule = intval($rule);
        if ($rule <= 0) {
            $rule = 7200;
        }
        $tokenData = intval(Crypto::decryptAES($value, md5(Container::getInstance()->config->get('web_secret_key') . Container::getInstance()->session->getid())));
        if ($tokenData > time() - $rule) {
            return true;
        }
        return false;
    }

    /**
     * 判断egt >=
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public function builtEgt($value, $rule, array $data = [])
    {
        $rule = $this->getDataValue($data, $rule);
        if ($value >= $rule) {
            return true;
        }
        return $rule;
    }

    /**
     * 判断gt >
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public function builtGt($value, $rule, array $data = [])
    {
        $rule = $this->getDataValue($data, $rule);
        if ($value > $rule) {
            return true;
        }
        return $rule;
    }

    /**
     * 判断elt <=
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public function builtElt($value, $rule, array $data = [])
    {
        $rule = $this->getDataValue($data, $rule);
        if ($value <= $rule) {
            return true;
        }
        return $rule;
    }

    /**
     * 判断lt <
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public function builtLt($value, $rule, array $data = [])
    {
        $rule = $this->getDataValue($data, $rule);
        if ($value < $rule) {
            return true;
        }
        return $rule;
    }

    /**
     * 判断eq ==
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public function builtEq($value, $rule, array $data = [])
    {
        $rule = $this->getDataValue($data, $rule);
        if ($value == $rule) {
            return true;
        }
        return $rule;
    }

    /**
     * 判断ne !=
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     * @param array $data  数据
     * @return bool|string
     */
    public function builtNe($value, $rule, array $data = [])
    {
        $rule = $this->getDataValue($data, $rule);
        if ($value != $rule) {
            return true;
        }
        return $rule;
    }

    /**
     * 判断regex
     * @param mixed $value 字段值
     * @param string $rule 验证规则
     * @return bool|string
     */
    public function builtRegex($value, $rule)
    {
        if (isset($this->regex[$rule])) {
            $rule = $this->regex[$rule];
        }
        if ($rule[0] != '/') {
            $rule = '/^' . $rule . '$/';
        }
        if (is_scalar($value) && preg_match($rule, $value)) {
            return true;
        }
        return '';
    }

    //---以下获取或转换用--------------------------------------------------

    /**
     * 生成校验字符
     * @param string $value 操作
     * @return string
     */
    public function convCheckToken()
    {
        return Crypto::encryptAES(microtime(true), md5(Container::getInstance()->config->get('web_secret_key') . Container::getInstance()->session->getid()));
    }

    /**
     * 转换 bool
     * @param mixed $value 字段值
     * @return int
     */
    public function convBool($value)
    {
        return (intval(trim($value)) > 0) ? 1 : 0;
    }

    /**
     * 转换 html
     * @param mixed $value   字段值
     * @param mixed $isSmart 是否智能转换
     * @return int
     */
    public function convHtml($value, $isSmart = true)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->convHtml($val, $isSmart);
            }
        } elseif ($isSmart) {
            $value = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', htmlspecialchars(trim($value), ENT_QUOTES));
        } else {
            $value = htmlspecialchars(trim($value), ENT_QUOTES);
        }
        return $value;
    }

    /**
     * 数字时间转换为自定义格式
     * @param int    $timestamp 时间戳
     * @param string $mode      格式化的模式
     * @return string
     */
    public function convTime($timestamp, $mode = 'FS')
    {
        if ($timestamp <= 0) {
            return '';
        }
        switch (strtoupper($mode)) {
            case 'FT':
                $mode = ($this->convTime($timestamp, 'FD') == $this->convTime(time(), 'FD')) ? 'H:i:s' : 'Y-m-d';
                break;
            case 'FS':
                $mode = 'Y-m-d H:i:s';
                break;
            case 'FI':
                $mode = 'Y-m-d H:i';
                break;
            case 'FD':
                $mode = 'Y-m-d';
                break;
            default:
                break;
        }
        return date($mode, $timestamp);
    }

    /**
     * 格式化秒数
     * @param int $intsec 秒数
     * @return string
     */
    public function convSecond($intsec)
    {
        $return = '';
        if ($intsec >= 86400) {
            $return .= floor($intsec / 86400) . '天';
            $intsec = $intsec % 86400;
        }
        if ($intsec >= 3600) {
            $return .= floor($intsec / 3600) . '小时';
            $intsec = $intsec % 3600;
        }
        if ($intsec >= 60) {
            $return .= floor($intsec / 60) . '分';
            $intsec = $intsec % 60;
        }
        if ($intsec > 0 || $return == '') {
            $return .= $intsec . '秒';
        }
        return $return;
    }

    /**
     * 格式化文件大小
     * @param int $filesize 文件大小Byte
     * @return string
     */
    public function convFileSize($filesize)
    {
        if ($filesize >= 1073741824) {
            $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
        } elseif ($filesize >= 1048576) {
            $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
        } elseif ($filesize >= 1024) {
            $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
        } else {
            $filesize = $filesize . ' Bytes';
        }
        return $filesize;
    }

    /**
     * 驼峰转下划线
     * @param  string $value     值
     * @param  string $delimiter 分隔符
     * @return string
     */
    public function convToSnake($value, $delimiter = '_')
    {
        if (!ctype_lower($value)) {
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/', '$1' . $delimiter, $value));
        }
        return $value;
    }

    /**
     * 下划线转驼峰
     * @param string $value 值
     * @param bool   $lower 首字母是否小写
     * @return string
     */
    public function convToCamel($value, $lower = false)
    {
        $value = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
        if ($lower) {
            $value = lcfirst($value);
        }
        return $value;
    }

    //---以下为内部解析用---------------------------------------------

    /**
     * 解析rule
     */
    protected function parseRule(array $rules, array $sceneRules = [])
    {
        $rulesArray = [];
        foreach ($rules as $key => $value) {
            $rule = [];
            if (strpos($key, '|')) {
                [$key, $rule['title']] = explode('|', $key, 2);
            }
            if (strpos($key, ',')) {
                [$key, $rule['formattedParam']] = explode(',', $key, 2);
                if (empty($rule['formattedParam'])) {
                    $rule['formattedParam'] = $key;
                }
            }
            $rule['param'] = $key;
            if (empty($rule['title'])) {
                $rule['title'] = $key;
            }
            if (!empty($sceneRules) && !isset($sceneRules[$key]) && !in_array($key, $sceneRules)) {
                continue;
            }
            $rule['setting'] = [];
            $value = isset($sceneRules[$key]) ? $sceneRules[$key] : $value;
            if ($value instanceof Closure) {
                $rule['setting']['_callback'] = $value;
            } else {
                $value = explode('|', $value);
                foreach ($value as $setting) {
                    if (strpos($setting, ':')) {
                        [$setType, $setVal] = explode(':', $setting, 2);
                    } else {
                        $setType = $setting;
                        $setVal = '';
                    }
                    if (isset($this->alias[$setType])) {
                        $setType = $this->alias[$setType];
                    }
                    if (in_array($setType, array('require', 'default'))) {
                        $rule[$setType] = $setVal;
                    } else {
                        $rule['setting'][$setType] = $setVal;
                    }
                }
            }
            $rulesArray[] = $rule;
        }
        return $rulesArray;
    }

    /**
     * 判断rule
     */
    protected function checkItem(array $rule, $value = null, $data = [])
    {
        $return = ['status' => true, 'old' => $value, 'new' => $value, 'err' => ''];

        if (empty($value) && $value != '0') {
            if (isset($rule['require'])) {
                $return['status'] = false;
                $return['new'] = null;
                $return['err'] = str_replace(':attribute', $rule['title'], $this->getRuleMsg($rule['param'], 'require'));
            } elseif (isset($rule['default'])) {
                $return['old'] = $return['new'] = $rule['default'];
            }
            return $return;
        }

        foreach ($rule['setting'] as $type => $val) {
            if ($val instanceof Closure) {
                $result = call_user_func_array($val, [$value, '', $data, $rule['param']]);
                if (is_string($result) && $result != '') {
                    $this->message([$rule['param'] => $result]);
                }
            } elseif (method_exists($this, 'built' . ucfirst($type))) {
                $result = call_user_func_array([$this, 'built' . ucfirst($type)], [$value, $val, $data, $rule['param']]);
            } elseif (isset($this->type[$type])) {
                $curTypeRule = $this->type[$type];
                $result = true;
                if (!empty($curTypeRule[0])) {
                    if ($curTypeRule[0] instanceof Closure) {
                        $result = call_user_func_array($curTypeRule[0], [$value, $val, $data, $rule['param']]);
                    } elseif ($curTypeRule[0][0] == '/') {
                        $result = call_user_func_array([$this, 'builtRegex'], [$value, $curTypeRule[0]]);
                    } elseif ($curTypeRule[0][0] == '\\') {
                        $result = $curTypeRule[0]($value);
                    } elseif (method_exists($this, $curTypeRule[0])) {
                        $result = call_user_func_array([$this, $curTypeRule[0]], [$value]);
                    }
                }
                if (is_bool($result) && $result && !empty($curTypeRule[1])) {
                    if ($curTypeRule[1] instanceof Closure) {
                        $return['new'] = call_user_func_array($curTypeRule[1], [$value]);
                    } elseif ($curTypeRule[1][0] == '\\') {
                        $return['new'] = $curTypeRule[1]($value);
                    } elseif (method_exists($this, $curTypeRule[1])) {
                        $return['new'] = call_user_func_array([$this, $curTypeRule[1]], [$value]);
                    }
                }
            } elseif (method_exists($this, $type)) {
                $result = call_user_func_array([$this, $type], [$value, $val, $data, $rule['param']]);
            } else {
                $result = true;
            }
            if (!is_bool($result) || !$result) {
                $return['status'] = false;
                $return['new'] = null;
                $return['err'] = str_replace([':attribute', ':rule'], [$rule['title'], strval($result)], $this->getRuleMsg($rule['param'], $type));
                return $return;
            }
        }

        return $return;
    }

    /**
     * 获取错误信息
     */
    protected function getRuleMsg($param, $type)
    {
        if (isset($this->message[$param . '.' . $type])) {
            return $this->message[$param . '.' . $type];
        } elseif (isset($this->message[$param])) {
            return $this->message[$param];
        } elseif (isset($this->typeMsg[$type])) {
            return $this->typeMsg[$type];
        } else {
            return $this->typeMsg['default'];
        }
    }

    /**
     * 获取数据值
     */
    protected function getDataValue(array $data, $key, $default = null)
    {
        if (is_numeric($key)) {
            $value = $key;
        } else {
            $value = $data[$key] ?? $default;
        }
        return $value;
    }

    /**
     * 保存检测结果
     */
    protected function saveResult($rule, $result, $key = null)
    {
        if (!$result['status'] && !isset($this->error[$rule['param']])) {
            $this->error[$rule['param']] = $result['err'];
        }
        if (!is_null($result['old'])) {
            if (!is_null($key)) {
                $this->originalData[$rule['param']][$key] = $result['old'];
            } else {
                $this->originalData[$rule['param']] = $result['old'];
            }
        }
        if (isset($rule['formattedParam']) && !is_null($result['new'])) {
            if (!is_null($key)) {
                $this->formattedData[$rule['formattedParam']][$key] = $result['new'];
            } else {
                $this->formattedData[$rule['formattedParam']] = $result['new'];
            }
        }
        return $result['status'];
    }
}
