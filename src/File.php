<?php

/**
 * 文件上传类
 */

namespace lfly;

use InvalidArgumentException;

class File
{
    /**
     * 错误信息
     */
    protected $error;

    /**
     * 总上传文件数
     */
    protected $fileNum = 0;

    /**
     * 上传保存后文件列表
     */
    protected $fileList = [];

    /**
     * 上传数据
     */
    protected $upData = [];

    /**
     * 默认错误信息
     */
    protected $errorMsg = [
        'file' => '文件',
        'require' => ':attribute必须上传',
        'error' => ':attribute上传有误',
        'uploadError' => ':attribute上传的文件 :file 发生错误',
        'invalid' => ':attribute上传的文件 :file 无效',
        'extError' => ':attribute上传的文件 :file 为不允许的格式',
        'sizeError' => ':attribute上传的文件 :file 大小超出限制',
    ];

    /**
     * 默认类型后缀设定
     */
    protected $type = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp'],
        'music' => ['mp3', 'wma', 'ape', 'wav', 'flac'],
        'video' => ['mp4', 'avi', 'wmv', 'rm', 'rmvb', 'mkv', 'mov'],
    ];

    /**
     * 上传文件配置
     */
    protected $config = ['require' => false, 'ext' => ['jpg', 'jpeg', 'png', 'gif'], 'size' => 0];

    /**
     * 磁盘位置配置
     */
    protected $diskConfig;

    /**
     * 临时引擎
     */
    protected $tempEngine;

    /**
     * @var App
     */
    protected $app;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->diskConfig = $this->app->config->get('file');
    }

    /**
     * 设置上传文件
     * @param string $inputName 类名
     * @param string $config    上传配置
     * @return $this
     */
    public function init($inputName, $config = null)
    {
        if (strpos($inputName, '|')) {
            [$inputName, $this->config['title']] = explode('|', $inputName, 2);
        } else {
            $this->config['title'] = $this->errorMsg['file'];
        }
        $this->setInput($inputName);
        if (!empty($config)) {
            $this->parseConfig($config);
        }
        $this->CheckFile();
        return $this;
    }

    /**
     * 获取引擎
     * @param string $engine 文件类型
     * @return FileHandlerInterface
     * 
     * @throws InvalidArgumentException
     */
    public function engine($engine)
    {
        $curEngineConfig = $this->diskConfig['engine'][$engine];
        if (empty($curEngineConfig)) {
            throw new InvalidArgumentException('file engine error: ' . $engine);
        }
        if ($curEngineConfig['type'] == 'local') {
            $fileObj = $this;
            $this->tempEngine = $engine;
        } else {
            if (empty($curEngineConfig['class'])) {
                throw new InvalidArgumentException('file engine error: ' . $engine);
            }
            $fileObj = $this->app->invokeClass($curEngineConfig['class']);
            $fileObj->init($curEngineConfig);
        }
        return $fileObj;
    }

    /**
     * 获取检测结果
     * @return bool
     */
    public function getResult()
    {
        return !is_null($this->error) ? false : true;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error ?? '';
    }

    /**
     * 获取上传文件个数
     * @return int
     */
    public function getNum()
    {
        return $this->fileNum;
    }

    /**
     * 获取上传文件数组
     * @return array
     */
    public function getFile()
    {
        return $this->fileList;
    }

    /**
     * 自动保存文件
     * @param string $filePath  额外文件路径
     * @param bool   $isOldName 是否加入旧文件名
     * @return array
     */
    public function save($filePath = '', $isOldName = false)
    {
        return $this->saveAs($this->diskConfig['default'], $filePath, $isOldName);
    }

    /**
     * 设定引擎保存文件
     * @param string $engine    设置保存引擎
     * @param string $filePath  额外文件路径
     * @param bool   $isOldName 是否加入旧文件名
     * @return array
     */
    public function saveAs($engine, $filePath = '', $isOldName = false)
    {
        $fileObj = $this->engine($engine);
        $returnArray = [];
        foreach ($this->upData as $key => $attach) {
            $filePreFix = '';
            if ($isOldName) {
                $saveName = preg_replace('/[^0-9a-zA-Z\-\._]+/', '', $attach['name']);
                $filePreFix = substr(str_replace(strrchr($saveName, '.'), '', $saveName), 0, 32) . '_';
            }
            $saveName = $filePath . $this->newFolder() . '/' . $filePreFix . $this->newFilename($attach['name']);

            $tempFileinfo = ['type' => $this->getExt($attach['name']), 'name' => basename($saveName), 'isimage' => 0];
            if (in_array($tempFileinfo['type'], $this->type['image']) && function_exists('getimagesize')) {
                $imageFileinfo = @getimagesize($attach['tmp_name']);
                if ($imageFileinfo !== false) {
                    $tempFileinfo['width'] = $imageFileinfo[0];
                    $tempFileinfo['height'] = $imageFileinfo[1];
                    if (in_array($imageFileinfo[2], array('1', '2', '3', '6'))) {
                        $tempFileinfo['isimage'] = 1;
                    }
                }
            }

            $tempFileinfo['file'] = $fileObj->putFile($attach['tmp_name'], $saveName);
            if (!empty($tempFileinfo['file'])) {
                if (is_array($tempFileinfo['file'])) {
                    $tempFileinfo['path'] = $tempFileinfo['file'][1];
                    $tempFileinfo['file'] = $tempFileinfo['file'][0];
                }
                @unlink($attach['tmp_name']);
                $tempFileinfo['size'] = $attach['size'];
                $tempFileinfo['oldname'] = $attach['name'];
                $returnArray[] = $tempFileinfo;
            }
        }
        $this->fileList = $returnarray;
        return $this->fileList;
    }

    /**
     * 本地保存文件
     * @param  array $fromFile 原始文件
     * @param  array $toFile   新文件路径
     * @return string
     */
    public function putFile($fromFile, $toFile)
    {
        $curEngineConfig = $this->diskConfig['engine'][$this->tempEngine];
        $saveFile = $curEngineConfig['root'] . $toFile;
        $savePath = dirname($saveFile);
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
            @touch($savePath . DS . 'index.html');
        }

        $fileSaved = false;
        if (@copy($fromFile, $saveFile) || (function_exists('move_uploaded_file') && @move_uploaded_file($fromFile, $saveFile))) {
            $fileSaved = true;
        }

        if (!$fileSaved && @is_readable($fromFile)) {
            $attachedFile = file_get_contents($fromFile);
            @$fp = fopen($saveFile, 'wb');
            @flock($fp, LOCK_EX);
            if (@fwrite($fp, $attachedFile)) {
                $fileSaved = true;
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        return $fileSaved ? [$curEngineConfig['url'] . $toFile, $saveFile] : '';
    }

    /**
     * 删除上传的文件
     * @param  string $file 完整文件
     * @return bool
     */
    public function delFile($file = null)
    {
        if (empty($file)) {
            if (!empty($this->fileList)) {
                foreach ($this->fileList as $upfile) {
                    if (!empty($upfile['path'])) {
                        @unlink($upfile['path']);
                    }
                }
            }
        } else {
            @unlink($file);
        }
        return true;
    }

    /**
     * 获取文件后缀
     */
    public function getExt($file)
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * 生成新文件名
     */
    public function newFilename($file)
    {
        $tempext = $this->getExt($file);
        list($usec, $sec) = explode(' ', microtime());
        $length = 6;
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return date('ymdHis') . $usec . $hash . '.' . $tempext;
    }

    /**
     * 生成新文件夹名
     */
    public function newFolder($strMode = 'Ym')
    {
        return date($strMode);
    }

    /**
     * 设置上传控件获取数据
     */
    protected function setInput($inputName)
    {
        $curData = isset($_FILES[$inputName]) ? $_FILES[$inputName] : '';
        if (is_array($curData)) {
            foreach ($curData as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $id => $val) {
                        $this->upData[$id][$key] = $val;
                    }
                } else {
                    $this->upData[0][$key] = $value;
                }
            }
        }
        foreach ($this->upData as $key => $value) {
            if (empty($value['name']) && empty($value['tmp_name']) && $value['size'] == 0) {
                unset($this->upData[$key]);
            }
        }
    }

    /**
     * 解析上传配置参数
     */
    protected function parseConfig($config)
    {
        $value = explode('|', $config);
        foreach ($value as $setting) {
            if (strpos($setting, ':')) {
                [$setType, $setVal] = explode(':', $setting, 2);
            } else {
                $setType = $setting;
                $setVal = true;
            }
            if ($setType == 'ext') {
                $setVal = explode(',', $setVal);
            } elseif ($setType == 'type' && isset($this->type[$setVal])) {
                $setType = 'ext';
                $setVal = $this->type[$setVal];
            }
            $this->config[$setType] = $setVal;
        }
    }

    /**
     * 检测上传的文件
     */
    protected function CheckFile()
    {
        if (!is_array($this->upData) || empty($this->upData)) {
            if ($this->config['require']) {
                $this->setError('require');
            }
            return false;
        }
        foreach ($this->upData as $key => $attach) {
            if (!$this->IsUploadedFile($attach['tmp_name']) || !($attach['tmp_name'] != 'none' && $attach['tmp_name'] && $attach['name'])) {
                $this->setError('error');
                return false;
            }

            $showName = htmlspecialchars($attach['name']);

            if (isset($attach['error']) && $attach['error'] > 0) {
                $this->setError('uploadError', $showName);
                return false;
            }

            $attach['ext'] = $this->getExt($attach['name']);
            if (empty($attach['ext']) || empty($this->config['ext']) || !is_array($this->config['ext']) || !in_array($attach['ext'], $this->config['ext'])) {
                $this->setError('extError', $showName);
                return false;
            }

            if (empty($attach['size']) || ($this->config['size'] > 0 && $attach['size'] > $this->config['size'])) {
                $this->setError('sizeError', $showName);
                return false;
            }

            if (in_array($attach['ext'], $this->type['image']) && function_exists('getimagesize') && !@getimagesize($attach['tmp_name'])) {
                $this->setError('invalid', $showName);
                return false;
            }
        }
        $this->fileNum = count($this->upData);
        return true;
    }

    /**
     * 设置错误信息
     */
    protected function setError($errorType, $file = '')
    {
        $this->error = str_replace([':attribute', ':file'], [$this->config['title'], $file], $this->errorMsg[$errorType]);
    }

    /**
     * 检测是否上传文件
     */
    protected function isUploadedFile($file)
    {
        return function_exists('is_uploaded_file') && (is_uploaded_file($file));
    }
}
