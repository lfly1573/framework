<?php

/**
 * 注释解析类
 */

namespace lfly\util;

use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class DocParser
{
    /**
     * 解析某个类的注释
     * @param string $class 类名
     * @param int    $type  类型，可组合：1-公开 2-保护 4-内部
     * @return array
     */
    public function parseClass($class, int $type = 1)
    {
        try {
            $reflect = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new LogicException('class not exists: ' . $class);
        }
        $return = [];
        $filter = 0;
        if ($type & 1) {
            $filter = $filter | ReflectionMethod::IS_PUBLIC;
        }
        if ($type & 2) {
            $filter = $filter | ReflectionMethod::IS_PROTECTED;
        }
        if ($type & 4) {
            $filter = $filter | ReflectionMethod::IS_PRIVATE;
        }
        $methods = $reflect->getMethods($filter);
        foreach ($methods as $method) {
            $comment = $this->parseLines($method->getDocComment());
            $return[] = ['class' => $method->class, 'method' => $method->name] + $comment;
        }
        return $return;
    }

    /**
     * 解析某个方法的注释
     * @param string $doc 注释内容
     * @return array
     */
    public function parseLines($doc = '')
    {
        if ($doc == '' || !preg_match('#^/\*\*(.*)\*/#s', trim($doc), $comment) || !preg_match_all('#^\s*\*\s*(.+)#m', preg_replace('#\n\s*\*\s*\n#', PHP_EOL, trim($comment[1])), $lines)) {
            return [];
        }
        $return = ['title' => '', 'description' => [], 'param' => [], 'return' => []];
        if (isset($lines[1][0]) && $lines[1][0][0] != '@') {
            $return['title'] = trim($lines[1][0]);
            array_shift($lines[1]);
        }
        $description = [];
        foreach ($lines[1] as $line) {
            $line = trim($line);
            if ($line != '') {
                $parsedLine = $this->parseLine($line);
                if (empty($parsedLine)) {
                    $description[] = $line;
                } else {
                    $return[$parsedLine[0]][] = $parsedLine[1];
                }
            }
        }
        if (!empty($return['description'])) {
            $return['description'] = implode(PHP_EOL, $return['description']);
        } elseif (!empty($description)) {
            $return['description'] = implode(PHP_EOL, $description);
        } else {
            $return['description'] = '';
        }
        $return['return'] = implode('|', $return['return']);
        return $return;
    }

    /**
     * 解析单行注释
     * @param string $line 单行注释内容
     * @return array
     */
    private function parseLine($line)
    {
        $return = [];
        if (preg_match('/^@([a-z0-9_]+)\s*(.*)$/i', trim($line), $data)) {
            $return[0] = $data[1];
            if ($return[0] == 'param') {
                $return[1] = explode(' ', preg_replace('/\s+/', ' ', $data[2]), 3);
            } else {
                $return[1] = $data[2];
            }
        }
        return $return;
    }
}
