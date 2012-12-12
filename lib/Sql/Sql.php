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

    public function select() {
        $columns = '*';
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
}
