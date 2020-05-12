<?php

/**
 * PDO数据库类
 */

namespace lfly\db;

use PDO;
use PDOStatement;
use PDOException;
use RuntimeException;
use Throwable;
use Exception;
use lfly\contract\ConnectionHandlerInterface;

abstract class PDOConnection implements ConnectionHandlerInterface
{
    /**
     * 数据库连接参数配置
     * @var array
     */
    protected $config = [
        //数据库类型
        'type' => 'mysql',
        //服务器地址
        'host' => '',
        //连接端口
        'port' => '',
        //数据库名
        'database' => '',
        //用户名
        'username' => '',
        //密码
        'password' => '',
        //数据库连接参数
        'params' => [],
        //数据库编码默认采用utf8mb4
        'charset' => 'utf8mb4',
        //数据库表前缀
        'prefix' => '',
        //主服务器数量
        'master_num' => 1,
        //读写分离
        'rw_separate' => false,
        //Query类
        'query' => '',
        //记录sql日志
        'log' => false,
        //慢查询秒数设定(配合专门日志记录)
        //'slow_time' => 0,
    ];

    /**
     * Db对象
     * @var Db
     */
    protected $db;

    /**
     * 重连次数
     * @var int
     */
    protected $reConnectTimes = 0;

    /**
     * 事务指令数
     * @var int
     */
    protected $transTimes = 0;

    /**
     * PDO操作参数
     * @var array
     */
    protected $PDOData = [];

    /**
     * 查询结果类型
     * @var int
     */
    protected $fetchType = PDO::FETCH_ASSOC;

    /**
     * 服务器数量
     * @var int
     */
    protected $allNum;

    /**
     * 数据库连接对象
     * @var array
     */
    protected $links = [];

    /**
     * 当前连接序号
     * @var int
     */
    protected $curLinkNum;

    /**
     * 连接读写服务器
     * @var bool
     */
    protected $linkMaster = false;

    /**
     * 当前连接失败序号
     * @var array
     */
    protected $linkErrorNum = [];

    /**
     * PDO连接参数
     * @var array
     */
    protected $params = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * 取得数据表的字段信息
     * @param string $tableName 数据表名称
     * @return array
     */
    abstract public function getFields($query, $tableName = null);

    /**
     * 取得数据库的表信息
     * @param string $dbName 数据库名称
     * @return array
     */
    abstract public function getTables($query, $dbName = '');

    /**
     * 生成sql语句
     * @param  Query $query 查询对象
     * @param  string $type 操作
     * @return string
     */
    abstract public function buildSql($query, $type = 'select');

    /**
     * 解析pdo连接的dsn信息
     * @param array $config 连接信息
     * @return string
     */
    abstract protected function parseDsn(array $config);

    /**
     * 连接成功后执行动作
     * @return void
     */
    abstract protected function connectAfter();

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        $this->allNum = is_array($this->config['host']) ? count($this->config['host']) : 1;
    }

    /**
     * 获取当前连接器类对应的Query类
     * @return string
     */
    public function getQueryClass()
    {
        return !empty($this->getConfig('query')) ? $this->getConfig('query') : Query::class;
    }

    /**
     * 获取数据库的配置参数
     * @param string $config 配置名称
     * @return mixed
     */
    public function getConfig($config = '')
    {
        if ('' === $config) {
            return $this->config;
        }
        return $this->config[$config] ?? null;
    }

    /**
     * 获取当前连接器的唯一标识
     * @return string
     */
    public function getConnectSign()
    {
        return md5($this->getConfig('host') . $this->getConfig('port') . $this->getConfig('database'));
    }

    /**
     * 设置当前的数据库Db对象
     * @param Db $db
     * @return void
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * 设置日志
     * @param string       $op   操作
     * @param string|array $info 内容
     * @param string       $type 类型
     * @return void
     */
    public function setLog($op, $info = '', $type = 'db')
    {
        if ($this->config['log'] || $type != 'db') {
            $this->db->setLog($op, $info, $type);
        }
    }

    /**
     * 执行查询但只返回PDOStatement对象
     * @param string $sql       sql指令
     * @param array  $bind      参数绑定
     * @param bool   $master    是否在主服务器读操作
     * @param bool   $bindParam 是否动态参数绑定
     * @return PDOStatement
     * 
     * @throws RuntimeException
     */
    public function getPDOStatement($sql, array $bind = [], bool $master = false, bool $bindParam = false) : PDOStatement
    {
        $curLinkObj = $this->initConnect($master);
        $this->PDOData = [];

        $this->PDOData['queryStr'] = $sql;
        $this->PDOData['bind'] = $bind;
        $this->db->updateQueryTimes();
        try {
            $this->PDOData['queryStartTime'] = microtime(true);

            $this->PDOData['PDOStatement'] = $curLinkObj->prepare($sql);
            $this->bindData($bind, $bindParam);
            $this->PDOData['executeResult'] = $this->PDOData['PDOStatement']->execute();
            $this->PDOData['queryCostTime'] = $this->formatTime($this->PDOData['queryStartTime']);

            $isSlowTime = (isset($this->config['slow_time']) && $this->PDOData['queryCostTime'] >= $this->config['slow_time']);
            if ($this->config['log'] || $isSlowTime) {
                $msg = ['sql' => $this->getLastsql(), 'serverID' => $this->curLinkNum, 'costTime' => $this->PDOData['queryCostTime']];
                if ($isSlowTime) {
                    $this->setLog('querySlow', $msg, 'querySlow');
                    $this->db->trigger('QuerySlow', $msg);
                } else {
                    $this->setLog('query', $msg);
                }
            }

            $this->reConnectTimes = 0;
            return $this->PDOData['PDOStatement'];
        } catch (Throwable | Exception $e) {
            if ($this->transTimes == 0 && $this->reConnectTimes < 1) {
                ++$this->reConnectTimes;
                $this->setLog('queryReConnect', ['sql' => $this->getLastsql(), 'serverID' => $this->curLinkNum, 'costTime' => $this->formatTime($this->PDOData['queryStartTime'])], 'error');
                return $this->close()->getPDOStatement($sql, $bind, $master, $bindParam);
            }
            $msg = ['sql' => $this->getLastsql(), 'info' => $e->getMessage(), 'serverID' => $this->curLinkNum, 'costTime' => $this->formatTime($this->PDOData['queryStartTime'])];
            $this->setLog('queryError', $msg, 'error');
            $this->db->trigger('QueryError', $msg);
            throw new RuntimeException('SQL Error');
        }
    }

    /**
     * 获取数据库连接
     * @param boolean $master 是否主服务器
     * @return PDO
     * 
     * @throws RuntimeException
     */
    public function initConnect(bool $master = false)
    {
        if ($master) {
            $this->linkMaster = true;
        } else {
            $master = $this->linkMaster;
        }

        if (!is_null($this->curLinkNum)) {
            $curIsMaster = $this->curLinkNum < $this->config['master_num'];
            if (!$master || $curIsMaster) {
                return $this->links[$this->curLinkNum];
            }
        }

        if ($this->allNum == 1) {
            $linkNum = 0;
            if (!empty($this->linkErrorNum)) {
                throw new RuntimeException('database server has gone away');
            }
        } else {
            if ($master) {
                $availableLink = array_fill(0, $this->config['master_num'], 1);
            } elseif ($this->config['rw_separate']) {
                $availableLink = array_fill($this->config['master_num'], $this->allNum - $this->config['master_num'], 1);
            } else {
                $availableLink = array_fill(0, $this->allNum, 1);
            }
            if (!empty($this->linkErrorNum)) {
                $availableLink = array_diff_key($availableLink, $this->linkErrorNum);
            }
            if (empty($availableLink)) {
                throw new RuntimeException('database server has gone away');
            }
            $linkNum = array_rand($availableLink);
        }
        return $this->connect([], $linkNum);
    }

    /**
     * 连接数据库方法
     * @param array $config  连接参数
     * @param int   $linkNum 连接序号
     * @return PDO
     * 
     * @throws PDOException
     */
    public function connect(array $config = [], $linkNum = 0)
    {
        if ($linkNum >= $this->allNum) {
            return $this->initConnect();
        }

        $this->curLinkNum = $linkNum;
        if (isset($this->links[$linkNum])) {
            return $this->links[$linkNum];
        }

        if (empty($config)) {
            $config = $this->getConnectConfig($linkNum);
        }
        $config = array_merge($this->config, $config);

        if (isset($config['params']) && is_array($config['params'])) {
            $params = $config['params'] + $this->params;
        } else {
            $params = $this->params;
        }

        $startTime = microtime(true);
        try {
            if (empty($config['dsn'])) {
                $config['dsn'] = $this->parseDsn($config);
            }
            $this->links[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $params);
            $this->connectAfter();
            $this->setLog('connect', ['dsn' => $config['dsn'], 'costTime' => $this->formatTime($startTime)]);
            return $this->links[$linkNum];
        } catch (PDOException $e) {
            $this->linkErrorNum[$linkNum] = $linkNum;
            $msg = ['info' => $e->getMessage(), 'dsn' => $config['dsn'], 'serverID' => $this->curLinkNum, 'costTime' => $this->formatTime($startTime)];
            $this->setLog('connectError', $msg, 'error');
            $this->db->trigger('ConnectError', $msg);
            return $this->initConnect();
        }
    }

    /**
     * 获取数据库连接配置信息
     * @param int   $linkNum 连接序号
     * @return array
     */
    public function getConnectConfig($linkNum)
    {
        $config = [];
        foreach (['host', 'port', 'database', 'username', 'password', 'dsn', 'charset'] as $name) {
            if (isset($this->config[$name])) {
                if (is_string($this->config[$name])) {
                    $config[$name] = $this->config[$name];
                } elseif (isset($this->config[$name][$linkNum])) {
                    $config[$name] = $this->config[$name][$linkNum];
                } else {
                    $config[$name] = $this->config[$name][0];
                }
            }
        }
        return $config;
    }

    /**
     * 获取PDO对象
     * @return PDO
     */
    public function getPDO()
    {
        if (is_null($this->curLinkNum)) {
            return $this->initConnect();
        }
        return $this->links[$this->curLinkNum];
    }

    /**
     * 执行查询 返回数据集
     * @param Query  $query 查询对象
     * @param string $sql   sql指令
     * @param array  $bind  参数绑定
     * @return array
     */
    public function query($query, $sql = '', array $bind = [])
    {
        if (empty($sql)) {
            $sql = $this->buildSql($query, 'select');
        }
        $bind = array_merge($query->getOptions('bind', []), $bind);
        $master = $query->getOptions('master', false);
        $bindParam = !empty($query->getOptions('pointer')) ? true : in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);

        $this->getPDOStatement($sql, $bind, $master, $bindParam);
        $result = [];
        if ($bindParam) {
            do {
                $item = $this->PDOData['PDOStatement']->fetchAll($this->fetchType);
                if (!empty($item)) {
                    $result[] = $item;
                }
            } while ($this->PDOData['PDOStatement']->nextRowset());
        } else {
            while ($item = $this->PDOData['PDOStatement']->fetch($this->fetchType)) {
                $item = $this->formatModelData($query, $item);
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * 游标查询用于逐条获取数据
     * @param Query  $query 查询对象
     * @param string $sql   sql指令
     * @param array  $bind  参数绑定
     * @return array
     */
    public function cursor($query, $sql = '', array $bind = [])
    {
        if (empty($sql)) {
            $sql = $this->buildSql($query, 'select');
        }
        $bind = array_merge($query->getOptions('bind', []), $bind);
        $master = $query->getOptions('master', false);
        $bindParam = !empty($query->getOptions('pointer')) ? true : in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);

        $this->getPDOStatement($sql, $bind, $master, $bindParam);
        while ($item = $this->PDOData['PDOStatement']->fetch($this->fetchType)) {
            $item = $this->formatModelData($query, $item);
            yield $item;
        }
    }

    /**
     * 执行操作 返回操作成功与否
     * @param Query  $query 查询对象
     * @param string $sql   sql指令
     * @param array  $bind  参数绑定
     * @return bool
     */
    public function execute($query, $sql = '', array $bind = [])
    {
        if (empty($sql)) {
            throw new RuntimeException('SQL Error');
        }
        $bind = array_merge($query->getOptions('bind', []), $bind);
        $master = true;
        $bindParam = !empty($query->getOptions('pointer')) ? true : in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);

        $this->getPDOStatement($sql, $bind, $master, $bindParam);
        $this->PDOData['numRows'] = $this->PDOData['PDOStatement']->rowCount();
        $msg = ['sql' => $this->getLastsql(), 'numRows' => $this->PDOData['numRows'], 'serverID' => $this->curLinkNum];
        $this->db->trigger('Execute', $msg);
        return $this->PDOData['numRows'];
    }

    /**
     * 获取记录数
     * @param mixed  $query 查询对象
     * @param string $field 字段名
     * @return int
     */
    public function count($query, $field = '*')
    {
        $query->limit(1);
        $query->fieldRaw('COUNT(' . $field . ') AS lfly_count');
        $result = $this->query($query);
        return $result[0] ? intval($result[0]['lfly_count']) : 0;
    }

    /**
     * 获取最大数
     * @param mixed  $query 查询对象
     * @param string $field 字段名
     * @return mixed
     */
    public function max($query, $field)
    {
        $query->limit(1);
        $query->fieldRaw('MAX(' . $field . ') AS lfly_max');
        $result = $this->query($query);
        return $result[0] ? $result[0]['lfly_max'] : null;
    }

    /**
     * 获取最小数
     * @param mixed  $query 查询对象
     * @param string $field 字段名
     * @return mixed
     */
    public function min($query, $field)
    {
        $query->limit(1);
        $query->fieldRaw('MIN(' . $field . ') AS lfly_min');
        $result = $this->query($query);
        return $result[0] ? $result[0]['lfly_min'] : null;
    }

    /**
     * 获取平均值
     * @param mixed  $query 查询对象
     * @param string $field 字段名
     * @return mixed
     */
    public function avg($query, $field)
    {
        $query->limit(1);
        $query->fieldRaw('AVG(' . $field . ') AS lfly_avg');
        $result = $this->query($query);
        return $result[0] ? $result[0]['lfly_avg'] : null;
    }

    /**
     * 获取总和
     * @param mixed  $query 查询对象
     * @param string $field 字段名
     * @return mixed
     */
    public function sum($query, $field)
    {
        $query->limit(1);
        $query->fieldRaw('SUM(' . $field . ') AS lfly_sum');
        $result = $this->query($query);
        return $result[0] ? $result[0]['lfly_sum'] : null;
    }

    /**
     * 获取单条记录
     * @param Query $query 查询对象
     * @return array
     */
    public function find($query)
    {
        $query->limit(1);
        $result = $this->query($query);
        return $result[0] ?? [];
    }

    /**
     * 获取全部记录
     * @param Query $query 查询对象
     * @return array
     */
    public function select($query)
    {
        $model = $query->getOptions('model');
        if (!empty($model) && method_exists($model, 'getListField')) {
            $listField = $model->getListField();
            if (!empty($listField)) {
                $query->field($listField);
            }
        }
        $result = $this->query($query);
        return $result;
    }

    /**
     * 插入记录
     * @param mixed $query 查询对象
     * @param array $data  数据
     * @param bool  $getId 是否获取插入id
     * @return mixed
     */
    public function insert($query, array $data = [], bool $getId = false)
    {
        $query->bind($data);
        $this->formatModelBindData($query);
        $status = $this->triggerModelEvent('onBeforeInsert', $query);
        if (!$status) {
            return $getId ? 0 : false;
        }
        $sql = $this->buildSql($query, 'insert');
        $result = !empty($sql) ? $this->execute($query, $sql) : 0;
        if ($getId) {
            if ($result > 0) {
                $insertId = $this->getLastInsertId($query->getOptions('pkid'));
                $query->setOption('insertId', $insertId);
                $this->triggerModelEvent('onAfterInsert', $query);
                return $insertId;
            } else {
                return 0;
            }
        }
        return $result > 0;
    }

    /**
     * 批量插入记录
     * @param mixed   $query    查询对象
     * @param mixed   $dataList 二维数组
     * @return int 插入条数
     */
    public function insertAll($query, array $dataList = [])
    {
        if (!is_array(reset($dataList))) {
            return 0;
        }
        $limit = intval($query->getOptions('limit', 1000));
        if (count($dataList) <= $limit) {
            $limit = 0;
        }

        if ($limit) {
            //使用事务分批写入
            $this->startTrans();
            try {
                $array = array_chunk($dataList, $limit, true);
                $count = 0;
                foreach ($array as $item) {
                    $query->bind($item, true);
                    $this->formatModelBindData($query);
                    $sql = $this->buildSql($query, 'insertAll');
                    $query->bind([], true);
                    $count += $this->execute($query, $sql);
                }
                $this->commit();
            } catch (Exception | Throwable $e) {
                $this->rollback();
                throw $e;
            }
            return $count;
        }

        $query->bind($dataList, true);
        $sql = $this->buildSql($query, 'insertAll');
        $query->bind([], true);
        return $this->execute($query, $sql);
    }

    /**
     * 更新记录
     * @param mixed $query 查询对象
     * @param array $data  数据
     * @return int 影响条数
     */
    public function update($query, array $data = [])
    {
        $query->bind($data);
        $this->formatModelBindData($query, true);
        $status = $this->triggerModelEvent('onBeforeUpdate', $query);
        if (!$status) {
            return 0;
        }
        $sql = $this->buildSql($query, 'update');
        $result = !empty($sql) ? $this->execute($query, $sql) : 0;
        if ($result) {
            $this->triggerModelEvent('onAfterUpdate', $query);
        }
        return $result;
    }

    /**
     * 删除记录
     * @param mixed $query 查询对象
     * @param bool  $clean 是否彻底删除，false为先尝试软删除，没有软删除设置再物理删除
     * @return int 影响条数
     */
    public function delete($query, bool $clean = false)
    {
        $status = $this->triggerModelEvent('onBeforeDelete', $query);
        if (!$status) {
            return 0;
        }
        $deleteField = $query->getOptions('deleteField', '');
        if (!$clean && !empty($deleteField)) {
            $result = $this->update($query, [$deleteField => time()]);
        } else {
            $sql = $this->buildSql($query, 'delete');
            $result = !empty($sql) ? $this->execute($query, $sql) : 0;
            if ($result) {
                $this->triggerModelEvent('onAfterDelete', $query);
            }
        }
        return $result;
    }

    /**
     * 得到某个字段的值
     * @param mixed  $query   查询对象
     * @param string $field   字段名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function value($query, $field, $default = null)
    {
        $result = $this->find($query);
        return $result[$field] ?? $default;
    }

    /**
     * 得到某个列的数组
     * @param mixed  $query  查询对象
     * @param string $column 字段名 *表示全部
     * @param string $key    索引
     * @return array
     */
    public function column($query, $column = '*', $key = null)
    {
        $result = $this->select($query);
        if ('*' == $column) {
            $column = null;
        }
        return !empty($result) ? array_column($result, $column, $key) : [];
    }

    /**
     * 是否存在表
     * @param mixed $query 查询对象
     * @return bool
     */
    public function exists($query)
    {
        $sql = $this->buildSql($query, 'exists');
        $result = $this->query($query, $sql);
        return count($result) > 0;
    }

    /**
     * 启动事务
     * @return void
     */
    public function startTrans()
    {
        $this->initConnect(true);
        ++$this->transTimes;
        $this->getPDO()->beginTransaction();
        $this->setLog('beginTransaction');
    }

    /**
     * 提交事务
     * @return void
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $this->getPDO()->commit();
            --$this->transTimes;
            $this->setLog('commit');
        }
    }

    /**
     * 事务回滚
     * @return void
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $this->getPDO()->rollBack();
            --$this->transTimes;
            $this->setLog('rollBack');
        }
    }

    /**
     * 关闭数据库
     * @return $this
     */
    public function close()
    {
        $this->PDOData = [];
        $this->curLinkNum = null;
        $this->links = [];
        return $this;
    }

    /**
     * 获取最近一次查询的sql语句
     * @return string
     */
    public function getLastSql()
    {
        return $this->getRealSql($this->PDOData['queryStr'], $this->PDOData['bind']);
    }

    /**
     * 获取当前数据库表结构更新时间
     * @return int
     */
    public function getChangeFieldTime()
    {
        return \Cache::get($this->getChangeFieldKey(), 0);
    }

    /**
     * 设置当前数据库表结构更新时间
     * @return int
     */
    public function setChangeFieldTime()
    {
        return \Cache::set($this->getChangeFieldKey(), time(), 0);
    }

    /**
     * 根据参数绑定组装最终的SQL语句
     * @param string $sql  带参数绑定的sql语句
     * @param array  $bind 参数绑定列表
     * @return string
     */
    protected function getRealSql($sql, array $bind = [])
    {
        foreach ($bind as $key => $val) {
            $value = is_array($val) ? $val[0] : $val;
            if (!is_int($value) && !is_float($value)) {
                $value = '\'' . addslashes($value) . '\'';
            }
            $sql = is_int($key) ?
                substr_replace($sql, $value, strpos($sql, '?'), 1) :
                substr_replace($sql, $value, strpos($sql, ':' . $key), strlen($key) + 1);
        }
        return trim($sql);
    }

    /**
     * 获取最近插入的ID
     * @param string $name 序列对象的名称
     * @return mixed
     */
    protected function getLastInsertId($name = null)
    {
        try {
            $insertId = $this->getPDO()->lastInsertId($name);
        } catch (\Exception $e) {
            $insertId = '';
        }
        return $insertId;
    }

    /**
     * 获取影响的记录数
     * @return int
     */
    protected function getNumRows()
    {
        return $this->PDOData['numRows'];
    }

    /**
     * 格式化运行事件
     * @param float $time 运行时间或开始时间
     * @param bool  $auto 自动计算
     * @return float
     */
    protected function formatTime($time, bool $auto = true)
    {
        if ($auto) {
            $time = microtime(true) - $time;
        }
        return number_format($time, 6);
    }

    /**
     * 格式化模型数据
     * @param mixed $query 查询对象
     * @param array $data  结果数据
     * @return array
     */
    protected function formatModelData($query, $data)
    {
        $join = $query->getOptions('joinModel', []);
        if (!empty($join)) {
            foreach ($join as $value) {
                if (method_exists($value['model'], 'formatJoinData')) {
                    $data = $value['model']->formatJoinData($data);
                }
            }
        }
        $model = $query->getOptions('model');
        if (!empty($model) && method_exists($model, 'formatData')) {
            $data = $model->formatData($data);
        }
        return $data;
    }

    /**
     * 格式化模型输入数据
     * @param mixed $query 查询对象
     * @param bool  $edit  是否编辑
     * @return void
     */
    protected function formatModelBindData($query, bool $edit = false)
    {
        $model = $query->getOptions('model');
        if (!empty($model) && method_exists($model, 'formatBindData')) {
            $data = $query->getOptions('bind', []);
            $data = $model->formatBindData($data, $edit);
            $query->bind($data, true);
        }
    }

    /**
     * 调用模型的触发事件
     * @param string $action 动作
     * @param mixed  $query  查询对象
     * @return mixed
     */
    protected function triggerModelEvent($action, $query)
    {
        $model = $query->getOptions('model');
        if (!empty($model) && method_exists($model, $action)) {
            return $model->$action($query);
        }
        return true;
    }

    /**
     * 参数值绑定 ['变量名1'=>'值1'] 对应命名占位符 或者 ['值1','值2'] 对应问号占位符
     * @param array $bind      要绑定的参数列表
     * @param bool  $bindParam 是否动态参数绑定
     * @return void 错误绑定将忽略
     */
    protected function bindData(array $bind = [], bool $bindParam = false)
    {
        $mothod = $bindParam ? 'bindParam' : 'bindValue';
        foreach ($bind as $key => $val) {
            $param = is_int($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                array_unshift($val, $param);
                $result = call_user_func_array([$this->PDOData['PDOStatement'], $mothod], $val);
            } else {
                $dataType = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $result = $this->PDOData['PDOStatement']->$mothod($param, $val, $dataType);
            }
        }
    }

    /**
     * 获取当前数据库表结构更新时间对应缓存字段
     * @return string
     */
    protected function getChangeFieldKey()
    {
        return 'db_' . $this->getConnectSign() . '_field_time';
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        $this->close();
    }
}
