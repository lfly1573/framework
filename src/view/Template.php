<?php

/**
 * 默认视图解释类
 */

namespace lfly\view;

use RuntimeException;
use lfly\contract\TemplateHandlerInterface;

class Template implements TemplateHandlerInterface
{
    /**
     * 模版参数
     * @var array
     */
    protected $config = [
        'templateFolder' => APP_PATH . 'view' . DS,
        'cacheFolder' => CACHE_PATH . 'view' . DS,
        'tplFileExt' => '.html',
        'objFileExt' => '.tpl.php',
    ];

    /**
     * 检测是否存在模板文件
     * @param  string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template)
    {
        return is_file($this->getTplFile($template));
    }

    /**
     * 渲染模板文件
     * @param  string $template 模板文件
     * @param  array  $data 模板变量
     * @return void
     */
    public function fetch(string $template, array $data = [])
    {
        $curTplObjFile = $this->checkTplObjFile($template);
        extract($data, EXTR_OVERWRITE);
        include $curTplObjFile;
    }

    /**
     * 渲染模板内容
     * @param  string $content 模板内容
     * @param  array  $data 模板变量
     * @return void
     */
    public function display(string $content, array $data = [])
    {
        extract($data, EXTR_OVERWRITE);
        eval('?>' . $this->parse($content));
    }

    /**
     * 获取模版解析后的文件
     * @param  string $template 模板文件
     * @return string
     */
    public function getFile(string $template)
    {
        return $this->checkTplObjFile($template);
    }

    /**
     * 配置模板引擎
     * @param  array $config 参数
     * @return void
     */
    public function config(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取模板引擎配置
     * @param  string $name 参数名
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : '';
    }

    //获取模版实际文件路径
    protected function getTplFile(string $template)
    {
        if (DS != '/') {
            $template = str_replace('/', DS, $template);
        }
        return $this->config['templateFolder'] . $template . $this->config['tplFileExt'];
    }

    //获取模版编译文件路径
    protected function getTplObjFile(string $template)
    {
        return $this->config['cacheFolder'] . str_replace('/', '.', $template) . $this->config['objFileExt'];
    }

    //检查编译文件
    protected function checkTplObjFile(string $template)
    {
        $curTplFile = $this->getTplFile($template);
        $curTplObjFile = $this->getTplObjFile($template);
        if (!is_file($curTplFile)) {
            throw new RuntimeException('template file not exists: ' . $template);
        }
        if (!is_file($curTplObjFile) || @filemtime($curTplFile) > @filemtime($curTplObjFile)) {
            $content = $this->parse(file_get_contents($curTplFile));
            if (!is_dir(dirname($curTplObjFile))) {
                mkdir(dirname($curTplObjFile), 0777, true);
            }
            if (!@$fp = fopen($curTplObjFile, 'wb')) {
                throw new RuntimeException('template file parse error: ' . $template);
            }
            flock($fp, LOCK_EX);
            fwrite($fp, trim($content));
            flock($fp, LOCK_UN);
            fclose($fp);
            @chmod($curTplObjFile, 0777);
        }
        return $curTplObjFile;
    }

    //编译模版
    protected function parse($template)
    {
        $nest = 10;
        $template = str_replace("{LF}", "<?php echo PHP_EOL; ?>", $template);
        $template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = preg_replace('/\s*\{\/\/.+?\}/s', '', $template);

        $template = preg_replace("/\{!!\s*(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\s*!!\}/is", "<?php echo \\1; ?>", $template);
        $template = preg_replace("/\{\s*(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+?)\s*\|\s*([a-zA-Z0-9_]+)\s*\}/s", "<?php echo htmlspecialchars(\\2(\\1)); ?>", $template);
        $template = preg_replace("/\{\s*(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\s*\}/s", "<?php echo htmlspecialchars(\\1); ?>", $template);

        $template = "<?php !defined('LFLY_VERSION') and exit(); ?>$template";
        $template = preg_replace("/\s*\{template\s+([a-z0-9_\/]+)\}[ \t]*/is", "<?php include \\View::getFile('\\1'); ?>", $template);
        $template = preg_replace("/\s*\{template\s+(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}[ \t]*/is", "<?php include \\View::getFile(\\1); ?>", $template);

        $template = preg_replace_callback("/\{!!\s*R\.([a-zA-Z0-9_]+(\(.*?\))?)\s*!!\}/", function ($matches) {
            return '<?php echo \\Request::' . $matches[1] . (empty($matches[2]) ? '()' : '') . '; ?>';
        }, $template);
        $template = preg_replace_callback("/\{R\.([a-zA-Z0-9_]+(\(.*?\))?)\}/", function ($matches) {
            return '<?php echo htmlspecialchars(\\Request::' . $matches[1] . (empty($matches[2]) ? '()' : '') . '); ?>';
        }, $template);
        $template = preg_replace_callback("/\{U(\(.+?\))\}/", function ($matches) {
            return '<?php echo \\Route::buildUrl' . $matches[1] . '; ?>';
        }, $template);
        $template = preg_replace_callback("/\{C\.([a-zA-Z0-9_\.]+)\}/", function ($matches) {
            return '<?php echo \\Config::get(\'' . $matches[1] . '\',\'\'); ?>';
        }, $template);
        $template = preg_replace_callback("/\{V\.([a-zA-Z0-9_]+(\(.*?\))?)\}/", function ($matches) {
            return '<?php echo \\Validate::' . $matches[1] . (empty($matches[2]) ? '()' : '') . '; ?>';
        }, $template);

        $template = preg_replace_callback("/[\n\r\t]*\{eval\s+(.+?)\s*\}[\n\r\t]*/is", function ($matches) {
            return $this->stripvTags('<?php ' . $matches[1] . ' ?>', '');
        }, $template);
        $template = preg_replace_callback("/[\n\r\t]*\{eval\}(.+?)\{\/eval\}[\n\r\t]*/is", function ($matches) {
            return $this->stripvTags('<?php ' . $matches[1] . ' ?>', '');
        }, $template);
        $template = preg_replace_callback("/[\n\r\t]*\{literal\}(.+?)\{\/literal\}[\n\r\t]*/is", function ($matches) {
            return $this->stripvTags($matches[1], '');
        }, $template);
        $template = preg_replace_callback("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is", function ($matches) {
            return $this->stripvTags('<?php echo ' . $matches[1] . '; ?>', '');
        }, $template);
        $template = preg_replace_callback("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is", function ($matches) {
            return $this->stripvTags($matches[1] . '<?php } elseif(' . $matches[2] . ') { ?>' . $matches[3], '');
        }, $template);
        $template = preg_replace("/([\n\r\t]*)\{else\}([\n\r\t]*)/is", "\\1<?php } else { ?>\\2", $template);

        for ($i = 0; $i < $nest; $i++) {
            $template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/is", function ($matches) {
                return $this->stripvTags('<?php if(is_array(' . $matches[1] . ')) { foreach(' . $matches[1] . ' as ' . $matches[2] . ') { ?>', $matches[3] . '<?php } } ?>');
            }, $template);
            $template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/is", function ($matches) {
                return $this->stripvTags('<?php if(is_array(' . $matches[1] . ')) { foreach(' . $matches[1] . ' as ' . $matches[2] . ' => ' . $matches[3] . ') { ?>', $matches[4] . '<?php } } ?>');
            }, $template);
            $template = preg_replace_callback("/([\n\r\t]*)\{if\s+(.+?)\}([\n\r]*)(.+?)([\n\r]*)\{\/if\}([\n\r\t]*)/is", function ($matches) {
                return $this->stripvTags($matches[1] . '<?php if(' . $matches[2] . ') { ?>' . $matches[3], $matches[4] . $matches[5] . '<?php } ?>' . $matches[6]);
            }, $template);
        }

        $template = preg_replace("/\{([A-Z][A-Z0-9_]*)\}/", "<?php echo htmlspecialchars(\\1); ?>", $template);
        $template = preg_replace("/\s+\?\>[\n\r]*\<\?php\s+/s", "\n", $template);
        $template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/", function ($matches) {
            return $this->transAmp($matches[0]);
        }, $template);
        $template = str_replace('#$#', '$', $template);
        $template = str_replace('#{#', '{', $template);
        $template = str_replace('#}#', '}', $template);

        return $template;
    }

    protected function addQuote($var)
    {
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
    }

    protected function stripvTags($expr, $statement)
    {
        $expr = str_replace("\\\"", "\"", preg_replace("/\<\?php\s+echo\s+htmlspecialchars\((\\\$.+?)\);\s*\?\>/s", "{\\1}", $expr));
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr . $statement;
    }

    protected function transAmp($str)
    {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('&amp;amp;', '&amp;', $str);
        $str = str_replace('\"', '"', $str);
        return $str;
    }
}
