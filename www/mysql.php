<?php
/* Проверка на обязательность включения */
if (!defined("LEGAL")) {
	header("HTTP/1.1 404 Not Found");
	exit();
}

/* Константы */
$MYSQL_SERVER = "localhost";

$MYSQL_MAIN_USER = "sqlcc";
$MYSQL_MAIN_PASSWORD = "sqlcc";

$MYSQL_MAIN_DB = "sqlcc";

$MYSQL_LIMITED_USER = "sqlcc_testing";
$MYSQL_LIMITED_PASSWORD = "sqlcc_testing";

//$MYSQL_DB_1 = 'sqlcc_testing_1';
//$MYSQL_DB_2 = 'sqlcc_testing_2';
/* Функции */
$db_master_connect = mysql_connect($MYSQL_SERVER, $MYSQL_MAIN_USER, $MYSQL_MAIN_PASSWORD);
mysql_select_db($MYSQL_MAIN_DB, $db_master_connect);
mysql_query("SET NAMES utf8", $db_master_connect);

?>