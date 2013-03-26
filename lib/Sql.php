<?php

require_once 'Sql/Join.php';
require_once 'Sql/Join/Inner.php';
require_once 'Sql/Join/Cross.php';
require_once 'Sql/Join/Left.php';
require_once 'Sql/Join/Right.php';
require_once 'Sql/Exception.php';

abstract class Sql
{
    protected $table;
    protected $alias;

    private $limit;
    private $offset;
    private $wheres = array();
    private $values = array();
    private $orders = array();
    private $groups = array();
    private $sets = array();
    private $columns = array();
    private $inserts = array();
    private $joins = array();
    private $join;
    private $distinct;

    private $allows = array(
          'MIN' => true
        , 'MAX' => true
        , 'COUNT' => true
    );

    public function __construct($settings = array()) {
        foreach ($settings as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    public function from() {
        if (func_num_args() === 1) {
            $this->table = func_get_arg(0);
            return $this;
        }

        return $this->table ?: get_class($this);
    }

    public function alias() {
        if (func_num_args() === 0) {
            return $this->alias;
        }

        $this->alias = func_get_arg(0);
        return $this;
    }

    public function column() {
        $function;
        $column;
        $alias;

        /**
         * arguments
         */
        if (func_num_args() === 2) {
            $function = func_get_arg(0);
            $column = func_get_arg(1);
        }

        else {
            $column = func_get_arg(0);
        }

        /**
         * alias
         */
        if (is_array($column)) {
            $alias = $column[1];
            $column = $column[0];
        }

        /**
         * column
         */
        if (preg_match('/\./', $column) === 0) {
            $column = "{$this->alias}.$column";
        }

        if (@$this->allows[$function]) {
            $column = "$function($column)";
        }

        if (isset($alias)) {
            $column .= " AS $alias";
        }

        $this->columns[] = $column;

        return $this;
    }

    public function select() {
        $select = ($this->distinct === true) ? 'SELECT DISTINCT' : 'SELECT';
        $columns = implode(', ', $this->columns) ?: '*';
        $table = $this->table ?: get_class($this);
        $alias = $this->alias ?: 'me';
        $sql = "$select $columns FROM $table AS $alias";

        /**
         * JOIN
         */
        if (count($this->joins) > 0) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        /**
         * WHERE
         */
        if (count($this->wheres) > 0) {
            $sql .= sprintf(' WHERE (%s)', implode(' AND ', $this->wheres));
        }

        /**
         * GROUP
         */
        if (count($this->groups) > 0) {
            $sql .= sprintf(' GROUP BY %s', implode(', ', $this->groups));
        }

        /**
         * ORDER
         */
        if (count($this->orders) > 0) {
            $sql .= sprintf(' ORDER BY %s', implode(', ', $this->orders));
        }

        /**
         * LIMIT
         */
        if ($this->limit > -1) {
            $sql .= " LIMIT {$this->limit}";
        }

        /**
         * OFFSET
         */
        if ($this->offset > -1) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    public function distinct() {
        $this->distinct = true;
        return $this;
    }

    public function count() {
        $table = $this->table ?: get_class($this);
        $alias = $this->alias ?: 'me';
        $sql = "SELECT COUNT(*) AS count FROM $table AS $alias";

        /**
         * JOIN
         */
        if (count($this->joins) > 0) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        /**
         * WHERE
         */
        if (count($this->wheres) > 0) {
            $sql .= sprintf(' WHERE (%s)', implode(' AND ', $this->wheres));
        }

        return $sql;
    }

    public function selectOne() {
        return $this->limit(1)->offset(0)->select();
    }

    public function where($column, $op, $value = null) {
        if (is_null($value)) {
            $value = $op;
            $op = '=';
        }

        if (is_array($value)) {
            $op = 'IN';
            $values = array();

            foreach ($value as $v) {
                if (is_int($v)) {
                    $values[] = $v;
                }

                else {
                    $values[] = '?';
                    $this->values[] = $v;
                }
            }

            $this->wheres[] = sprintf("($column $op (%s))", implode(', ', $values));
        }

        else if (is_null($value)) {
            $this->wheres[] = "($column IS NULL)";
        }

        else if (is_int($value)) {
            $this->wheres[] = "($column $op $value)";
        }

        else {
            $this->wheres[] = "($column $op ?)";
            $this->values[] = $value;
        }

        return $this;
    }

    public function wheres($wheres) {
        foreach ($wheres as $where) {
            call_user_func_array(array($this, 'where'), $where);
        }
        return $this;
    }

    public function orWheres($values) {
        $wheres = array();

        foreach ($values as $column => $value) {
            if (is_int($value)) {
                $wheres[] = "($column = $value)";
            }

            else {
                $wheres[] = "($column = ?)";
                $this->values[] = $value;
            }
        }

        $this->wheres[] = '(' . implode(' OR ', $wheres) . ')';

        return $this;
    }

    public function values() {
        return $this->values;
    }

    public function order($column, $order = null) {
        $order = (strtolower($order) === 'desc') ? 'DESC' : 'ASC';

        if (preg_match('/\./', $column) === 0) {
            $column = $this->alias() . ".$column";
        }

        $this->orders[] = "$column $order";
        return $this;
    }

    public function limit($n) {
        if (is_int($n) === false) {
            throw new \Sql\Exception('not int.');
        }
        $this->limit = $n;
        return $this;
    }

    public function offset($n) {
        if (is_int($n) === false) {
            throw new \Sql\Exception('not int.');
        }
        $this->offset = $n;
        return $this;
    }

    public function group($column) {
        if (preg_match('/\./', $column) === 0) {
            $column = $this->alias() . ".$column";
        }

        $this->groups[] = $column;
        return $this;
    }

    public function set() {
        if (func_num_args() === 2) {
            list($column, $value) = func_get_args();
            $this->sets[$column] = $value;
            return $this;
        }

        $sets = array();
        $values = array();

        foreach ($this->sets as $column => $value) {
            if (is_null($value)) {
                $sets[] = "$column = NULL";
            }

            else if (is_int($value)) {
                $sets[] = "$column = $value";
            }

            else {
                $sets[] = "$column = ?";
                $values[] = $value;
            }
        }

        $this->values = array_merge($values, $this->values);

        return sprintf('SET %s', implode(', ', $sets));
    }

    public function sets($sets) {
        foreach ($sets as $set) {
            call_user_func_array(array($this, 'set'), $set);
        }
        return $this;
    }

    public function update() {
        $sql = 'UPDATE ' . $this->from();

        /**
         * JOIN
         */
        if (count($this->joins) > 0) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        /**
         * SET
         */

        $sql .= ' ' . $this->set();

        /**
         * WHERE
         */
        if (count($this->wheres) > 0) {
            $sql .= sprintf(' WHERE (%s)', implode(' AND ', $this->wheres));
        }

        /**
         * LIMIT
         */
        if ($this->limit > -1) {
            $sql .= " LIMIT {$this->limit}";
        }

        return $sql;
    }

    public function delete() {
        $table = $this->table ?: get_class($this);
        $sql = "DELETE FROM $table";

        /**
         * WHERE
         */
        if (count($this->wheres) > 0) {
            $sql .= sprintf(' WHERE (%s)', implode(' AND ', $this->wheres));
        }

        /**
         * LIMIT
         */
        if ($this->limit > -1) {
            $sql .= " LIMIT {$this->limit}";
        }

        return $sql;
    }

    public function insert() {
        $keys = array();
        $values = array();

        /**
         * INTO
         */
        foreach ($this->sets as $key => $value) {
            if (is_null($value)) {
                $value = 'NULL';
            }

            else if (is_int($value) === false) {
                $this->values[] = $value;
                $value = '?';
            }

            $keys[] = $key;
            $values[] = $value;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)'
            , $this->table ?: get_class($this)
            , implode(', ', $keys)
            , implode(', ', $values)
        );

        return $sql;
    }

    public function inner() {
        $this->join = '\Sql\Join\Inner';
        return $this;
    }

    public function cross() {
        $this->join = '\Sql\Join\Cross';
        return $this;
    }

    public function left() {
        $this->join = '\Sql\Join\Left';
        return $this;
    }

    public function right() {
        $this->join = '\Sql\Join\Right';
        return $this;
    }

    public function join() {
        $class = $this->join ?: '\Sql\Join';
        $this->join = null;
        return new $class($this, func_get_arg(0));
    }

    public function joins() {
        $this->joins[] = func_get_arg(0);
        return $this;
    }
}
