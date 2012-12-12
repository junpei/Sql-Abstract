<?php

namespace Sql;

require_once 'Exception.php';

abstract class Sql
{
    protected $table;
    protected $alias;

    private $limit;
    private $offset;

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

    public function where() {
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
