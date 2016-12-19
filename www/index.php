<?php 
/* GLOBAL */
define("LEGAL", true);

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

/* Заголовки */
header('Content-Type: text/html; charset=utf-8');

/* БД */
require_once("mysql.php");


$reg_error_login = false;
$reg_error_pass = false;
$reg_error_name = false;
$reg_error_spec = false;
$auth_error = false;

// выбираем действие
if (!array_key_exists('act', $_POST)) $_POST['act'] = '';
switch($_POST['act']){
	case "login":
        // логинимся
        $auth_error_login = !mb_ereg_match("^[a-zA-Z0-9_\-]{3,30}$", $_POST['name']);
        $auth_error_pass =  !mb_ereg_match("^[a-zA-Z0-9_\-]{3,30}$", $_POST['password']);

        if (!$auth_error_login && !$auth_error_pass){
            $query = mysql_query( "SELECT * FROM users WHERE login = '".mysql_real_escape_string($_POST['name'])."' ".
                                    "AND password = '".md5($_POST['password'])."' LIMIT 1");
            $result = mysql_fetch_array($query);
            $row = mysql_num_rows($query);
            if ($row != 0){
                if (!array_key_exists('keeponline', $_POST)) $_POST['keeponline'] = '';
                if ($_POST['keeponline'] == "true"){
                    setcookie("id", $result['id'], time() + 60*60*24*365);
                    setcookie("password", $result['password'], time() + 60*60*24*365);
                } else {
                    setcookie("id", $result['id']);
                    setcookie("password", $result['password']);
                }
                $logged = true;
                $username = $result['name'];
                $userid = $result['id'];
                //echo $result['id'].", ".$result['name'].", ".$result['password'];
            } else {
                $auth_error = true;
                $logged = false;
            }
        } else {
            $auth_error = true;
            $logged = false;
        }
	break;
	
	
	
	case "register":
        // регаемся
        $reg_login = $_POST['login'];
        $reg_pass1 = $_POST['password'];
        $reg_pass2 = $_POST['password2'];
        $reg_name = mysql_real_escape_string(htmlspecialchars(trim($_POST['name'])));
        $reg_spec = mysql_real_escape_string(htmlspecialchars(trim($_POST['spec'])));

        $reg_error_login = !mb_ereg_match("^[a-zA-Z0-9_\-]{3,30}$", $reg_login);
        $reg_error_pass =  !mb_ereg_match("^[a-zA-Z0-9_\-]{3,30}$", $reg_pass1) || ($reg_pass1 != $reg_pass2);
        $reg_error_name = (mb_strlen($reg_name, "utf-8") > 80) || (mb_strlen($reg_name, "utf-8") == 0);
        $reg_error_spec = (mb_strlen($reg_spec, "utf-8") > 20) || (mb_strlen($reg_spec, "utf-8") == 0);

        if (!$reg_error_login) {
            // проверяем не занят ли логин
            $query = mysql_query("SELECT `login` FROM users WHERE login = '".$reg_login."' LIMIT 1");
            $row = mysql_num_rows($query);
            if ($row != 0) {
                $reg_error_login_used = true;
            } else {
                $reg_error_login_used = false;
            }
        } else {
             $reg_error_login_used = false;
        }
        $reg_error = $reg_error_login || $reg_error_pass || $reg_error_name || $reg_error_spec || $reg_error_login_used;
        if (!$reg_error){
            $query = mysql_query(	"INSERT INTO users (id, login, password, name, spec) ".
                                    "VALUES ('', '".$reg_login."', '".md5($reg_pass1)."', '".$reg_name."', '".$reg_spec."')"	);

            // логинимся
            $query = mysql_query( 	"SELECT * FROM users WHERE login = '".$reg_login."' ".
                                    "AND password = '".md5($reg_pass1)."' LIMIT 1"   );
            $result = mysql_fetch_array($query);
            $row = mysql_num_rows($query);

            setcookie("id", $result['id']);
            setcookie("password", $result['password']);

            $logged = true;
            $username = $result['name'];
            $userid = $result['id'];
            $user_admin = $result['adm_priv'] == 'Y';
            $user_editor = $result['edit_priv'] == 'Y';
        } else {
            // не зарегались
            $logged = false;
        }
	
	break;
	
	
	default:
	    /* auth */
        $logged = false;
        require_once("authorize.php");
	break;
}

// выход
if (array_key_exists('logout', $_GET)){
	setcookie("id", "");
	setcookie("password", "");
	$logged = false;
}

mysql_close();

if ($logged) {
	//header('Location: /lobby.php');
    header('Location: /lobby.php');
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
<script type="application/javascript">
function center(){
    $('#body_center').css('position', 'absolute')
        .css('left', ($(this).width() - $('#body_center').width()) / 2)
        .css('top', ($(this).height() - $('#topShadow').height() - $('#bodyPannel').height() - $('#bottomShadow').height()) / 2);
}
$(document).ready(center);
$(window).resize(center);
</script>
</head>
<body>
<div id="body_center">
    <div id="topShadow"></div>
    <div id="bodyPannel">
        <form method="post" action="/" name="register" id="leftform" class="vf">
            <input type="hidden" name="act" value="register"/>
            <h2>Зарегистрируйтесь</h2>
            <?php if($reg_error_login){?><p>Разрешены символы: a-z, A-Z, 0-9, _, - <br/>Длина: 3-30 знаков</p><?php }?>
            <label>логин<input name="login" type="text" /></label>
            <br class="spacer" />
            <?php if($reg_error_pass){?><p>Разрешены символы: a-z, A-Z, 0-9, _, - <br/>Длина: 3-30 знаков, пароли должны совпадать</p><?php }?>
            <label>пароль<input name="password" type="password" /></label>
            <br class="spacer" />
            <label>пароль<input name="password2" type="password" id="password2" /></label>
            <br class="spacer" />
            <?php if($reg_error_name){?><p>Имя слишком длинное либо отсутствет</p><?php }?>
            <label>фио<input name="name" type="text" id="fio" /></label>
            <br class="spacer" />
            <?php if($reg_error_spec){?><p>Название слишком длинное либо отсутствет</p><?php }?>
            <label>группа <input name="spec" type="text" id="group" /></label>
            <br class="spacer" />
            <input name="register" type="submit" id="register" value="зарегистрироваться" title="зарегистрироваться" class="submit" />
        </form>
        <form method="post" action="/" name="login" id="rightform" class="vf">
            <input type="hidden" name="act" value="login"/>
            <h2>Войдите на сайт</h2>
            <?php if($auth_error){?><p>Неверное имя или пароль</p><?php }?>
            <label>имя<input name="name" type="text" id="name" /></label>
            <br class="spacer" />
            <label>пароль<input name="password" type="password" /></label>
            <br class="spacer" />
            <label>оставаться в системе
                <input name="keeponline" type="checkbox" value="true" class="check" />
            </label>
            <br />
            <input name="login" type="submit" value="войти" title="войти" class="submit" />
        </form>
    </div>
    <div id="bottomShadow"></div>
</div>
</body>
</html>
