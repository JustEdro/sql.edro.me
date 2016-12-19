<?php 	
/* GLOBAL */
define("LEGAL", true);

/* Заголовки */
header("Content-Type: text/html; charset=utf-8");

/* TIMER */
$start_time = microtime();
$start_array = explode(" ",$start_time);
$start_time = $start_array[1] + $start_array[0];

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
<script type="application/javascript" src="service/tables.js"></script>    
<script type="application/javascript">

$(document).ready(function(){
   colorize();
});

</script>
</head>
<body>
<div id="body">
    <div id="topShadow"></div>
    <div id="bodyPannel">


<?php
// проверяем тему
if (array_key_exists('theme', $_GET)){
    $theme = intval($_GET['theme']);
    $query = mysql_query('SELECT * FROM `themes` WHERE `id` = '.$theme.' LIMIT 1;');
    if ($query){
        if (mysql_num_rows($query) > 0){
            $result = mysql_fetch_array($query);
            $theme_name = $result['name'];
        } else {
            $theme = -1;
        }
    } else {
        $theme = -1;
    }
} else {
     $theme = -1;
}

// выводим таблицу или список тем
if ($theme != -1){
    // получаем список задач из темы
    $query = mysql_query(	'SELECT `id`, `grade` FROM `tasks` WHERE `theme` = '.$theme.' ORDER BY `sort` ASC;');
    $tasks = Array();
    if ($query){
        $tasks_count = mysql_num_rows($query);
        if ($tasks_count > 0){
            while ($result = mysql_fetch_array($query)){
                $tasks[$result['id']] = $result['grade'];
            }
        }
    } else {
        $tasks_count = 0;
    }
    // получаем результаты решений
    $query = mysql_query(	'SELECT `users`.`name`, `solutions`.`responder`, `solutions`.`task`, `solutions`.`status`, '.
                                '`solutions`.`timestamp` '.
                            'FROM `solutions` '.
                            'LEFT JOIN `users` '.
                            'ON `users`.`id` = `solutions`.`responder` '.
                            'WHERE `solutions`.`task` IN ('.
                                'SELECT `id` FROM `tasks` '.
                                'WHERE `theme` = '.$theme.' '.
                            ') '
                        );

    $monitor = Array();
    $sort = Array();
    if ($query){
        $sol_count = mysql_num_rows($query);
        // построение таблицы
        if ($sol_count > 0){
            while ($result = mysql_fetch_assoc($query)){
                $responder = $result['responder'];
                $responder_name = $result['name'];
                // проверяем ответившего
                if (!array_key_exists($responder, $monitor)){
                    // initializing
                    $monitor[$responder] = Array('name' => $responder_name, 'time' => 0, 'solved' => 0 );
                    foreach($tasks AS $key => $val){
                        $monitor[$responder][$key][] = 0;       // fail attempts
                        $monitor[$responder][$key][] = false;   // solved
                    }
                }
                // заносим ответ
                if ($result['status'] != 0){
                    $monitor[$responder][$result['task']][0] += 1;
                    $monitor[$responder]['time'] += 1;
                } else {
                    if (!$monitor[$responder][$result['task']][1]){
                        $monitor[$responder][$result['task']][1] = true;
                        $monitor[$responder]['solved'] += 1;
                    }
                }
            }
            // сортируем
            foreach($monitor AS $key => $val){
                $sort[] = Array($key, $val['solved'], $val['time']);
            }
            // по количеству решенных
            for ($i = 0; $i < count($sort); $i++){
                for ($j = $i + 1; $j < count($sort); $j++){
                    if ($sort[$i][1] < $sort[$j][1]){
                        $temp = $sort[$i];
                        $sort[$i] = $sort[$j];
                        $sort[$j] = $temp;
                    }
                }
            }
            // по ошибкам
            for ($i = 0; $i < count($sort); $i++){
                for ($j = $i + 1; $j < count($sort); $j++){
                    if ($sort[$i][1] != $sort[$j][1]) break;
                    if ($sort[$i][2] > $sort[$j][2]){
                        $temp = $sort[$i];
                        $sort[$i] = $sort[$j];
                        $sort[$j] = $temp;
                    }
                }
            }
        } 

    } else {
        $sol_count = 0;
    }

    // вывод
    echo '<h4>Статистика решений по теме &quot;'.$theme_name.'&quot;</h4>';
    echo '<p><a href="lobby.php">на главную</a></p>';
    if ($sol_count == 0){
        echo '<p class="bnav">Никто еще не решил вопросы и этой темы</p>';
    } else {
        // выводим в убывающем порядке
        echo '<table width="100%" border="1" rules="all" cellpadding="5" class="col" bordercolor="black">';
        // заголовок
        echo '<tr>';
        echo    '<th>';
        echo        'Имя';
        echo    '</th>';
        for ($i = 1; $i <= $tasks_count; $i++){
            echo    '<th width="25">';
            echo        $i;
            echo    '</th>';
        }
        echo    '<th>';
        echo        'Всего';
        echo    '</th>';
        echo '</tr>';
        echo "\n";

        // данные
        for ($i = 0; $i < count($sort); $i++){
            $curr = $sort[$i][0];
            echo '<tr>';
            echo    '<td>';
            echo        $monitor[$curr]['name'];
            echo    '</td>';
            // выводим решенные задачи и количество попыток
            foreach ($tasks AS $key => $val){
                $att = $monitor[$curr][$key][0];
                if ($att == 0) $att = '';
                if ($monitor[$curr][$key][1]){
                    echo    '<td align="center" class="solved">';
                    echo         '+'.$att;
                    echo    '</td>';
                } else {
                    echo    '<td align="center" class="failed">';
                    echo         '-'.$att;
                    echo    '</td>';
                }
            }
            echo    '<td align="center">';
            echo        $monitor[$curr]['solved'];
            echo        '<small>/'.$tasks_count.'</small>';
            echo    '</td>';

            echo '</tr>';
            echo "\n";
        }
        echo '</table>';
    }
    
    
} else {
    $sql = "SELECT * FROM THEMES";
    $query = mysql_query($sql);
    if ($query){
        while($result = mysql_fetch_array($query)) {
            echo '<p><a href="/monitor.php?theme='.$result['id'].'">'.$result['name'].'</a></p>';
        }
    }
}

?>
    </div>
    <div id="bottomShadow"></div>
    <br class="spacer" />
</div>
<div id="footer">
    <p class="navigation"><?php echo $username ?> - <a href="?logout">Выход</a></p>
    <p class="tworld">
<?php
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