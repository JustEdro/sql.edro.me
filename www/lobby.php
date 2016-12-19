<?php 	
/* GLOBAL */
define("LEGAL", true);

/* Заголовки */
header("Content-Type: text/html; charset=utf-8");

/* БД */
require_once("mysql.php");

/* auth */
require_once("authorize.php");

// выход
if (array_key_exists('logout', $_GET)){
	setcookie('id', '');
	setcookie('password', '');
	$logged = false;
}


// редирект на главную
if (!$logged) {
	header('Location: /index.php');
	exit();
} 

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html" />
<title>Система SQLcc</title>
<link href="service/style.css" rel="stylesheet" type="text/css" />
<script type="application/javascript" src="service/jquery-1.4.2.min.js"></script>
</head>
<body>
<div id="body">
    <div id="topShadow"></div>
    <div id="bodyPannel">
        <h3>Прихожая</h3>
        <p>Система <b>SQLcc</b> позволяет вам проверить и улучшить свои знания языка запросов SQL.</p>
        <p><b>SQL</b> (ˈɛsˈkjuˈɛl; англ. Structured Query Language — «язык структурированных запросов») — универсальный компьютерный язык, применяемый для создания, модификации и управления данными в реляционных базах данных.</p>
        <p>Чтобы начать работу выберите тему и начните отвечать на вопросы.</p>
		<!--<p>Вы можете начать проверять свои знания на какой-либо конкретной базе данных, либо на всех базах, постепенно повышая сложность. </p>-->
		
		<div id="leftform" >
			<h4>Список тем</h4>
<?php
$sql = "SELECT * FROM `themes` WHERE `active` = 'Y'";
$query = mysql_query($sql);
if ($query){
    if (mysql_num_rows($query) > 0){
        while($result = mysql_fetch_array($query)) {
            echo '<p>'.$result['name'].'<a style="float: right" href="/blackboard.php?theme='.$result['id'].'">начать</a></p>';
        }
    } else {
        echo '<p>Нет активных тем</p>';
    }
}
?>

        <h4>Статистика по темам</h4>
<?php

if (mysql_num_rows($query) > 0){
    mysql_data_seek($query, 0);
	while($result = mysql_fetch_array($query)) {
		echo '<p>'.$result['name'].'<a style="float: right" href="/monitor.php?theme='.$result['id'].'">смотреть</a></p>';
	}
} else {
    echo '<p>Нет активных тем</p>';
}
?>
		</div>
		
<?php if($user_editor){ ?>
        <div id="rightform">
		    <h4>Редактирование</h4>
            <p>Задачи<a style="float: right" href="/taskeditor.php?act=tasks">начать</a></p>
            <p>Темы<a style="float: right" href="/taskeditor.php?act=themes">начать</a></p>
            <p>Описания баз данных<a style="float: right" href="/taskeditor.php?act=databases">начать</a></p>
        </div>
<?php } ?>
		<br class="spacer" />
        
    </div>
    <div id="bottomShadow"></div>
    <br class="spacer" />
</div>
<div id="footer">
    <p class="navigation"><?php echo $username ?> - <a href="?logout">Выход</a></p>
    <!--<p class="tworld"><a href="http://edro.me/" target="_blank">О нас</a><br />
        Оформление: <a href="http://www.templateworld.com/" target="_blank">Template World</a>--> </p>
</div>
</body>
</html>