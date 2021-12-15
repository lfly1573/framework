<?php

/**
 * 数据库连接接口
 */

namespace lfly\contract;

interface ConnectionHandlerInterface
{
    /**
     * 获取当前连接器类对应的Query类
     * @return string
     */
    public function getQueryClass();

    /**
     * 获取数据库的配置参数
     * @param string $config 配置名称
     * @return mixed
     */
    public function getConfig($config = '');

    /**
     * 设置当前的数据库Db对象
     * @param \lfly\Db $db
     * @return void
     */
    public function setDb($db);

    /**
     * 获取当前的数据库Db对象
     * @return \lfly\Db
     */
    public function getDb();

    /**
     * 连接数据库
     * @param array $config  连接参数
     * @param int   $linkNum 连接序号
     * @return mixed
     */
    public function connect(array $config = [], $linkNum = 0);

    /**
     * 关闭数据库
     * @return $this
     */
    public function close();

    /**
     * 执行查询 返回数据集
     * @param Query  $query 查询对象
     * @param string $sql   sql指令
     * @param array  $bind  参数绑定
     * @return array
     */
    public function query($query, $sql = '', array $bind = []);

    /**
     * 游标查询用于逐条获取数据
     * @param Query  $query 查询对象
     * @param string $sql   sql指令
     * @param array  $bind  参数绑定
     * @return Generator
     */
    public function cursor($query, $sql = '', array $bind = []);

    /**
     * 执行操作 返回操作成功与否
     * @param Query  $query 查询对象
     * @param string $sql   sql指令
     * @param array  $bind  参数绑定
     * @return array
     */
    public function execute($query, $sql = '', array $bind = []);

    /**
     * 获取记录数
     * @param mixed  $query 查询对象
     * @param string $field 字段名
     * @return int
     */
    public function count($query, $field = '*');

    /**
     * 查找单条记录
     * @param mixed $query 查询对象
     * @return array
     */
    public function find($query);

    /**
     * 查找记录
     * @param mixed $query 查询对象
     * @return array
     */
    public function select($query);

    /**
     * 插入记录
     * @param mixed $query 查询对象
     * @param array $data  数据
     * @return mixed
     */
    public function insert($query, array $data = [], bool $getId = false);

    /**
     * 批量插入记录
     * @param mixed   $query    查询对象
     * @param mixed   $dataList 二维数组
     * @return int 插入条数
     */
    public function insertAll($query, array $dataList = []);

    /**
     * 更新记录
     * @param mixed $query 查询对象
     * @param array $data  数据
     * @return int 影响条数
     */
    public function update($query, array $data = []);

    /**
     * 删除记录
     * @param mixed $query 查询对象
     * @return int 影响条数
     */
    public function delete($query);

    /**
     * 得到某个字段的值
     * @param mixed  $query   查询对象
     * @param string $field   字段名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function value($query, $field, $default = null);

    /**
     * 得到某个列的数组
     * @param mixed  $query  查询对象
     * @param string $column 字段名 *表示全部
     * @param string $key    索引
     * @return array
     */
    public function column($query, $column = '*', $key = null);

    /**
     * 是否存在表
     * @param mixed $query 查询对象
     * @return bool
     */
    public function exists($query);

    /**
     * 生成sql语句
     * @param  mixed  $query 查询对象
     * @param  string $type  操作
     * @return string
     */
    public function buildSql($query, $type = 'select');

    /**
     * 启动事务
     * @return void
     */
    public function startTrans();

    /**
     * 提交事务
     * @return void
     */
    public function commit();

    /**
     * 事务回滚
     * @return void
     */
    public function rollback();

    /**
     * 获取最近一次查询的sql语句
     * @return string
     */
    public function getLastSql();
}
