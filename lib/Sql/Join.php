<?php

namespace Sql;

class Join
{
    private $left;
    private $right;

    protected $inner;
    protected $cross;
    protected $outer;

    public function __construct(\Sql $left, \Sql $right) {
        $this->left = $left;
        $this->right = $right;
        return $this;
    }

    public function on($from, $to) {
        $sql = 'JOIN';

        if (@$this->inner) {
            $sql = "INNER $sql";
        }

        if (@$this->cross) {
            $sql = "CROSS $sql";
        }

        else if (@$this->outer === 'LEFT') {
            $sql = "LEFT OUTER $sql";
        }

        else if (@$this->outer === 'RIGHT') {
            $sql = "RIGHT OUTER $sql";
        }

        if (preg_match('/\./', $from) === 0) {
            $from = "{$this->left->alias()}.$from";
        }

        if (preg_match('/\./', $to) === 0) {
            $to = "{$this->right->alias()}.$to";
        }

        $sql .= sprintf(
            ' %s AS %s ON (%s = %s)'
            , $this->right->from()
            , $this->right->alias()
            , $from
            , $to
        );

        return $this->left->joins($sql);
    }
}
