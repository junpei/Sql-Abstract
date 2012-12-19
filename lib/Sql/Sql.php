<?php

namespace Sql;

require_once 'Exception.php';

abstract class Sql
{
    protected $table;
    protected $alias;

    private $limit;
    private $offset;
    private $wheres = array();
    private $values = array();
    private $orders = array();
    private $sets = array();
    private $columns = array();
    private $inserts = array();

    public function __construct() {
        return $this;
    }

    public function from($table) {
        $this->table = $table;
        return $this;
    }

    public function alias($alias) {
        $this->alias = $alias;
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

        /**
         * function
         */
        $allows = array(
            'MIN' => true,
            'MAX' => true,
            'COUNT' => true,
        );

        if (@$allows[$function]) {
            $column = "$function($column)";
        }

        if (isset($alias)) {
            $column .= " AS $alias";
        }

        $this->columns[] = $column;

        return $this;
    }

    public function select() {
        $columns = implode(', ', $this->columns) ?: '*';
        $table = $this->table ?: get_class($this);
        $alias = $this->alias ?: 'me';
        $sql = "SELECT $columns FROM $table AS $alias";

        /**
         * WHERE
         */
        if (count($this->wheres) > 0) {
            $sql .= sprintf(' WHERE (%s)', implode(' AND ', $this->wheres));
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

        $this->wheres[] = implode(' OR ', $wheres);

        return $this;
    }

    public function values() {
        return $this->values;
    }

    public function order($column, $order = null) {
        $order = (strtolower($order) === 'desc') ? 'DESC' : 'ASC';
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

    public function set($column, $value) {
        $this->sets[$column] = $value;
        return $this;
    }

    public function sets($sets) {
        foreach ($sets as $set) {
            call_user_func_array(array($this, 'set'), $set);
        }
        return $this;
    }

    public function update() {
        $table = $this->table ?: get_class($this);
        $sql = "UPDATE $table";

        /**
         * SET
         */
        $sets = array();

        foreach ($this->sets as $column => $value) {
            if (is_null($value)) {
                $sets[] = "$column = NULL";
            }

            else if (is_int($value)) {
                $sets[] = "$column = $value";
            }

            else {
                $sets[] = "$column = ?";
                $this->values[] = $value;
            }
        }

        if (count($sets) > 0) {
            $sql .= sprintf(' SET %s', implode(', ', $sets));
        }

        /**
         * WHERE
         */
        if (count($this->wheres) > 0) {
            $sql .= sprintf(' WHERE (%s)', implode(' AND ', $this->wheres));
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

        return $sql;
    }

    public function insert() {
        $keys = array();
        $values = array();

        /**
         * SET
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
}
