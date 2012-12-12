# SQL Abstract for PHP

## Examples

    require_once('Sql/Sql.php');
    $sql = new \Sql\Sql();
    print($sql->from('tables')->alias('t')->limit(8)->offset(0)->select());
    // SELECT * FROM tables AS t LIMIT 8 OFFSET 0
