<?php
/* GLOBAL */
define("LEGAL", true);

/* TIMER */
$start_time = microtime();
$start_array = explode(" ",$start_time);
$start_time = $start_array[1] + $start_array[0];


/* Заголовки */
header("Content-Type: text/html; charset=utf-8");

/* БД */
require_once("mysql.php");

/* auth */
require_once("authorize.php");

// выход
if (array_key_exists('logout', $_GET)){
	setcookie("id", "");
	setcookie("password", "");
	$logged = false;
}

// редирект на главную
if (!$logged || !$user_admin && !$user_editor) {
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
<script type="application/javascript" src="service/jquery-ui-1.8.6.custom.min.js"></script>
<script type="application/javascript" src="service/tables.js"></script>
<script type="application/javascript">

$(document).ready(function(){
   
    $('.dragzone').sortable({
        axis: 'y',
        items: '.cell',
        opacity: 0.9,
        //revert: true,
        tolerance: 'pointer',
        start: function (event, ui){
            ui.item.css('background-color', '#fefaf6');
        },
        stop: function(event, ui) {
            ui.item.css('background-color', '#ffffff');
            $('.dragzone').click(); // loss of focus fix
	    }
    });
	$('.dragzone').disableSelection();

    $('#save_order').submit(function(){
        $('#save_order_neworder').val($(".dragzone").sortable('serialize'));
    });
    $('.confirm').click(function(){
        return confirm('Вы уверены?');
    });

    var querySent = false;
    $('#check_query').click(function(){
        if (!querySent){
            querySent = true;

            $.post('/sql.php', {query: $("#query").val(), database: $("#database").val(), sandbox: "true"}, function(json){
               
                $('#response').html('');
                switch(json.status){
                    case 0: $('#response').append('Запрос выполнен без ошибок за: '+ json.gentime + ' сек.'); break;
                    case 1: $('#response').append('Ошибка авторизации на сервере'); break;
                    case 2: $('#response').append('База не найдена'); break;
                    case 3: $('#response').append('Ошибка в запросе:<br/><div class="code">' + json.error+'</div>'); break;
                    case 7: $('#response').append('Внутренняя ошибка базы данных'); break;
                    case 8: $('#response').append('В запросе содержатся выражения, запрещенные в целях безопасности:'+
                                                    '<br/><div class="code">' + json.error+'</div>'); break;
                    case 9: $('#response').append('Необрабатываемая ошибка'); break;
                }
                $('#response').append('<br/>');

                if (json.status == 0){
                    $('#response').append(serializeTable(json.arr_testing_1, 'Ответ сервера 1'));
                    $('#response').append(serializeTable(json.arr_testing_2, 'Ответ сервера 2')); 
                }

                colorize();

                querySent = false;
            }, 'json');
        }
    });

    // fade-out notifications
    function fadeout(){
        $('.notify_bad, .notify_good').animate({opacity: 0, height: 0}, "slow");
    }
    setTimeout(fadeout, 2000);
});
 
</script>
</head>
<body>

<div id="body">
    <div id="topShadow"></div>
    <div id="bodyPannel">

<?php
// выводим обратную ссылку
if (array_key_exists('from', $_GET)){
    if ($_GET['from'] == 'bb'){
        $theme = intval($_GET['theme']);
        $task = intval($_GET['task']);
        echo '<p class="bnav"><a href="blackboard.php?theme='.$theme.'&task='.$task.'">Вернуться к решению задач</a>';
    } else {
        $_GET['from'] = '';
    }
} else {
    $_GET['from'] = '';
}



// выбор действия
if (!array_key_exists('act', $_GET)) $_GET['act'] = '';
switch ($_GET['act']){
    case 'databases':
        echo '<p class="bnav">Центр управления - Описания баз данных | <a href="taskeditor.php">назад</a>'.
                ' - <a href="lobby.php">на главную</a></p>';
        // сохраняем изменения
        if(array_key_exists('save', $_POST)){
            $saved_db = intval($_POST['db']);
            $new_content = htmlentities(trim($_POST['content']), ENT_QUOTES, 'UTF-8');
            $dbname = trim($_POST['dbname']);
            $db1 = trim($_POST['db_name1']);
            $db2 = trim($_POST['db_name2']);
            if ($new_content != '' && $dbname != '' && $db1 != '' && $db1 != ''){
                $query = mysql_query(   "UPDATE `databases` SET `description` = '".
                                        mysql_real_escape_string($new_content)."', ".
                                        "`db_1` = '".mysql_real_escape_string($db1)."', ".
                                        "`db_2` = '".mysql_real_escape_string($db2)."', ".
                                        "`name` = '".mysql_real_escape_string($dbname)."' ".
                                        'WHERE `id` = '.$saved_db.' LIMIT 1;'   );
                if ($query){
                    if (mysql_affected_rows() != 0){
                        echo '<div class="notify_good">Изменения сохранены</div>';
                    }
                } else {
                    echo '<div class="notify_bad">Изменения не сохранены из-за неизвестной ошибки</div>';
                }
            } else {
                echo '<div class="notify_bad">Нельзя задать пустое поле</div>';
            }
        }

        // создаем базу
        if(array_key_exists('save_new', $_POST)){
            $new_content = htmlentities(trim($_POST['content']), ENT_QUOTES, 'UTF-8');
            $dbname = trim($_POST['dbname']);
            $db1 = trim($_POST['db_name1']);
            $db2 = trim($_POST['db_name2']);
            if ($new_content != '' && $dbname != '' && $db1 != '' && $db1 != ''){
                $query = mysql_query(   "INSERT INTO `databases` (`id`, `name`, `description`, `db_1`, `db_2`) ".
                                        "VALUES ( '', ".
                                        "'".mysql_real_escape_string($dbname)."', ".
                                        "'".mysql_real_escape_string($new_content)."', ".
                                        "'".mysql_real_escape_string($db1)."', ".
                                        "'".mysql_real_escape_string($db2)."') " );
                if ($query){
                    if (mysql_affected_rows() != 0){
                        echo '<div class="notify_good">Новая тестовая база добавлена</div>';
                    }
                } else {
                    echo '<div class="notify_bad">Изменения не сохранены из-за неизвестной ошибки</div>';
                }
            } else {
                echo '<div class="notify_bad">Нельзя задать пустое поле</div>';
            }
        }

        // удаляем базу
        if(array_key_exists('delete', $_GET)){
            $database = intval($_GET['delete']);
            // проверяем возможность удаления
            $query = mysql_query(   'SELECT COUNT( * ) AS `count` FROM `tasks` WHERE `database` = '. $database  );
            if ($query){
                $result = mysql_fetch_array($query);
                if ($result['count'] == 0){
                    $query = mysql_query(   'DELETE FROM `databases` WHERE `id` = '. $database  );
                    if (mysql_affected_rows() != 0){
                        echo '<div class="notify_good">База удалена</div>';
                    }
                } else {
                    echo '<div class="notify_bad">Для данной базы существуют задачи, для начала удалите их</div>';
                }
            } else {
                echo '<div class="notify_bad">База не удалена из-за неизвестной ошибки</div>';
            }

        }

        echo '<p>Используйте [ и ] для обозначения слова, доступного в автоподстановке, '.
                    '{ и } для выделения участков текста, для которых требутся моноширинное начертание.</p>';
        echo '<p>В поля База 1 и База 2 требутеся ввести реальное имя баз на сервере. Пользователь сможет просматривать значения из первой базы.</p>';

        // нужно ли добавить новую задачу?
        if (array_key_exists('add', $_GET)){
            // показываем форму для новой задачи
            echo '<div class="cellcontainer">';
            echo '<div class="cell"><h4>Новая тестовая база данных</h4>';
            if ($_GET['from']){
                echo '<form method="POST" action="taskeditor.php?act=databases&theme='.$theme.
                        '&task='.$task.'&from=bb">';
            } else {
                echo '<form method="POST" action="taskeditor.php?act=databases">';
            }
            echo '<div id="hidden"><label>Имя <input type="text" name="dbname" value=""/></label></div>';
            echo '  <input type="hidden" name="save_new" value="true"/>';
            echo '  <textarea name="content" rows="12" cols="80"></textarea>';
            echo '  <label>База 1 <input type="text" name="db_name1" value=""/></label>';
            echo '  <label>База 2 <input type="text" name="db_name2" value=""/></label>';
            echo '  <input name="submit" type="submit" value="Сохранить изменения" />';
            echo '</form></div>';

            echo '</div>';
        } else {
            // показваем список или одну базу
            if (array_key_exists('database', $_GET)){
                $database = intval($_GET['database']);
                $query = mysql_query('SELECT * FROM `databases` WHERE `id` = '.$database);
            } else {
                $query = mysql_query('SELECT * FROM `databases`');
            }


            if ($query){
                $db_count = mysql_num_rows($query);
                if ($db_count > 0){
                    echo '<div class="cellcontainer">';
                    while ($result = mysql_fetch_array($query)){
                        echo '<div class="cell"><h4>'.$result['name'].'</h4>';
                        if ($_GET['from']){
                            echo '<form method="POST" action="taskeditor.php?act=databases&theme='.$theme.
                                    '&task='.$task.'&from=bb">';
                        } else {
                            echo '<form method="POST" action="taskeditor.php?act=databases">';
                        }
                        echo '<div id="hidden"><label>Новое имя <input type="text" name="dbname" value="'.$result['name'].
                                '"/> [не изменяйте это поле если хотите оставить прежним]</label></div>';
                        echo '  <input type="hidden" name="save" value="true"/>';
                        echo '  <input type="hidden" name="db" value="'.$result['id'].'"/>';
                        echo '  <textarea name="content" rows="12" cols="80">'.$result['description'].'</textarea>';
                        echo '  <label>База 1 <input type="text" name="db_name1" value="'.$result['db_1'].'"/></label>';
                        echo '  <label>База 2 <input type="text" name="db_name2" value="'.$result['db_2'].'"/></label>';
                        echo '  <input name="submit" type="submit" value="Сохранить изменения" />';
                        echo '  <a class="confirm" href="taskeditor.php?act=databases&delete='.$result['id'].'">Удалить</a>';
                        echo '</form></div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>Нет описаний баз даных</p>';
                }
            } else {
                echo '<div class="notify_bad">Ошибка запроса к БД</div>';
            }
        }
        echo '<a href="taskeditor.php?act=databases&add">Добавить тестовую базу</a>';
    break;

/* * * * * * * * * * * * * * * * * * * *
 *
 *            Список тем
 *
 * * * * * * * * * * * * * * * * * * * */

    case 'themes':
        echo '<p class="bnav">Центр управления - Темы | <a href="taskeditor.php">назад</a>'.
                ' - <a href="lobby.php">на главную</a></p>';

        // сохраняем изменения
        if(array_key_exists('save', $_POST)){
            $saved_theme = intval($_POST['theme']);
            $saved_caption = htmlentities(trim($_POST['caption']), ENT_QUOTES, 'UTF-8');
            if ($saved_caption != ''){
                if(!array_key_exists('growing', $_POST)) $_POST['growing'] = 'false';
                if(!array_key_exists('active', $_POST)) $_POST['active'] = 'false';
                $growing_mode = ($_POST['growing'] == 'true')? 'Y': 'N';
                $active = ($_POST['active'] == 'true')? 'Y': 'N';

                $query = mysql_query(   "UPDATE `themes` SET `name` = '".
                                        mysql_real_escape_string($saved_caption)."', ".
                                        "`growing_mode` = '".$growing_mode."', ".
                                        "`active` = '".$active."' ".
                                        'WHERE `id` = '.$saved_theme.' LIMIT 1;'   );
                if ($query){
                    if (mysql_affected_rows() != 0){
                        echo '<div class="notify_good">Изменения сохранены</div>';
                    }
                } else {
                    echo '<div class="notify_bad">Изменения не сохранены из-за неизвестной ошибки</div>';//.mysql_error();
                }
            } else {
                echo '<div class="notify_bad">Нельзя задать пустое имя</div>';
            }
        }

        // создаем новую тему
        if(array_key_exists('add', $_POST)){
            $new_caption = htmlentities(trim($_POST['caption']), ENT_QUOTES, 'UTF-8');
            if ($new_caption != ''){
                if(!array_key_exists('growing', $_POST)) $_POST['growing'] = 'false';
                $growing_mode = ($_POST['growing'] == 'true')? 'Y': 'N';
                $active = ($_POST['active'] == 'true')? 'Y': 'N';
                $query = mysql_query(   "INSERT INTO `themes` (`name`, `growing_mode`, `active`) VALUES ".
                                        "('".mysql_real_escape_string($new_caption)."', ".
                                        "'".$growing_mode."', ".
                                        "'".$active."') "   );
                if ($query){
                    if (mysql_affected_rows() != 0){
                        echo '<div class="notify_good">Тема создана</div>';
                    }
                } else {
                    echo '<div class="notify_bad">Тема не создана из-за неизвестной ошибки</div>';
                }
            } else {
                echo '<div class="notify_bad">Нельзя создать тему с пустым именем</div>';
            }
        }

        // удаляем тему (только пустую)
        if(array_key_exists('deltheme', $_GET)){
            $theme = intval($_GET['deltheme']);

            // проверяем возможность удаления
            $query = mysql_query(   'SELECT COUNT( * ) AS `count` FROM `tasks` WHERE `theme` = '. $theme  );
            if ($query){
                $result = mysql_fetch_array($query);
                if ($result['count'] == 0){
                    $query = mysql_query( 'DELETE FROM `themes` WHERE `id` = '.$theme.' LIMIT 1');
                    if (mysql_affected_rows() != 0){
                        echo '<div class="notify_good">Тема удалена</div>';
                    }
                } else {
                    echo '<div class="notify_bad">В теме содержатся задачи. Для начала нужно удалить их</div>';
                }
            } else {
                echo '<div class="notify_bad">Тема не удалена из-за неизвестной ошибки</div>';
            }
        }

        echo '<p>Активация опции &quot;по возрастанию&quot; запрещает решать задачи не по порядку.</p>';
        /// показыаем список тем или только одну (не показывая форму для новой темы)
        if(array_key_exists('theme', $_GET)){
            $theme = intval($_GET['theme']);
            $query = mysql_query('SELECT * FROM `themes` WHERE `id` = '.$theme);
        } else {
            $query = mysql_query('SELECT * FROM `themes`');
            $theme = -1;
        }

        if ($query){
            $db_count = mysql_num_rows($query);
            if ($db_count > 0){
                echo '<div class="cellcontainer">';
                while ($result = mysql_fetch_array($query)){


                    /**
                     * Форма редакирование тем
                     */
?>                    

<div class="cell">
    <form method="POST" action="taskeditor.php?act=themes<?=(($_GET['from'] == 'bb')?'&theme=.'.$theme.'&task='.$task.'&from=bb':'')?>">
        <input type="hidden" name="save" value="true"/>
        <input type="hidden" name="theme" value="<?=$result['id']?>" />
        <table>
            <tr>
                <td rowspan="2" align="right">
                    <label>Название темы <input name="caption" type="text" value="<?=$result['name']?>" /></label>
                </td>
                <td align="right">
                    <label>активно
                        <input name="active" type="checkbox" value="true" <?=($result['active']=='Y'?'checked="checked" ':'')?> />
                    </label>
                </td>
                <td rowspan="2" align="right">
                    <input name="submit" type="submit" value="Сохранить изменения" />
                </td>
            </tr>
            <tr>
                <td align="right">
                    <label>по возрастанию
                        <input name="growing" type="checkbox" value="true" <?=($result['growing_mode']=='Y'?'checked="checked" ':'')?> />
                    </label>
                </td>
            </tr>
        </table>
    </form>
    <a href="?act=tasks&theme=<?=$result['id']?>">редактировать задачи</a> | 
    <a class="confirm" href="?act=themes&deltheme=<?=$result['id']?>">удалить тему</a>
</div>


<?php
                }
                echo '</div>';
            } else {
                echo '<p class="bnav">Нет тем</p>';
            }
        } else {
            echo '<div class="notify_bad">Ошибка запроса к БД</div>';
        }
        if ($theme == -1){
            echo '<form method="POST" action="taskeditor.php?act=themes">';
            echo        '<table><tr><td rowspan="2" align="right">';
            echo        '<input type="hidden" name="add" value="true"/>';
            echo        '<label>Новая тема <input name="caption" type="text" class="simple_w" />';
            echo        '</td><td align="right">';
            echo        '<label>активно <input name="active" type="checkbox" value="true" checked="checked"/></label>';
            echo        '</td><td rowspan="2" align="right">';
            echo        '<input name="submit" type="submit" value="Создать" class="simple" />';
            echo        '</td></tr><tr><td align="right">';
            echo        '<label>по возрастанию <input name="growing" type="checkbox" value="true" /></label>';
            echo '</td></tr></table>';
            echo '</form>';
        }
    break;

/* * * * * * * * * * * * * * * * * * * *
 *
 *            Список задач
 *
 * * * * * * * * * * * * * * * * * * * */

    case 'tasks':
        // если не задана тема - выбираем
        if(!array_key_exists('theme', $_GET)){
            echo '<p class="bnav">Центр управления - Задачи | <a href="taskeditor.php">назад</a>'.
                ' - <a href="lobby.php">на главную</a></p>';

            $query = mysql_query('SELECT * FROM `themes`');
            if($query){
                if (mysql_num_rows($query) > 0){
                    echo '<p>Выберите тему для редактирования</p>';
                    echo '<p>';
                    while ($result = mysql_fetch_array($query)){
                        echo '<a href="?act=tasks&theme='.$result['id'].'">'.$result['name'].'</a><br/>';
                    }
                    echo '</p>';
                } else {
                    echo '<p>Нет тем для выбора</p>';
                }
            } else {
                echo '<div class="notify_bad">Ошибка запроса к БД</div>';
            }
        } else {
            // показываем вопрос(ы) для редактирования
            $theme = intval($_GET['theme']);
            $query = mysql_query('SELECT `name` FROM `themes` WHERE `id` = '.$theme);
            if ($query){
                if(mysql_num_rows($query) > 0){
                    $result = mysql_fetch_array($query);
                    $theme_name = $result['name'];


                    // запрос уровней сложности
                    $grades_list = Array();
                    $query = mysql_query('SELECT * FROM `grades`');
                    if ($query){
                        if (mysql_num_rows($query) > 0){
                            while ($result = mysql_fetch_array($query)){
                                $grades_list[$result['id']] = $result['caption'];
                            }
                        }
                    }
                    // запрс списка баз данных
                    $databases_list = Array();
                    $databases_description = Array();
                    $query = mysql_query('SELECT `id`, `name`, `description` FROM `databases`');
                    if ($query){
                        if (mysql_num_rows($query) > 0){
                            while ($result = mysql_fetch_array($query)){
                                $databases_list[$result['id']] = $result['name'];
                                $databases_description[$result['id']] = $result['description'];
                            }
                        }
                    }

                    // сохраняем задачу (возможно и новую)
                    if (array_key_exists('savetask', $_POST)){
                        $task = $_POST['task'];
                        $addtask = $task == 'new';
                        if (!$addtask) $task = intval($task);
                        $database = intval($_POST['database']);
                        $grade = intval($_POST['grade']);

                        $question = htmlentities(trim($_POST['question']), ENT_QUOTES, 'UTF-8');
                        $answer = $_POST['answer'];

                        if (!($question == '' || $answer == '')){
                            if (!$addtask){
                                $query = mysql_query(   'UPDATE `tasks` SET `database` = '.$database.', '.
                                                        '`grade` = '.$grade.', '.
                                                        "`question` = '".mysql_real_escape_string($question)."', ".
                                                        "`answer` = '".mysql_real_escape_string($answer)."' ".
                                                        'WHERE `id` = '.$task.' LIMIT 1; ');
                                if ($query){
                                    if (mysql_affected_rows() > 0){
                                        echo '<div class="notify_good">Изменения сохранены</div>';
                                    }
                                } else {
                                    echo '<div class="notify_bad">Ошибка запроса к БД</div>';
                                }
                            } else {
                                $query = mysql_query(  'INSERT INTO `tasks` (`theme`, `database`, `grade`, `question`, `answer`) '.
                                                       'VALUES ( '.
                                                       $theme.' , '.
                                                       $database.' , '.
                                                       $grade.' , '.
                                                       "'".mysql_real_escape_string($question)."', ".
                                                       "'".mysql_real_escape_string($answer)."' );"  );
                                if ($query){
                                    if (mysql_affected_rows() > 0){
                                        echo '<div class="notify_good">Задача добавлена в начало списка, вы можете'.
                                                ' изменить ее местоположение перетащив ее мышью</div>';
                                    }
                                } else {
                                    echo '<div class="notify_bad">Ошибка запроса к БД</div>';
                                }
                            }
                        } else {
                            echo '<div class="notify_bad">Нельзя задавать пустой вопрос или ответ</div>';
                        }
                    }


                    /**
                     * Редактируем задачу
                     * или
                     * создаем новую
                     * или
                     * удаляем (спрашиваем сначала)
                     */
                    if(array_key_exists('task', $_GET) || array_key_exists('add', $_GET)){
                        if (array_key_exists('delete', $_GET)){
                            $action = $_GET['delete'];
                            $task = intval($_GET['task']);
                            if($action == 'ask'){
                                // ask
                                echo '<p class="bnav">Вы действительно хотите удалить эту задачу? Восстановление невозможно!</p>';
                                echo '<h4><a href="?act=tasks&theme='.$theme.'&task='.$task.'&delete=true">да</a>'.
                                    ' - - - <a href="?act=tasks&theme='.$theme.'">нет</a><h4>';
                            } else {
                                // delete
                                $query = mysql_query('DELETE FROM `tasks` WHERE `id` = '.$task.' LIMIT 1 ;');
                                if ($query){
                                    if (mysql_affected_rows() > 0){
                                        echo '<div class="notify_good">Задача удалена</div>';
                                    } else {
                                        echo '<div class="notify_bad">Задача не найдена</div>';
                                    }
                                } else {
                                    echo '<div class="notify_bad">Ошибка запроса к БД</div>';
                                }
                                echo '<p><a href="?act=tasks&theme='.$theme.'">продолжить</a>'.
                                    ' - <a href="lobby.php">на главную</a></p>';

                                // update answers
                                $query = mysql_query('UPDATE `solutions` SET `task` = 0 WHERE `task` = '.$task.' ;');
                                if (mysql_affected_rows() > 0){
                                    echo '<div class="notify_good">Обновлено '.mysql_affected_rows().' записей о верном решении</div>';
                                }
                            }
                        } else {
                            $newtask = array_key_exists('add', $_GET);

                            if (!$newtask){
                                $task = intval($_GET['task']);
                                $query = mysql_query('SELECT * FROM `tasks` WHERE `id` = '.$task.'; ');
                                if ($query){
                                    if (mysql_num_rows($query) > 0){
                                        $result = mysql_fetch_array($query);
                                    } else { $newtask = true; }
                                } else { $newtask = true; }
                            }
                            if (!$newtask){
                                echo '<p class="bnav">Центр управления - Задачи - '.$theme_name.
                                    ' - Задача | <a href="?act=tasks&theme='.$theme.'">назад</a>'.
                                    ' - <a href="lobby.php">на главную</a></p>';
                            } else {
                                echo '<p class="bnav">Центр управления - Задачи - '.$theme_name.
                                    ' - Новая задача'.
                                    ' | <a href="?act=tasks&theme='.$theme.'">назад</a>'.
                                    ' - <a href="lobby.php">на главную</a></p>';
                            }

                            // форма редактирования задачи
                            if ($_GET['from']){
                                echo '<form method="POST" action="taskeditor.php?act=tasks&theme='.$theme.
                                '&task='.$task.'&from=bb">';
                            } else {
                                echo '<form method="POST" action="taskeditor.php?act=tasks&theme='.$theme.'">';
                            }
                            echo '<input type="hidden" name="savetask" value="true" />';
                            if (!$newtask){
                                echo '<input type="hidden" name="task" value="'.$task.'" />';
                            } else {
                                echo '<input type="hidden" name="task" value="new" />';
                            }
                            echo '<table><tr><td>Вопрос:</td><td colspan="2"><label>';
                            if (!$newtask){
                                echo '<textarea name="question" rows="4" cols="70">'.$result['question'].'</textarea>';
                            } else {
                                echo '<textarea name="question" rows="4" cols="70"></textarea>';
                            }
                            echo '</label></td></tr><tr><td>Ответ:</td><td colspan="2"><label>';
                            if (!$newtask){
                                echo '<textarea name="answer" rows="11" cols="70" id="query">'.$result['answer'].'</textarea>';
                            } else {
                                echo '<textarea name="answer" rows="11" cols="70" id="query"></textarea>';
                            }
                            echo '</label></td></tr><tr><td>База:</td><td><label>';
                            echo '<select id="database" name="database">';

                            // список баз
                            foreach($databases_list AS $key => $val){
                                if (!$newtask){
                                    if ($key == $result['database']){
                                        echo '<option selected="selected" value="'.$key.'">'.$val.'</option>';
                                    } else {
                                        echo '<option value="'.$key.'">'.$val.'</option>';
                                    }
                                } else {
                                    echo '<option value="'.$key.'">'.$val.'</option>';
                                }
                            }

                            echo '</select></label></td><td rowspan="2"><label>';
                            echo '<input type="button" value="проверить ответ" id="check_query" />';
                            echo '<input type="submit" value="сохранить" name="submit" /></label>';
                            echo '</td></tr><tr><td>Сложность:</td><td><label><select name="grade">';

                            // список сложностей
                            foreach($grades_list AS $key => $val){
                                if (!$newtask){
                                    if ($key == $result['grade']){
                                        echo '<option selected="selected" value="'.$key.'">'.$val.'</option>';
                                    } else {
                                        echo '<option value="'.$key.'">'.$val.'</option>';
                                    }
                                } else {
                                    echo '<option value="'.$key.'">'.$val.'</option>';
                                }
                            }

                            echo '</select></label></td></tr></table></form>';
                            echo '<div id="response"></div>';
                        }
                    } else {
                        echo '<p class="bnav">Центр управления - Задачи - '.$theme_name.
                                ' | <a href="?act=tasks">назад</a>'.
                                ' - <a href="lobby.php">на главную</a></p>';


                        // сохраняем порядок
                        if (array_key_exists('reorder', $_POST)){
                            // task[]=4&task[]=1&task[]=7&task[]=8&task[]=2&task[]=6&task[]=9&task[]=3&task[]=5
                            $orderarr = explode('&', $_POST['reorder']);
                            $neworder = Array();
                            $t = Array();
                            foreach($orderarr AS $key => $val){
                                $t = explode('=', $val);
                                if (array_key_exists(1, $t)){
                                    $neworder[] = intval($t[1]);
                                }
                            }
                            unset ($t);
                            $affected = 0;
                            foreach ($neworder AS $key => $val){
                                $query = mysql_query(   'UPDATE `tasks` SET `sort` = '.($key+1).' '.
                                                        'WHERE `tasks`.`id` = '.$val.' LIMIT 1; ');
                                if (!$query){
                                    echo '<div class="notify_bad">Порядок не сохранен</div>';
                                    //echo mysql_error();
                                } else {
                                    $affected += mysql_affected_rows();
                                }
                            }
                            echo '<div class="notify_good">Новый порядок сохранен для '.$affected.' вопросов</div>';
                        }

                        echo '<p>Рекомендуется располагать тесты в порядке возрастания сложности<br/>'.
                             'Перетащите мышью панель с вопросом для изменения порядка и нажмите '.
                              '&quot;Сохранить порядок&quot; внизу страницы</p>';

                        echo '<a href="?act=tasks&theme='.$theme.'&add=true">Добавить задачу</a>';

                        // запрос вопросов
                        $query = mysql_query('SELECT * FROM `tasks` WHERE `theme` = '. $theme.' ORDER BY `sort` ASC');
                        if ($query){
                            if (mysql_num_rows($query) > 0){
                                $i = 1;
                                echo '<div class="dragzone">';
                                while ($result = mysql_fetch_array($query)){

                                    echo '<div class="cell" id="task-'.$result['id'].'">';
                                    echo '<table><tr><td rowspan="2" width="20">'.$i.'</td>';
                                    echo '<td colspan="2">'.$result['question'].'</td></tr>';
                                    echo '<tr><td width="200">';
                                    echo '<p class="tip">База: '.$databases_list[$result['database']].'</p>';
                                    echo '<p class="tip">Сложность: '.$grades_list[$result['grade']].'</p>';
                                    echo '</td><td>';
                                    echo '<a href="?act=tasks&theme='.$theme.'&task='.$result['id'].'">Редактировать</a> - ';
                                    echo '<a href="?act=tasks&theme='.$theme.'&task='.$result['id'].'&delete=ask">Удалить</a>';
                                    echo '</td></tr></table>';
                                    echo '</div>';

                                    $i++;
                                }
                                echo '</div>';

                                echo '<form id="save_order" action="?act=tasks&theme='.$theme.'" method="POST">';
                                // сюда подставится новый порядок
                                echo '<input id="save_order_neworder" type="hidden" name="reorder" value="false" />'; 
                                echo '<label>';
                                echo '<input type="submit" value="сохранить порядок" />';
                                echo '</label>';
                                echo '</form>';
                            } else {
                                echo '<p class="bnav">Нет задач в теме</p>';
                            }
                        } else {
                            echo '<div class="notify_bad">Ошибка запроса к БД</div>';
                        }
                    }
                } else {
                    echo '<p class="bnav">Нет такой темы</p>';
                }
            } else {
                echo '<div class="notify_bad">Ошибка запроса к БД</div>';
            }
        }
    break;

 /* * * * * * * * * * * * * * * * * * * *
 *
 *            Список действий
 *
 * * * * * * * * * * * * * * * * * * * */
    
    default:
        echo '<p class="bnav">Центр управления | <a href="lobby.php">на гавную</a></p>';
        echo '<p>Выберите раздел для редактирования</p>';
        echo '<p><a href="?act=databases">Описания баз данных</a><br />';
        echo '<a href="?act=themes">Темы</a><br />';
        echo '<a href="?act=tasks">Задачи</a></p>';
    break;
}
    
?>


    </div>
    <div id="bottomShadow"></div>
    <br class="spacer" />
</div>
<div id="footer">
    <p class="navigation"><?php echo $username ?> - <a href="?logout">Выход</a></p>
    <p class="tworld"><!--<a href="http://edro.me/" target="_blank">О нас</a>-->
        
<?php
/*  Timer */
$end_time = microtime();
$end_array = explode(" ",$end_time);
$end_time = $end_array[1] + $end_array[0];
$time = $end_time - $start_time;

echo 'generated in '.$time.' seconds';
?>
    </p>
</div>
</body>
</html>