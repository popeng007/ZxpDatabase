<?php
/**
 * 数据库常用操作类，插入、更新、删除、查询，批量插入、批量更新
 * compact, lightweight, in common use. PHP, MySQL, SQLite, PDO, database
 * functions: insert, update, delete, select, insertBatch, updateBatch
 * PHP 5 >= 5.4
 *
 * @author      zhangxianpeng <https://github.com/popeng007>
 * @version     1.1.0 (last revision: Nov, 27, 2017)
 * @copyright   (c) 2017 zhangxianpeng
 * @license     http://www.gnu.org/licenses/lgpl-3.0.txt
 *              GNU LESSER GENERAL PUBLIC LICENSE
 */
class ZxpPDO
{
    /**
     * pdo对象
     *
     * @var object
     */
    public $pdo = null;

    /**
     * 测试模式
     *
     * @var boolean
     */
    public $isDebug = false;

    /**
     * 构造函数
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array  $driver_options
     */
    public function __construct(
        $dsn,
        $username = '',
        $password = '',
        $driver_options = []
    ) {
        try {
            $this->pdo = new PDO($dsn, $username, $password, $driver_options);
            $this->pdo->query('set names "utf8"');
        } catch (PDOException $e) {
            echo 'Error : ' . $e->getMessage() . '<br />';
        }
    }

    /**
     * 插入单条记录
     * insert a record to database.
     *
     * @param string $table
     * @param array $args
     * @return int
     */
    public function insert($table, $args)
    {
        $sql = $this->genInsertSql($table, $args);
        $vals = array_values($args);
        $this->debug($sql, $vals);
        $this->execute($sql, $vals);
        return $this->pdo->lastInsertId();
    }

    /**
     * 插入一批(多条)记录
     * insert a batch of records to database.
     *
     * @param string $table
     * @param array $data
     * @return array
     */
    public function insertBatch($table, $data)
    {
        if (empty($data)) return [];
        $insertIds = [];
        $sql = $this->genInsertSql($table, $data[0]);
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare($sql);
            foreach ($data as $i => $args) {
                $vals = array_values($args);
                $this->debug($sql, $vals);
                $stmt->execute($vals);
                $insertIds[$i] = $this->pdo->lastInsertId();
            }
            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollback();
            echo 'Error : ' . $e->getMessage();
        }
        return $insertIds;
    }

    /**
     * 更新单条记录
     * update a record
     *
     * @param string $table
     * @param array $args
     * @param array $where
     */
    public function update($table, $args, $where)
    {
        $sql = $this->genUpdateSql($table, $args);
        $genWhere = $this->genWhere($where);
        $sql .= $genWhere['where'];
        $vals = array_values($args);
        $vals = array_merge($vals, array_values($where));
        $this->debug($sql, $args);
        $this->execute($sql, $vals);
    }

    /**
     * 更新一批(多条)记录
     * update a batch of records
     *
     * @param string $table
     * @param array $data
     */
    public function updateBatch($table, $data)
    {
        if (empty($data)) return;
        $sql = $this->genUpdateSql($table, $data[0][0]);
        if (!empty($data[0][1])) {
            $genWhere = $this->genWhere($data[0][1]);
            $sql .= $genWhere['where'];
        }
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare($sql);
            foreach ($data as $args) {
                $vals = array_values($args[0]);
                if (!empty($args[1])) {
                    $vals = array_merge($vals, array_values($args[1]));
                }
                $this->debug($sql, $vals);
                $stmt->execute($vals);
            }
            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollback();
            echo 'Error : ' . $e->getMessage();
        }
    }

    /**
     * 查询记录
     * select record from database.
     *
     * @param string $table
     * @param mixed $columns
     * @param array $where
     * @param array $more
     * @return array
     */
    public function select($table, $columns, $where = [], $more = [])
    {
        $sql = 'select ';
        if (is_string($columns)) {
            $sql .= $columns;
        } elseif (is_array($columns)) {
            $cols = [];
            foreach ($columns as $col) {
                $col = trim($col);
                if (strrpos($col, '`') === false) $col = "`$col`";
                $cols[] = $col;
            }
            $cols = implode(', ', $cols);
            $sql .= $cols;
        }
        $sql .= " from `$table`";

        $genWhere = $this->genWhere($where);
        $sql .= $genWhere['where'];
        $vals = $genWhere['vals'];
        $sql .= $this->genMore($more);
        $this->debug($sql, $vals);

        return $this->query($sql, $vals);
    }

    /**
     * 执行 sql 语句
     *
     * @param string $sql
     * @param array $vals
     * @return array
     */
    public function query($sql, $vals = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($vals);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo 'Error : ' . $e->getMessage();
        }
    }

    /**
     * 删除数据
     * delete records
     *
     * @param string $table
     * @param mixed $where
     */
    public function delete($table, $where)
    {
        $sql = "delete from `$table`";
        if ($where == 'all') {
            $this->debug($sql, []);
            $this->execute($sql, []);
        } elseif (is_array($where) && !empty($where)) {
            $genWhere = $this->genWhere($where);
            $sql .= $genWhere['where'];
            $vals = $genWhere['vals'];
            $this->debug($sql, $vals);
            $this->execute($sql, $vals);
        } elseif ($this->isDebug) {
            echo "delete error : ";
            echo "the parameter 2 must be type of Array or 'all' <br />";
        }
    }

    /**
     * 执行一条 SQL 语句，并返回受影响的行数
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param string $sql
     * @return int
     */
    public function exec($sql)
    {
        $this->debug($sql, []);
        return $this->pdo->exec($sql);
    }

    /**
     * 生成 insert sql 语句
     * generate a sql statement for insert.
     *
     * @param string $table
     * @param array $args
     * @return string
     */
    private function genInsertSql($table, $args)
    {
        $cols = [];
        $pres = [];
        foreach ($args as $key => $value) {
            $cols[] = "`$key`";
            $pres[] = '?';
        }
        $cols = implode(', ', $cols);
        $pres = implode(', ', $pres);
        $sql = "insert into `$table` ($cols) values ($pres)";
        return $sql;
    }

    /**
     * 生成 update sql 语句
     * generate a sql statement for update.
     *
     * @param string $table
     * @param array $args
     * @return string
     */
    private function genUpdateSql($table, $args)
    {
        $sql = "update `$table` set ";
        $pres = [];
        foreach ($args as $key => $value) {
            $pres[] = "`$key` = ?";
        }
        $pres = implode(', ', $pres);
        $sql .= $pres;
        return $sql;
    }

    /**
     * 生成 where 条件语句
     * generate a where clause to filter records.
     *
     * @param array $where
     * @return array
     */
    private function genWhere($where)
    {
        $clause = '';
        $pres = [];
        $vals = [];
        $operator = '';
        foreach ($where as $column => $set) {
            $pres = [];
            $clauseTemp = '';
            $column = $this->wrapColumn($column);
            if (!is_array($set)) {
                $clause .= " $column = ? and";
                $vals[] = $set;
                continue;
            }
            $operator = strtolower($set[0]);
            if (isset($set['prefix'])) $column = $set['prefix'] . $column;
            switch ($operator) {
                case '=':
                case '!=':
                case '<>':
                case 'like':
                    $val = $set[1];
                    $clauseTemp = " $column $operator ?";
                    $vals[] = $val;
                    if (!empty($set['or'])) {
                        foreach ($set['or'] as $val) {
                            $clauseTemp .= " or $column $operator ?";
                            $vals[] = $val;
                        }
                        $clauseTemp = ' (' . trim($clauseTemp) . ')';
                    }
                    $clause .= $clauseTemp;
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $val = $set[1];
                    $clauseTemp = " $column $operator ?";
                    $vals[] = $val;
                    $extra = $this->clauseExtra($column, $set, 'or', $vals);
                    $extra .= $this->clauseExtra($column, $set, 'and', $vals);
                    if (!empty($extra)) {
                        $clauseTemp = ' (' . $clauseTemp . $extra . ')';
                    }
                    $clause .= $clauseTemp;
                    break;
                case 'between':
                case 'not between':
                    $clause .= " $column $operator ? and ?";
                    $vals[] = $set[1];
                    $vals[] = $set[2];
                    break;
                case 'in':
                case 'not in':
                    foreach ($set[1] as $val) {
                        $pres[] = '?';
                        $vals[] = $val;
                    }
                    $pres = implode(', ', $pres);
                    $clause .= " $column $operator ($pres)";
                    break;
                default:
                    break;
            }
            $joint = isset($set['joint']) ? ' ' . $set['joint'] : ' and';
            $suffix = isset($set['suffix']) ? $set['suffix'] : '';
            $clause .= $suffix . $joint;
        }
        if (!empty($clause)) {
            $clause = ' where' . substr($clause, 0, -4);
        }
        $where = [
            'where' => $clause,
            'vals' => $vals
        ];
        return $where;
    }

    /**
     * 用 ` 包裹字段名
     * wrap column with `
     *
     * @param string $column
     * @return string
     */
    private function wrapColumn($column)
    {
        if (strpos($column, '`') === false && strpos($column, '(') === false) {
            $column = "`$column`";
        }
        return $column;
    }

    /**
     * 额外的条件
     *
     * @param string $column
     * @param array $set
     * @param string $logic
     * @param array &$vals
     * @return string
     */
    private function clauseExtra($column, $set, $logic, &$vals)
    {
        $clauseExtra = '';
        if (!empty($set[$logic]) && count($set[$logic]) == 2) {
            $operator = $set[$logic][0];
            $val = $set[$logic][1];
            if (in_array($operator, ['>', '>=', '<', '<='])) {
                $clauseExtra = " $logic $column $operator ?";
                $vals[] = $val;
            }
        }
        return $clauseExtra;
    }

    /**
     * 生成更多的查询条件语句，如 group by, order by, limit
     * generate more sql statement, like 'group by', 'order by', 'limit'
     *
     * @param array $more
     * @return string
     */
    private function genMore($more)
    {
        $sql = '';
        $more = array_change_key_case($more, CASE_LOWER);
        if (isset($more['group by'])) {
            $sql .= ' group by `' . $more['group by'] . '`';
        }
        if (isset($more['order by'])) {
            $sql .= ' order by ' . $more['order by'];
        }
        if (isset($more['limit'])) {
            $sql .= ' limit ' . implode(',', $more['limit']);
        }
        return $sql;
    }

    /**
     * 执行SQL语句
     *
     * @param string $sql
     * @param array $vals
     * @return boolean
     */
    private function execute($sql, $vals)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($vals);
        } catch (PDOException $e) {
            echo 'Error : ' . $e->getMessage();
        }
        return false;
    }

    /**
     * 测试，输入 sql 语句和参数
     *
     * @param string $sql
     * @param array $vals
     */
    private function debug($sql, $vals)
    {
        if (!($this->isDebug)) return;
        echo $sql . '<br />';
        echo json_encode($vals);
        echo '<br />';
    }
}
