<?php

namespace Sql;

class Join
{
    private $left;
    private $right;

    protected $inner;
    protected $cross;
    protected $outer;

    public function __construct(\Sql\Sql $left, \Sql\Sql $right) {
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

        $sql .= sprintf(
            ' %s AS %s ON (%s.%s = %s.%s)'
            , $this->right->from()
            , $this->right->alias()
            , $this->left->alias()
            , $from
            , $this->right->alias()
            , $to
        );

        return $this->left->joins($sql);
    }
}
