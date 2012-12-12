# SQL Abstract for PHP

## Examples

    require_once('Sql/Sql.php');
    $sql = new \Sql\Sql();

    print($sql->from('tables')->alias('t')->limit(8)->offset(0)->select());
    // SELECT * FROM tables AS t LIMIT 8 OFFSET 0

    print($sql->from('tables')->alias('t')->order('col0')->select());
    // SELECT * FROM tables AS t ORDER BY col0 ASC

    print($sql->from('tables')->alias('t')->order('col0', 'desc')->select());
    // SELECT * FROM tables AS t ORDER BY col0 DESC

    print($sql->from('tables')->where('col0', 1)->select());
    // SELECT * FROM tables AS me WHERE col0 = 1

    print($sql->from('tables')->where('col0', 'a')->select());
    print_r($sql->values());
    // SELECT * FROM tables AS me WHERE col0 = ?
    // Array
    // (
    //   [0] => a
    // )

    print($sql->from('tables')->where('col0', '<>', 1)->select());
    // SELECT * FROM tables AS me WHERE col0 <> 1

    print($sql->from('tables')->where('col0', array('a', 'b', 1))->select());
    print_r($sql->values());
    // SELECT * FROM tables AS me WHERE col0 IN (?, ?, 1)
    // Array
    // (
    //   [0] => a
    //   [1] => b
    // )

    print($sql->from('tables')->wheres(array(array('col0', 1), array('col1', 'a')))->select());
    print_r($sql->values());
    // SELECT * FROM tables AS me WHERE col0 = 1 AND col1 = ?
    // Array
    // (
    //   [0] => a
    // )
