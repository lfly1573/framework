<?php

/**
 * mysql数据库类
 */

namespace lfly\db\connector;

use PDO;
use lfly\db\PDOConnection;

class Mysql extends PDOConnection
{
    /**
     * 取得数据表的字段信息
     * @param  mixed $tableName 表名
     * @return array
     */
    public function getFields($query, $tableName = null)
    {
        if (!empty($tableName)) {
            $query->table($tableName);
        }
        $tableName = $query->getOptions('table', '');
        if (false === strpos($tableName, '`')) {
            if (strpos($tableName, '.')) {
                $tableName = str_replace('.', '`.`', $tableName);
            }
            $tableName = '`' . $tableName . '`';
        }

        $sql = 'SHOW FULL COLUMNS FROM ' . $tableName;
        $pdo = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info = [];
        if (!empty($result)) {
            foreach ($result as $val) {
                $val = array_change_key_case($val);
                $info[$val['field']] = [
                    'field' => $val['field'],
                    'type' => $val['type'],
                    'formatType' => $this->formatFieldType($val['type']),
                    'notnull' => (bool)('' === $val['null']),
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                    'comment' => $val['comment'],
                ];
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     * @param  string $dbName
     * @return array
     */
    public function getTables($query, $dbName = '')
    {
        $sql = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES';
        $pdo = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * 生成sql语句
     * @param  Query $query 查询对象
     * @param  string $type 操作
     * @return string
     */
    public function buildSql($query, $type = 'select')
    {
        $method = 'buildSql' . ucfirst($type);
        if (method_exists($this, $method)) {
            return $this->$method($query);
        }
        return '';
    }

    protected function buildSqlSelect($query)
    {
        $sql = '';
        $table = $this->parseQueryTable($query);
        $alias = $query->getOptions('alias', '');
        $extra = $this->parseQueryExtra($query);
        $field = $this->parseQueryField($query, $alias);
        $force = $query->getOptions('force', '');
        if ($force != '') {
            $force = " FORCE INDEX ({$force})";
        }
        $join = $this->parseQueryJoin($query);
        $where = $this->parseQueryWhere($query);
        $group = $query->getOptions('group', '');
        if ($group != '') {
            $group = " GROUP BY {$group}";
        }
        $having = $query->getOptions('having', '');
        if ($having != '') {
            $having = " HAVING {$having}";
        }
        $order = $this->parseQueryOrder($query);
        $limit = $query->getOptions('limit', '');
        $fromid = 0;
        if ($limit != '') {
            if (strpos($limit, ',')) {
                [$fromid, $limitnum] = explode(',', $limit);
                $fromid = intval($fromid);
            }
            $limit = " LIMIT {$limit}";
        }
        $lock = $query->getOptions('lock', '');
        if ($lock != '') {
            $lock = " {$lock}";
        }
        $pkid = $query->getOptions('pkid', '');

        if (!empty($table)) {
            if ($fromid >= 200 && $pkid != '' && $extra == '' && $alias == '' && $join == '' && $group == '' && $having == '' && $lock == '') {
                //优化分页
                $newalias = ['T1', 'T2'];
                $topfield = $this->parseQueryField($query, $newalias[1]);
                $sql = "SELECT {$topfield} FROM (SELECT {$pkid} FROM {$table}{$force}{$where}{$order}{$limit}) {$newalias[0]}, {$table} {$newalias[1]} WHERE {$newalias[0]}.{$pkid}={$newalias[1]}.{$pkid}{$order}";
            } else {
                $sql = "SELECT{$extra} {$field} FROM {$table}{$force}{$join}{$where}{$group}{$having}{$order}{$limit}{$lock}";
            }
        }
        return $sql;
    }

    protected function buildSqlInsert($query)
    {
        $sql = '';
        $extra = $this->parseQueryExtra($query);
        $table = $this->parseQueryTable($query);
        $data = $this->parseQueryData($query, true);
        $replace = $query->getOptions('replace', false);
        if (!empty($table) && !empty($data[0])) {
            $action = $replace ? 'REPLACE' : 'INSERT';
            $update = !empty($data[1]) ? " ON DUPLICATE KEY UPDATE {$data[1]}" : '';
            $sql = "{$action}{$extra} INTO {$table} SET {$data[0]}{$update}";
        }
        return $sql;
    }

    protected function buildSqlInsertAll($query)
    {
        $sql = '';
        $extra = $this->parseQueryExtra($query);
        $table = $this->parseQueryTable($query);
        $data = $query->getOptions('bind', []);
        $replace = $query->getOptions('replace', false);
        if (!empty($table) && !empty($data[0])) {
            $field = '(' . implode(', ', array_keys($data[0])) . ')';
            $dataArray = [];
            foreach ($data as $value) {
                $dataOne = [];
                foreach ($value as $subvalue) {
                    $dataOne[] = (is_int($subvalue) || is_float($subvalue)) ? $subvalue : $this->getPDO()->quote($subvalue);
                }
                $dataArray[] = '(' . implode(', ', $dataOne) . ')';
            }
            $data = implode(', ', $dataArray);
            $action = $replace ? 'REPLACE' : 'INSERT';
            $sql = "{$action}{$extra} INTO {$table} {$field} VALUES {$data}";
        }
        return $sql;
    }

    protected function buildSqlUpdate($query)
    {
        $sql = '';
        $table = $query->getOptions('table', '');
        $data = $this->parseQueryData($query);
        $where = $this->parseQueryWhere($query);
        if (!empty($table) && !empty($data)) {
            $sql = "UPDATE {$table} SET {$data}{$where}";
        }
        return $sql;
    }

    protected function buildSqlDelete($query)
    {
        $table = $query->getOptions('table', '');
        $where = $this->parseQueryWhere($query);
        return !empty($table) ? "DELETE FROM {$table}{$where}" : '';
    }

    protected function buildSqlExists($query)
    {
        $table = $query->getOptions('table', '');
        return !empty($table) ? "SHOW TABLES LIKE '" . $table . "'" : '';
    }

    protected function parseQueryTable($query)
    {
        $return = '';
        $table = $query->getOptions('table', '');
        $alias = $query->getOptions('alias', '');
        $temptable = $query->getOptions('temptable', '');
        if (!empty($table)) {
            $return = $table;
        } elseif (!empty($temptable)) {
            $return = '(' . $temptable . ')';
        }
        if (!empty($return) && !empty($alias)) {
            $return .= ' ' . $alias;
        }
        return $return;
    }

    protected function parseQueryExtra($query)
    {
        $extra = $query->getOptions('extra', '');
        if ($extra != '') {
            $extra = " {$extra}";
        }
        return $extra;
    }

    protected function parseQueryField($query, $pre = '')
    {
        if ($pre != '') {
            $pre = $pre . '.';
        }
        $field = $query->getOptions('field', []);
        if (!empty($field)) {
            if ($pre != '') {
                foreach ($field as $key => $value) {
                    if (strpos($value, '.') === false) {
                        $field[$key] = $pre . $value;
                    }
                }
            }
            return implode(', ', $field);
        }
        return $pre . '*';
    }

    protected function parseQueryData($query, $insert = false)
    {
        $infoStr = $updateStr = '';
        $data = $query->getOptions('bind', []);
        $pkid = $query->getOptions('pkid', '');
        if (!empty($data)) {
            $bind = $info = [];
            $pkdata = '';
            foreach ($data as $key => $value) {
                if ($pkid != '' && $key == $pkid) {
                    $bind[$key] = $value;
                    $pkdata = "`{$key}`=:{$key}";
                    continue;
                }
                if (is_array($value)) {
                    if (isset($value[1])) {
                        if ($value[0] == 'add') {
                            if (is_numeric($value[1])) {
                                $info[] = "`{$key}`=`{$key}`+{$value[1]}";
                            }
                        } elseif ($value[0] == 'exp') {
                            //直接放入，危险
                            $info[] = "`{$key}`={$value[1]}";
                        } else {
                            $bind[$key] = $value;
                            $info[] = "`{$key}`=:{$key}";
                        }
                    } else {
                        $bind[$key] = $value[0];
                        $info[] = "`{$key}`=:{$key}";
                    }
                } else {
                    $bind[$key] = $value;
                    $info[] = "`{$key}`=:{$key}";
                }
            }
            if (!empty($pkdata) || !empty($info)) {
                $query->bind($bind, true);
                if (!empty($pkdata)) {
                    if (!empty($info)) {
                        $updateStr = implode(', ', $info);
                    }
                    $info[] = $pkdata;
                }
                $infoStr = implode(', ', $info);
            }
        }
        return $insert ? [$infoStr, $updateStr] : $infoStr;
    }

    protected function parseQueryWhere($query)
    {
        $return = '';
        $where = $query->getOptions('where', []);
        if (!empty($where)) {
            $return = $this->parseQueryWhereSub($where, 'AND', true);
        }
        return !empty($return) ? ' WHERE ' . $return : '';
    }

    protected function parseQueryWhereSub($array, $type = 'AND', $top = false)
    {
        $sql = '';
        $tempArray = [];
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                if (is_int($key)) {
                    if (is_string($value)) {
                        $tempArray[] = $value;
                    } else {
                        $tempArray[] = $this->parseQueryWhereSub($value, $type);
                    }
                } else {
                    if (in_array($key, array('and', 'or'))) {
                        $tempArray[] = $this->parseQueryWhereSub($value, strtoupper($key));
                    } else {
                        if (strpos($key, '|') > 0) {
                            $tempArray[] = $this->parseQueryWhereSub(array_fill_keys(explode('|', $key), $value), 'OR');
                        } elseif (strpos($key, '&') > 0) {
                            $tempArray[] = $this->parseQueryWhereSub(array_fill_keys(explode('&', $key), $value), 'AND');
                        } else {
                            $op = '=';
                            if (is_array($value)) {
                                if (isset($value[1])) {
                                    $op = $value[0];
                                    $value = $value[1];
                                } else {
                                    $value = $value[0];
                                }
                            }
                            if ($op == 'in') {
                                if (is_array($value)) {
                                    $indata = [];
                                    foreach ($value as $invalue) {
                                        $indata[] = (is_int($invalue) || is_float($invalue)) ? $invalue : $this->getPDO()->quote($invalue);
                                    }
                                    $value = implode(',', $indata);
                                }
                                //非数组没有过滤，危险
                                $tempArray[] = "`{$key}` IN ({$value})";
                            } elseif ($op == 'likeand' || $op == 'likeor') {
                                $likevalue = explode(' ', $value);
                                $likearray = [];
                                foreach ($likevalue as $linkone) {
                                    $linkone = trim($linkone);
                                    if ($linkone != '') {
                                        $likearray[] = "`{$key}` LIKE " . $this->getPDO()->quote("%{$linkone}%");
                                    }
                                }
                                $likestr = implode(' ' . strtoupper(substr($op, 4)) . ' ', $likearray);
                                if (count($likearray) > 1) {
                                    $likestr = "({$likestr})";
                                }
                                $tempArray[] = $likestr;
                            } elseif ($op == 'exp') {
                                //原样放入，危险
                                $tempArray[] = "`{$key}` {$value}";
                            } else {
                                if (!(is_int($value) || is_float($value) || $value[0] == ':')) {
                                    $value = $this->getPDO()->quote($value);
                                }
                                $tempArray[] = "`{$key}` {$op} {$value}";
                            }
                        }
                    }
                }
            }
        }
        $tempArray = array_diff($tempArray, ['']);
        $sql = implode(" {$type} ", $tempArray);
        if (!$top && count($tempArray) > 1) {
            $sql = "($sql)";
        }
        return $sql;
    }

    protected function parseQueryJoin($query)
    {
        $return = [];
        $join = $query->getOptions('join', []);
        if (!empty($join)) {
            foreach ($join as $value) {
                $return[] = $value['type'] . ' JOIN ' . $value['table'] . ' ON ' . (is_array($value['condition']) ? implode(' AND ', $value['condition']) : $value['condition']);
            }
        }
        $join = $query->getOptions('joinModel', []);
        if (!empty($join)) {
            foreach ($join as $value) {
                $return[] = $value['type'] . ' JOIN ' . $value['table'] . ' ' . $value['alias'] . ' ON ' . (is_array($value['condition']) ? implode(' AND ', $value['condition']) : $value['condition']);
            }
        }
        return !empty($return) ? ' ' . implode(' ', $return) : '';
    }

    protected function parseQueryOrder($query, $pre = '')
    {
        if ($pre != '') {
            $pre = $pre . '.';
        }
        $return = [];
        $order = $query->getOptions('order', []);
        if (!empty($order)) {
            foreach ($order as $key => $value) {
                $return[] = $pre . $key . ' ' . $value;
            }
        }
        return !empty($return) ? ' ORDER BY ' . implode(',', $return) : '';
    }

    /**
     * 解析pdo连接的dsn信息
     * @param  array $config 连接信息
     * @return string
     */
    protected function parseDsn(array $config)
    {
        if (!empty($config['socket'])) {
            $dsn = 'mysql:unix_socket=' . $config['socket'];
        } elseif (!empty($config['port'])) {
            $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'];
        } else {
            $dsn = 'mysql:host=' . $config['host'];
        }
        $dsn .= ';dbname=' . $config['database'];
        if (!empty($config['charset'])) {
            $dsn .= ';charset=' . $config['charset'];
        }
        return $dsn;
    }

    /**
     * 连接成功后执行动作
     * @return void
     */
    protected function connectAfter()
    {
        $PDO = $this->getPDO();
        $PDO->exec("SET time_zone='" . date('P') . "';");
    }

    protected function formatFieldType($type)
    {
        if (strpos($type, 'int') !== false) {
            return 'int';
        } elseif (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
            return 'string';
        } elseif (strpos($type, 'blob') !== false) {
            return 'blob';
        } elseif (strpos($type, 'double') !== false || strpos($type, 'decimal') !== false || strpos($type, 'float') !== false) {
            return 'float';
        } else {
            return $type;
        }
    }
}
