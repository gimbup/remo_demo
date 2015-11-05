<?php
function connect()
{
        $db = mysql_connect('localhost', 'jayce', '880630');

        if (!$db) {
                die('Cannot connect: ' . mysql_error());
        }
        $db_name = 'InstructorDB';
        $use_db = mysql_select_db($db_name, $db);
        if (!$use_db) {
                die('Cannot use $dbname: ' . mysql_error());
        }

        return $db;
}
?>

