# SQL Abstract for PHP

## Examples

    namespace Model;

    require_once('Sql/Sql.php');

    class Foo extends \Sql\Sql
    {
        protected $table = 'table_names';
        protected $alias = 'alias';
    }

    $sql = new \Model\Foo();

### FROM

    $sql->from('tables');

### AS

    $sql->alias('t');

### SELECT

    $sql->select();
    // SELECT * FROM tables AS t

### WHERE

    $sql->where('col0', 1)->select();
    // SELECT * FROM tables AS t WHERE col0 = 1

    $sql->where('col0', 'a')->select();
    $sql->values();
    // SELECT * FROM tables AS t WHERE col0 = ?
    // Array
    // (
    //   [0] => a
    // )

    $sql->where('col0', 'a')->where('col1', '<>', 1)->select();
    $sql->values();
    // SELECT * FROM tables AS t WHERE col0 = ? AND col1 <> 1
    // Array
    // (
    //   [0] => a
    // )

    $sql->wheres(array(array('col0', 'a'), array('col1', '<>', 1)))->select();
    $sql->values();
    // SELECT * FROM tables AS t WHERE col0 = ? AND col1 <> 1
    // Array
    // (
    //   [0] => a
    // )

### ORDER

    $sql->order('col0')->select();
    // SELECT * FROM tables AS t ORDER BY col0 ASC

    $sql->order('col0', 'desc')->select();
    // SELECT * FROM tables AS t ORDER BY col0 DESC


### LIMIT

    $sql->limit(8)->select();
    // SELECT * FROM tables AS t LIMIT 8

### OFFSET

    $sql->offset(8)->select();
    // SELECT * FROM tables AS t OFFSET 8
