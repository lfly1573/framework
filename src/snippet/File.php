<?php

/**
 * 文件夹和文件操作的方法
 */

namespace lfly\snippet;

trait File
{
    /**
     * 清空文件夹
     * @param string $dir  文件夹路径
     * @param int    $mode 0-清空并删除当前文件夹 1-清空但不删除当前文件夹 2-清空但不删除当前及下属所有文件夹
     * @return void
     */
    protected function rmFolder($dir, $mode = 0)
    {
        $dir = rtrim($dir, '/');
        if (is_dir($dir)) {
            $current_dir = @opendir($dir);
            while ($entryname = @readdir($current_dir)) {
                if ($entryname != '.' && $entryname != '..') {
                    $fullpath = $dir . '/' . $entryname;
                    if (!is_dir($fullpath)) {
                        @unlink($fullpath);
                    } else {
                        $undermode = ($mode == 2) ? 2 : 0;
                        $this->rmFolder($fullpath, $undermode);
                    }
                }
            }
            @closedir($current_dir);
            if ($mode == 0) {
                @rmdir($dir);
            }
        }
    }

    //获取文件后缀名
    protected function fileExt($filename, $clearsuffix = 1)
    {
        if ($clearsuffix > 0) {
            $filename = preg_replace("/^(.+)\.file$/i", "\\1", $filename);	//剔掉危险文件新加的后缀
        }
        $fileext = strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
        if (!preg_match('/^[a-z0-9]{1,10}$/', $fileext)) {
            $fileext = '';
        }
        return $fileext;
    }

    //格式化文件大小
    protected function fileSize($filesize)
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
}
