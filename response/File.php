<?php

/**
 * 文件输出
 */

namespace lfly\response;

use lfly\Response;

class File extends Response
{
    protected $engine = 'file';
    protected $expire = 600;
    protected $display = false;
    protected $name;
    protected $mimeType;
    protected $isContent = false;

    protected function output($data)
    {
        if (!$this->isContent && !is_file($data)) {
            throw new \LogicException('file not exists:' . $data);
        }

        ob_end_clean();

        if (!$this->display) {
            if (!empty($this->name)) {
                $name = $this->name;
            } else {
                $name = !$this->isContent ? pathinfo($data, PATHINFO_BASENAME) : '';
            }
        }

        if ($this->isContent) {
            $mimeType = $this->mimeType;
            $size = strlen($data);
        } else {
            $mimeType = $this->getMimeType($data);
            $size = filesize($data);
        }

        $this->header['Pragma'] = 'public';
        $this->header['Content-Type'] = $mimeType ? $mimeType : 'application/octet-stream';
        $this->header['Cache-control'] = 'max-age=' . $this->expire;
        if (!$this->display) {
            $this->header['Content-Disposition'] = 'attachment; filename="' . $name . '"';
        }
        $this->header['Content-Length'] = $size;
        $this->header['Content-Transfer-Encoding'] = 'binary';
        $this->header['Expires'] = gmdate("D, d M Y H:i:s", time() + $this->expire) . ' GMT';
        $this->lastModified(gmdate('D, d M Y H:i:s', time()) . ' GMT');

        return $this->isContent ? $data : file_get_contents($data);
    }

    /**
     * 设置有效期
     * @param  int $expire 有效期
     * @return $this
     */
    public function expire($expire)
    {
        $this->expire = $expire;
        return $this;
    }

    /**
     * 设置文件直接显示而非下载
     * @return $this
     */
    public function display()
    {
        $this->display = true;
        return $this;
    }

    /**
     * 设置是否为内容
     * @param  bool $content
     * @return $this
     */
    public function isContent($content = true)
    {
        $this->isContent = $content;
        return $this;
    }

    /**
     * 设置文件类型
     * @param  string $mimeType 文件类型
     * @return $this
     */
    public function mimeType($mimeType)
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * 设置下载文件的显示名称
     * @param  string $filename 文件名
     * @param  bool   $extension 后缀自动识别
     * @return $this
     */
    public function name($filename, $extension = true)
    {
        $this->name = $filename;
        if ($extension && false === strpos($filename, '.')) {
            $this->name .= '.' . pathinfo($this->data, PATHINFO_EXTENSION);
        }
        return $this;
    }

    /**
     * 获取文件类型信息
     * @param  string $filename 文件名
     * @return string
     */
    protected function getMimeType(string $filename)
    {
        if (!empty($this->mimeType)) {
            return $this->mimeType;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        return finfo_file($finfo, $filename);
    }
}
