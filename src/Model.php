<?php

/**
 * 模型基类
 */

namespace lfly;

/**
 * @method Db getDb()
 * @method mixed getConfig($config = '')
 * @method \lfly\db\PDOConnection connect(array $config = [], $linkNum = 0)
 * @method mixed close()
 * @method array query($sql = '', array $bind = [])
 * @method \Generator cursor($sql = '', array $bind = [])
 * @method array execute($sql = '', array $bind = [])
 * @method int count($field = '*')
 * @method array find()
 * @method array select()
 * @method int|bool insert(array $data = [], bool $getId = false)
 * @method int insertAll(array $dataList = [])
 * @method int update(array $data = [])
 * @method int delete()
 * @method mixed value($field, $default = null)
 * @method array column($column = '*', $key = null)
 * @method bool exists()
 * @method string buildSql($type = 'select')
 * @method void startTrans()
 * @method void commit()
 * @method void rollback()
 * @method string getLastSql()
 * @method \lfly\db\Query setOption($name, $value)
 * @method \lfly\db\Query table($table)
 * @method \lfly\db\Query alias($alias)
 * @method \lfly\db\Query tempTable($sql)
 * @method \lfly\db\Query field($field)
 * @method \lfly\db\Query fieldRaw($field)
 * @method \lfly\db\Query join($table, $condition, $type = 'INNER')
 * @method \lfly\db\Query leftJoin($table, $condition = [])
 * @method \lfly\db\Query joinModel($model, $alias, $condition, $type = 'INNER')
 * @method \lfly\db\Query where(...$condition)
 * @method \lfly\db\Query whereRaw($condition, array $bind = [])
 * @method \lfly\db\Query bind($data, bool $clean = false)
 * @method \lfly\db\Query limit($offset, $length = null)
 * @method \lfly\db\Query page($page, $length)
 * @method \lfly\db\Query order(...$order)
 * @method \lfly\db\Query group($field)
 * @method \lfly\db\Query having($field)
 * @method \lfly\db\Query force($field)
 * @method \lfly\db\Query pkid($field)
 * @method \lfly\db\Query replace()
 * @method \lfly\db\Query extra($command)
 * @method \lfly\db\Query lock($command = 'FOR UPDATE')
 * @method \lfly\db\Query master()
 * @method \lfly\db\Query linkNum($linkNum)
 * @method \lfly\db\Query pointer()
 * @method \lfly\db\Query model($object)
 * @method \lfly\db\Query deleteField($field = 'delete_time')
 */
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
     * 设置是否自动刷新缓存字段数据，需缓存模块支持
     * @var bool
     */
    protected $refreshCacheField = false;

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
     * 设置是否软删除默认筛选
     * @var bool
     */
    protected $softDeleted = true;

    /**
     * 当前内置时间常量字段
     */
    const timeParam = array('createTime', 'updateTime', 'deleteTime');

    /**
     * 构造函数
     */
    public function __construct($table = null, $refreshCacheField = null)
    {
        $this->setTable($table);
        if (is_null($refreshCacheField)) {
            $refreshCacheField = \Config::get('database.refreshCacheField', false);
        }
        if (is_bool($refreshCacheField) === true) {
            $this->setNotRefreshCacheField($refreshCacheField);
        }
        $this->init();
        $this->setConfig();
    }

    /**
     * 获取当前数据库对象
     * @return \lfly\db\Query
     */
    public function db()
    {
        $query = \Db::connect($this->connection)->model($this)->table($this->table);
        if (!empty($this->config['pk'])) {
            $query->pkid($this->config['pk']);
        }
        if (!empty($this->config['deleteTime'])) {
            $query->deleteField($this->config['deleteTime']);
            if ($this->softDeleted) {
                $query->where($this->config['deleteTime'], 0);
            }
        }
        $this->addQuery($query);
        return $query;
    }

    /**
     * 设置表
     * @param string $table 表名
     * @return $this
     */
    public function setTable($table = null)
    {
        if (!empty($table)) {
            $this->table = $table;
        } elseif (empty($this->table)) {
            $this->table = \Validate::convToSnake(basename(str_replace('\\', '/', static::class)));
        }
        return $this;
    }

    /**
     * 设置不自动剔除软删除
     * @param bool $value 是否自动剔除
     * @return $this
     */
    public function setNotSoftDeleted(bool $value = false)
    {
        $this->softDeleted = $value;
        return $this;
    }

    /**
     * 设置不检测字段更改缓存变化
     * @param bool $value 是否检测字段更改缓存变化
     * @return $this
     */
    public function setNotRefreshCacheField(bool $value = false)
    {
        $this->refreshCacheField = $value;
        return $this;
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
            if (is_array($value)) {
                $return[$key] = $value;
            } elseif (isset($this->inSet[$key])) {
                $cur = $this->inSet[$key];
                if (isset($cur[1])) {
                    $return[$cur[0]] = $this->formatByRule($value, $cur[1], $data);
                } elseif (isset($this->field[$cur[0]])) {
                    $return[$cur[0]] = $this->formatByRule($value, $this->field[$cur[0]][0]);
                } else {
                    $return[$cur[0]] = $value;
                }
            } elseif (!is_null($value) && isset($this->field[$key])) {
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
            if (isset($return[$key]) && (is_array($return[$key]) || is_object($return[$key]))) {
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
                        $return[$key] = $this->formatByRule($return[$value[0]], $value[1], $data, $value[0]);
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
            if (isset($return[$key]) && (!is_array($return[$key]) || !is_object($return[$key]))) {
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
            $return[$value[0]] = "{$alias}.{$value[0]} AS {$key}";
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
        $return = $data;
        if (!empty($this->joinSet)) {
            foreach ($this->joinSet as $key => $value) {
                if (isset($data[$key]) || is_null($data[$key])) {
                    if (isset($value[1])) {
                        $return[$key] = $this->formatByRule($data[$key], $value[1], $data, $value[0]);
                    }
                    if (in_array($value[0], $this->config['json']) && (!is_array($return[$key]) || !is_object($return[$key]))) {
                        $return[$key] = @json_decode($return[$key], true);
                    }
                }
            }
        }
        return $return;
    }

    /**
     * 格式化已定义属性字段
     * @param int|string $value 当前数据
     * @param array      $data  全部数据
     * @param string     $field 当前字段名
     * @return mixed
     */
    public function formatFieldByProperty($value, $data = [], $field = '')
    {
        if ($field != '') {
            $param = 'field_' . $field;
            if (property_exists($this, $param)) {
                return $this->{$param}[$value] ?? '';
            }
        }
        return '';
    }

    /**
     * 获取已定义属性字段
     * @param string $field 当前字段名
     * @return array
     */
    public function getFieldByProperty($field)
    {
        $param = 'field_' . $field;
        if (property_exists($this, $param)) {
            return $this->{$param};
        }
        return [];
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
     * 继承实现：查询数据前执行
     * @param mixed $query 当前执行query
     * @return array|bool 返回数组将不再数据库查询
     */
    public function onBeforeSelect($query)
    {
        return true;
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

    //额外实现格式化方法 public function action($value, $data = [], $field = '')

    /**
     * 格式化数据
     * @param mixed  $value 值
     * @param string $rule  规则
     * @param array  $data  全部数据
     * @param string $field 当前字段名
     * @return mixed
     */
    protected function formatByRule($value, $rule = '', $data = [], $field = '')
    {
        if (!empty($rule)) {
            if ($rule == 'int') {
                $value = intval($value);
            } elseif ($rule == 'float') {
                $value = floatval($value);
            } elseif ($rule == 'bool') {
                $value = !empty($value) ? 1 : 0;
            } elseif ($rule == 'string') {
                if (!is_string($value) && !is_array($value) && !is_object($value)) {
                    $value = strval($value);
                }
            } elseif ($rule[0] == '\\') {
                $value = $rule($value);
            } elseif (method_exists($this, $rule)) {
                $value = call_user_func_array([$this, $rule], [$value, $data, $field]);
            }
        }
        return $value;
    }

    /**
     * 获取表信息
     */
    protected function getTableInfo()
    {
        $db = $this->db();
        $cacheFile = CACHE_PATH . $db->getDbSign() . '/' . $db->getOptions('table') . EXT;
        $cacheTime = is_file($cacheFile) ? filemtime($cacheFile) : 0;
        $refreshTime = $this->refreshCacheField ? $db->getChangeFieldTime() : 0;
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
