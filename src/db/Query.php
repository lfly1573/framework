<?php

/**
 * 数据查询条件类
 */

namespace lfly\db;

use lfly\contract\ConnectionHandlerInterface;

class Query
{
    /**
     * 当前数据库连接对象
     * @var ConnectionHandlerInterface
     */
    protected $connection;

    /**
     * 当前数据表前缀
     * @var string
     */
    protected $prefix = '';

    /**
     * 当前查询参数
     * @var array
     */
    protected $options = [];
    /*
        设置格式汇总：
        [
            'table' => '表名',
            'alias' => '表别名',
            'temptable' => 'sql查询的临时表',
            'extra' => '扩充命令如：IGNORE',
            'field' => ['字段1', '原始字段2'],
            'force' => '强制索引名',
            'join' => [['table' => '表名 别名', 'condition' => ['条件1', '条件2'], 'type' => '类型']],
            'joinModel' => [['model' => '关联的模型类实例', 'table' => '表名', 'alias'=>'表别名', 'condition' => ['条件1', '条件2'], 'type' => '类型']],
            'where' => [
                '原生sql可带变量',
                ['字段1' => '条件1'],
                ['字段2' => ['操作2(比较符/like/in/exp)', '条件2']],
                ['or/and' => [多个条件]]
            ],
            'bind' => ['绑定变量1' => '取值1', '绑定变量2' => ['取值2', '类型'], '绑定变量3' => ['add/exp', '取值3']],
            'group' => '字段1,字段2',
            'having' => '内容',
            'order' => [['字段1' => 'ASC'], ['字段2' => 'DESC']],
            'limit' => '10,20',
            'pkid' => '主键名',
            'lock' => '查询锁定命令',
            'replace' => '插入时使用替换模式 bool',
            'master' => '连接读写服务器 bool',
            'pointer' => '是否绑定指针变量 bool',
            'model' => '绑定模型类实例',
            'deleteField' => '软删除字段名',
            'insertId' => '当前插入后的自增id供模型用',
        ]
     */

    /**
     * 构造函数
     * @param ConnectionHandlerInterface $connection 数据库连接对象
     */
    public function __construct(ConnectionHandlerInterface $connection)
    {
        $this->connection = $connection;
        $this->prefix = $this->connection->getConfig('prefix');
    }

    /**
     * 获取当前的查询参数
     * @param string $name    参数名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function getOptions($name = '', $default = null)
    {
        if ('' === $name) {
            return $this->options;
        }
        return $this->options[$name] ?? $default;
    }

    /**
     * 设置当前的查询参数
     * @param string $name  参数名
     * @param mixed  $value 参数值
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * 指定当前数据表名
     * @param string|array $table 数据表名字 空格+别名可选，数组格式为完整表名 ['完整表名','别名']
     * @return $this
     */
    public function table($table)
    {
        if (is_string($table)) {
            $table = array_map('trim', explode(' ', $this->prefix . $table, 2));
        }
        $this->setOption('table', $table[0]);
        if (!empty($table[1])) {
            $this->alias($table[1]);
        }
        return $this;
    }

    /**
     * 设定数据表名的别名
     * @param string $alias 表别名
     * @return $this
     */
    public function alias($alias)
    {
        $this->options['alias'] = $alias;
        return $this;
    }

    /**
     * sql查询的临时表
     * @param string 查询的sql语句
     * @return $this
     */
    public function tempTable($sql)
    {
        $this->options['temptable'] = $sql;
        return $this;
    }

    /**
     * 指定查询字段
     * @param mixed $field 字段信息 '字段1,字段2 as 别名2' 或者 ['字段1','字段2'=>'别名2',['字段3','别名3']]
     * @return $this
     */
    public function field($field)
    {
        if (empty($field)) {
            return $this;
        }
        $curfield = [];
        if (is_string($field)) {
            $curfield = array_map('trim', explode(',', $field));
        } elseif (is_array($field)) {
            foreach ($field as $key => $value) {
                if (is_int($key)) {
                    if (is_array($value)) {
                        if (isset($value[1])) {
                            $curfield[] = $value[0] . ' AS ' . $value[1];
                        } else {
                            $curfield[] = $value[0];
                        }
                    } else {
                        $curfield[] = $value;
                    }
                } else {
                    $curfield[] = $key . ' AS ' . $value;
                }
            }
        }
        $this->options['field'] = array_unique(array_merge($this->options['field'] ?? [], $curfield));
        return $this;
    }

    /**
     * 原样查询字段
     * @param mixed $field 字段信息
     * @return $this
     */
    public function fieldRaw($field)
    {
        $this->options['field'][] = $field;
        return $this;
    }

    /**
     * 关联查询
     * @param string|array $table     要关联的表 空格+别名可选, 数组格式为完整表名 ['完整表名','别名']
     * @param string|array $condition 关联条件
     * @param string       $type      关联方式 INNER/LEFT/RIGHT/FULL
     * @return $this
     */
    public function join($table, $condition, $type = 'INNER')
    {
        if (is_array($table)) {
            $table = $table[0] . (isset($table[1]) ? ' ' . $table[1] : '');
        } else {
            $table = $this->prefix . $table;
        }
        $this->options['join'][] = ['table' => $table, 'condition' => (array)$condition, 'type' => $type];
        return $this;
    }

    /**
     * 左关联查询
     * @param string|array $table     要关联的表 空格+别名可选, 数组格式为完整表名 ['完整表名','别名']
     * @param string|array $condition 关联条件
     * @return $this
     */
    public function leftJoin($table, $condition = [])
    {
        $this->join($table, $condition, 'LEFT');
        return $this;
    }

    /**
     * 关联模型查询
     * @param string       $model     模型类名
     * @param string       $alias     模型对应表别名
     * @param string|array $condition 关联条件
     * @param string       $type      关联方式 INNER/LEFT/RIGHT/FULL
     * @return $this
     */
    public function joinModel($model, $alias, $condition, $type = 'INNER')
    {
        $modelObject = $db->invokeModel($model);
        $field = $modelObject->getJoinField($alias);
        if (!empty($field)) {
            $this->fieldRaw($field);
        }
        $this->options['joinModel'][] = ['model' => $modelObject, 'table' => $modelObject->getTable(), 'alias' => $alias, 'condition' => (array)$condition, 'type' => $type];
        return $this;
    }

    /**
     * 条件查询
     * @param mixed $field 条件信息 一个参数：数组 两个参数为：字段=值 三个参数：字段、操作符、值
     * @return $this
     */
    public function where(...$condition)
    {
        if (isset($condition[2])) {
            $this->options['where'][] = [$condition[0] => [$condition[1], $condition[2]]];
        } elseif (isset($condition[1])) {
            $this->options['where'][] = [$condition[0] => $condition[1]];
        } elseif (!empty($condition[0])) {
            $this->options['where'][] = $condition[0];
        }
        return $this;
    }

    /**
     * 原样条件查询
     * @param string $condition 条件信息 危险！注意自行转义用户输入字符或使用绑定
     * @param array  $bind      绑定值
     * @return $this
     */
    public function whereRaw($condition, array $bind = [])
    {
        $this->options['where'][] = $condition;
        if (!empty($bind)) {
            $this->bind($bind);
        }
        return $this;
    }

    /**
     * 参数绑定
     * @param array $data  绑定值 格式如：['绑定变量1' => '取值1', '绑定变量2' => ['取值2', '类型']] 特殊情况如插入多行是二维数组
     * @param bool  $clean 是否清空旧数据
     * @return $this
     */
    public function bind($data, bool $clean = false)
    {
        $this->options['bind'] = $clean ? $data : array_merge($this->options['bind'] ?? [], $data);
        return $this;
    }

    /**
     * 指定查询数量
     * @param int $offset 起始位置
     * @param int $length 查询数量
     * @return $this
     */
    public function limit($offset, $length = null)
    {
        $this->options['limit'] = $offset . ($length ? ',' . $length : '');
        return $this;
    }

    /**
     * 根据页数计算数量
     * @param int $page   当前页码
     * @param int $length 每页显示数
     * @return $this
     */
    public function page($page, $length)
    {
        $page = max(1, intval($page));
        $this->options['limit'] = (($page - 1) * $length) . ',' . $length;
        return $this;
    }

    /**
     * 排序
     * @param mixed $order 排序信息 一个参数：数组['key1','key2'=>'DESC']或字符 两个参数为：字段,排序方式默认是asc
     * @return $this
     */
    public function order(...$order)
    {
        if (isset($order[1])) {
            $this->options['order'][$order[0]] = strtoupper($order[1]);
        } elseif (is_array($order[0])) {
            foreach ($order[0] as $key => $value) {
                if (is_int($key)) {
                    $this->options['order'][$value] = 'ASC';
                } else {
                    $this->options['order'][$key] = strtoupper($value);
                }
            }
        } else {
            $this->options['order'][$order[0]] = 'ASC';
        }
        return $this;
    }

    /**
     * group分组
     * @param string $field 字段，多个逗号隔开
     * @return $this
     */
    public function group($field)
    {
        $this->options['group'] = $field;
        return $this;
    }

    /**
     * having设定
     * @param string $field 内容
     * @return $this
     */
    public function having($field)
    {
        $this->options['having'] = $field;
        return $this;
    }

    /**
     * force强制索引设定
     * @param string $field 索引名
     * @return $this
     */
    public function force($field)
    {
        $this->options['force'] = $field;
        return $this;
    }

    /**
     * 主键字段
     * @param string $field 当前主键字段
     * @return $this
     */
    public function pkid($field)
    {
        $this->options['pkid'] = $field;
        return $this;
    }

    /**
     * 插入时使用替换模式
     * @return $this
     */
    public function replace()
    {
        $this->options['replace'] = true;
        return $this;
    }

    /**
     * 扩充命令
     * @param string $command 命令，如：IGNORE 插入时忽略错误
     * @return $this
     */
    public function extra($command)
    {
        $this->options['extra'] = $command;
        return $this;
    }

    /**
     * 锁定命令
     * @param string $command 命令，设定后自动在 SELECT 后加入该命令
     * @return $this
     */
    public function lock($command = 'FOR UPDATE')
    {
        $this->options['lock'] = $command;
        return $this;
    }

    /**
     * 连接读写服务器
     * @return $this
     */
    public function master()
    {
        $this->options['master'] = true;
        return $this;
    }

    /**
     * 指定连接服务器(但系统会判断是否自动切换到读写服务器)
     * @param int $linkNum 连接序号
     * @return $this
     */
    public function linkNum($linkNum)
    {
        $this->connection->connect([], $linkNum);
        return $this;
    }

    /**
     * 绑定指针变量
     * @return $this
     */
    public function pointer()
    {
        $this->options['pointer'] = true;
        return $this;
    }

    /**
     * 绑定到模型
     * @return $this
     */
    public function model($object)
    {
        $this->options['model'] = $object;
        return $this;
    }

    /**
     * 设定软删除字段
     * @return $this
     */
    public function deleteField($field = 'delete_time')
    {
        $this->options['deleteField'] = $field;
        return $this;
    }

    public function __call($method, $args = [])
    {
        array_unshift($args, $this);
        return call_user_func_array([$this->connection, $method], $args);
    }
}
