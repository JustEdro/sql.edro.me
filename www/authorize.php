<?php
/* Проверка на обязательность включения */
if (!defined("LEGAL")) {
	header("HTTP/1.1 404 Not Found");
	exit();
}

// авторизуемся куками

if (!array_key_exists('id', $_COOKIE)) $_COOKIE['id'] = '';
if (!array_key_exists('password', $_COOKIE)) $_COOKIE['password'] = '';
if (($_COOKIE['id'] != '') && ($_COOKIE['password'] != '')){
	$query = mysql_query( "SELECT * FROM users WHERE id = ".intval($_COOKIE['id']).
						  " AND password = '".mysql_real_escape_string($_COOKIE['password'])."' LIMIT 1");
	$result = mysql_fetch_array($query);
	$row = mysql_num_rows($query);
	if ($row != 0){
		$logged = true;
		$username = $result['name'];
		$userid = $result['id'];
        $user_admin = $result['adm_priv'] == 'Y';
        $user_editor = $result['edit_priv'] == 'Y' || $user_admin;
	} else {
		$logged = false;
	}
} else {
    $logged = false;
}
?>