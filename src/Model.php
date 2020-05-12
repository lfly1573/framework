<?php

/**
 * 模型基类
 */

namespace lfly;

class Model
{
    /**
     * 设置当前模型对应的数据表名，数组格式为原始表名
     * @var string|array
     */
    protected $table;

    /**
     * 设置当前连接数据库engine名称
     * @var string
     */
    protected $connection;

    /**
     * 设置当前字段属性，如果设定就需要把相关的都设定好，不设定自动查询，默认识别添加时间字段create_time，修改update_time，软删除delete_time
     * ['字段名称'=>[
     *      '类型:string(默认)|int|bool|float|blob|date|datetime',
     *      '特点:(默认空)pk|json|createTime|updateTime|deleteTime',
     *      '禁止修改:false(默认)|true',
     *      '列表返回:false(默认)|true',
     *    ]
     * ]
     * @var array
     */
    protected $field = [];

    /**
     * 设置当前输入转换
     * ['输入字段名称'=>[
     *      '对应数据库实际字段',
     *      '格式化函数或方法名',
     *    ]
     * ]
     * @var array
     */
    protected $inSet = [];

    /**
     * 设置当前输出转换
     * ['输出字段名称'=>[
     *      '对应数据库实际字段',
     *      '格式化函数或方法名',
     *      '删除原值:true|false(默认)',
     *    ]
     * ]
     * @var array
     */
    protected $outSet = [];

    /**
     * 设置当前类被关联的输出字段
     * ['输出字段名称'=>[
     *      '对应数据库实际字段',
     *      '格式化函数或方法名',
     *    ]
     * ]
     * @var array
     */
    protected $joinSet = [];

    /**
     * 当前设置格式化
     * @var array
     */
    protected $config;

    /**
     * 当前内置时间常量字段
     */
    const timeParam = array('createTime', 'updateTime', 'deleteTime');

    /**
     * 构造函数
     */
    public function __construct($table = null)
    {
        $this->setTable($table);
        $this->init();
        $this->setConfig();
    }

    /**
     * 获取当前数据库对象
     * @return Query
     */
    public function db()
    {
        $query = \Db::connect($this->connection)->model($this)->table($this->table);
        if (!empty($this->config['pk'])) {
            $query->pkid($this->config['pk']);
        }
        if (!empty($this->config['deleteTime'])) {
            $query->deleteField($this->config['deleteTime']);
        }
        $this->addQuery($query);
        return $query;
    }

    /**
     * 设置表
     * @param string $table 表名
     */
    public function setTable($table = null)
    {
        if (!empty($table)) {
            $this->table = $table;
        } elseif (empty($this->table)) {
            $this->table = \Validate::convToSnake(basename(str_replace('\\', '/', static::class)));
        }
    }

    /**
     * 获取表
     * @return string
     */
    public function getTable()
    {
        return $this->config['table'];
    }

    /**
     * 获取主键
     * @return string
     */
    public function getPK()
    {
        return $this->config['pk'];
    }

    /**
     * 获取默认值
     * @param bool $format 是否格式化
     * @return array
     */
    public function getDefault(bool $format = false)
    {
        $return = [];
        $fieldArray = $this->getTableInfo();
        foreach ($fieldArray['original'] as $key => $value) {
            $return[$key] = $value['default'];
        }
        if ($format) {
            return $this->formatData($return);
        }
        return $return;
    }

    /**
     * 提供当前写入数据格式化
     * @param array $data 写入数据
     * @param bool  $edit 是否编辑
     * @return array
     */
    public function formatBindData($data, bool $edit = false)
    {
        $return = [];
        foreach ($data as $key => $value) {
            if (isset($this->inSet[$key])) {
                $cur = $this->inSet[$key];
                if (isset($cur[1])) {
                    $return[$cur[0]] = $this->formatByRule($value, $cur[1], $data);
                } elseif (isset($this->field[$cur[0]])) {
                    $return[$cur[0]] = $this->formatByRule($value, $this->field[$cur[0]][0]);
                } else {
                    $return[$cur[0]] = $value;
                }
            } elseif (isset($this->field[$key])) {
                $return[$key] = $this->formatByRule($value, $this->field[$key][0]);
            } else {
                $return[$key] = $value;
            }
        }
        if ($edit) {
            foreach ($this->config['notedit'] as $key) {
                if (isset($return[$key])) {
                    unset($return[$key]);
                }
            }
        } else {
            if (!empty($this->config['createTime'])) {
                $createTime = $this->config['createTime'];
                $return[$createTime] = time();
            }
        }
        if (!empty($this->config['updateTime'])) {
            $updateTime = $this->config['updateTime'];
            $return[$updateTime] = time();
        }
        foreach ($this->config['json'] as $key) {
            if (isset($return[$key]) && is_array($return[$key])) {
                $return[$key] = json_encode($return[$key]);
            }
        }
        return $return;
    }

    /**
     * 提供当前主数据的格式化
     * @param array $data 结果数组
     * @return array
     */
    public function formatData($data)
    {
        $return = [];
        foreach ($data as $key => $value) {
            if (isset($this->field[$key])) {
                $return[$key] = $this->formatByRule($value, $this->field[$key][0]);
            } else {
                $return[$key] = $value;
            }
        }
        if (!empty($this->outSet)) {
            foreach ($this->outSet as $key => $value) {
                if (isset($return[$value[0]])) {
                    if (isset($value[1])) {
                        $return[$key] = $this->formatByRule($return[$value[0]], $value[1], $data);
                    } elseif ($key != $value[0]) {
                        $return[$key] = $return[$value[0]];
                    }
                    if (isset($value[2]) && $value[2]) {
                        unset($return[$value[0]]);
                    }
                }
            }
        }
        foreach ($this->config['json'] as $key) {
            if (isset($return[$key]) && !is_array($return[$key])) {
                $return[$key] = @json_decode($return[$key], true);
            }
        }
        $return = $this->formatReturn($return);
        return $return;
    }

    /**
     * 提供列表查询字段
     * @return array
     */
    public function getListField()
    {
        return $this->config['list'];
    }

    /**
     * 提供对外关联查询字段
     * @param string $alias 表别名
     * @return string
     */
    public function getJoinField($alias)
    {
        $return = [];
        foreach ($this->joinSet as $key => $value) {
            $return[$value[0]] = "{$alias}.{$value[0]} AS " . $this->formatJoinField($value[0]);
        }
        return !empty($return) ? implode(', ', $return) : '';
    }

    /**
     * 提供对外关联查询内容格式化
     * @param array $data 结果数组
     * @return array
     */
    public function formatJoinData($data)
    {
        $return = [];
        if (!empty($this->joinSet)) {
            foreach ($this->joinSet as $key => $value) {
                $curkey = $this->formatJoinField($value[0]);
                if (isset($data[$curkey])) {
                    if (isset($value[1])) {
                        $return[$key] = $this->formatByRule($data[$curkey], $value[1], $data);
                    } else {
                        $return[$key] = $data[$curkey];
                    }
                }
            }
        }
        foreach ($this->config['json'] as $key) {
            if (isset($return[$key]) && !is_array($return[$key])) {
                $return[$key] = @json_decode($return[$key], true);
            }
        }
        return $return;
    }

    /**
     * 继承实现：查询结果集的额外格式化
     * @param array $data 当前单条结果
     * @return array
     */
    public function formatReturn($data)
    {
        return $data;
    }

    /**
     * 继承实现：插入数据前执行，不支持多条插入
     * @param mixed $query 当前执行query
     * @return bool
     */
    public function onBeforeInsert($query)
    {
        return true;
    }

    /**
     * 继承实现：插入数据成功后执行，不支持多条插入
     * @param mixed $query    当前执行query
     * @return void
     */
    public function onAfterInsert($query)
    {
    }

    /**
     * 继承实现：更新数据前执行
     * @param mixed $query 当前执行query
     * @return bool
     */
    public function onBeforeUpdate($query)
    {
        return true;
    }

    /**
     * 继承实现：更新数据成功后执行，软删除也属于该类型
     * @param mixed $query 当前执行query
     * @return void
     */
    public function onAfterUpdate($query)
    {
    }

    /**
     * 继承实现：删除数据前执行
     * @param mixed $query 当前执行query
     * @return bool
     */
    public function onBeforeDelete($query)
    {
        return true;
    }

    /**
     * 继承实现：删除数据成功后执行
     * @param mixed $query 当前执行query
     * @return void
     */
    public function onAfterDelete($query)
    {
    }

    /**
     * 继承实现：自定义初始化函数
     */
    protected function init()
    {
    }

    /**
     * 继承实现：增加查询条件
     * @param object $query 查询对象
     * @return void
     */
    protected function addQuery($query)
    {
    }

    //额外实现格式化方法 public function action($value, $data = [])

    /**
     * 格式化数据
     * @param mixed  $value 值
     * @param string $rule  规则
     * @param array  $data  全部数据
     * @return mixed
     */
    protected function formatByRule($value, $rule = '', $data = [])
    {
        if (!empty($rule)) {
            if ($rule == 'int') {
                $value = intval($value);
            } elseif ($rule == 'float') {
                $value = floatval($value);
            } elseif ($rule == 'bool') {
                $value = !empty($value) ? 1 : 0;
            } elseif ($rule == 'string') {
                if (!is_string($value) && !is_array($value)) {
                    $value = strval($value);
                }
            } elseif ($rule[0] == '\\') {
                $value = $rule($value);
            } elseif (method_exists($this, $rule)) {
                $value = call_user_func_array([$this, $rule], [$value, $data]);
            }
        }
        return $value;
    }

    /**
     * 格式化关联字段
     * @param string $field 字段
     * @return string
     */
    protected function formatJoinField($field)
    {
        return $this->getTable() . '_' . $field;
    }

    /**
     * 获取表信息
     */
    protected function getTableInfo()
    {
        $db = $this->db();
        $cacheFile = CACHE_PATH . $db->getConnectSign() . '/' . $db->getOptions('table') . EXT;
        $cacheTime = is_file($cacheFile) ? filemtime($cacheFile) : 0;
        $refreshTime = $db->getChangeFieldTime();
        if ($cacheTime > 0 && $cacheTime >= $refreshTime) {
            $fieldArray = (include $cacheFile);
        } else {
            $fieldArray = ['original' => $db->getFields(), 'field' => []];
            foreach ($fieldArray['original'] as $value) {
                $fieldArray['field'][$value['field']] = [$value['formatType']];
                if ($value['primary']) {
                    $fieldArray['field'][$value['field']][1] = 'pk';
                } elseif (substr($value['field'], -4) == 'json') {
                    $fieldArray['field'][$value['field']][1] = 'json';
                }
            }
            foreach (static::timeParam as $value) {
                $fieldName = \Validate::convToSnake($value);
                if (isset($fieldArray['field'][$fieldName])) {
                    $fieldArray['field'][$fieldName][1] = $value;
                }
            }
            \Cache::writeFile($cacheFile, $fieldArray);
        }
        return $fieldArray;
    }

    /**
     * 格式化设定
     */
    protected function setConfig()
    {
        if (empty($this->config)) {
            if (empty($this->field)) {
                $fieldArray = $this->getTableInfo();
                $this->field = $fieldArray['field'];
            }
            $db = $this->db();
            $config = ['table' => $db->getOptions('table'), 'pk' => '', 'json' => [], 'notedit' => [], 'list' => []];
            foreach ($this->field as $key => $value) {
                if (isset($value[1])) {
                    if ($value[1] == 'pk') {
                        $config['pk'] = $key;
                    } elseif ($value[1] == 'json') {
                        $config['json'][] = $key;
                    } elseif (in_array($value[1], static::timeParam)) {
                        $config[$value[1]] = $key;
                    }
                }
                if ((isset($value[2]) && $value[2]) || (isset($value[1]) && $value[1] == 'pk')) {
                    $config['notedit'][] = $key;
                }
                if ((isset($value[3]) && $value[3])) {
                    $config['list'][] = $key;
                }
            }
            $this->config = $config;
        }
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->db(), $method], $args);
    }

    public static function __callStatic($method, $args)
    {
        $model = new static();
        return call_user_func_array([$model->db(), $method], $args);
    }
}
