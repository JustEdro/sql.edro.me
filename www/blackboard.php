<?php 	
/* GLOBAL */
define('LEGAL', true);

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

/* TIMER */
$start_time = microtime();
$start_array = explode(" ",$start_time);
$start_time = $start_array[1] + $start_array[0];

/* Заголовки */
header('Content-Type: text/html; charset=utf-8');

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


// проверяем, выбрана ли тема
if (array_key_exists('theme', $_GET)){
	$random_mode = false;
	
	// получаем тему
	$query = mysql_query(	'SELECT * FROM `themes` '.
							'WHERE `id` = '.intval($_GET['theme']).' LIMIT 1');
	$row = mysql_num_rows($query);
	
	if ($row > 0) {
		$result = mysql_fetch_assoc($query);
		$selected_theme_name = $result['name'];
		$selected_theme  = $result['id'];
		$selected_theme_growing = $result['growing_mode'] == 'Y';
		/*foreach ($result as $key => $value){
			echo $key.' '.$value.'<br/>';
		}*/
	} else {
		$random_mode = true;
	}
} else {
	$random_mode = true;
}

if (!$random_mode){
    // забираем вопросы по теме из базы
	$query = mysql_query('SELECT * FROM `tasks` WHERE `theme` = '.$selected_theme.' ORDER BY `sort` ASC;');
	$tasks_count = mysql_num_rows($query);
	if ($tasks_count > 0){
		while($result = mysql_fetch_array($query)) {
			$tasks_list[$result['id']]['question'] = $result['question'];
			$tasks_list[$result['id']]['grade'] = $result['grade'];
		}

        // сколько вопросов из темы решено и по сколько раз
        $solved_tasks = Array();
		$query = mysql_query(	'SELECT `task`, COUNT( * ) AS `attempt` '.
                                'FROM `solutions` '.
                                'JOIN `tasks` ON `tasks`.`id` = `solutions`.`task` '.
                                'WHERE `solutions`.`responder` = '.$userid.' '.
                                'AND `solutions`.`status` = 0 '.
                                'AND `tasks`.`theme` = '.$selected_theme.' '.
                                'GROUP BY `solutions`.`task` '.
                                'ORDER BY `solutions`.`task` ' );
        $selected_theme_solved = mysql_num_rows($query);
        if($selected_theme_solved > 0){
		    while($result = mysql_fetch_array($query)){
                //echo serialize($result).'<br/>';
                $solved_tasks[$result['task']] = $result['attempt']; // сколько раз решил задачу
            }
        }


		// ищем выбранный вопрос если нужно
		if (array_key_exists('task', $_GET)){
			$getkey = intval($_GET['task']);
			if (array_key_exists($getkey, $tasks_list)){
				$selected_task = $getkey;
				$start_from_first = false;
                $selected_task_pos = 1; // позиция в списке вопросов нужно для growing
                foreach($tasks_list AS $key => $val){
                    if ($key != $getkey){
                        $selected_task_pos++;
                    } else {
                        break;
                    }
                }
			} else {
				$start_from_first = true;
			}
			unset ($getkey);
		} else {
			$start_from_first = true;
		}

        // holy shit
		if ($start_from_first) {
            $selected_task_pos = 1;
			if (count($tasks_list) > 0){
                // устанавливаем указатель на начале, на случай нсли не найдем нерешенных
				reset($tasks_list);
				$selected_task = key($tasks_list);
                // найдем первый, к которому есть доступ
                $i = 1;
                foreach($tasks_list AS $key => $val){
                    // получаем первую нерешеную или первую доступную
                    if (!array_key_exists($key, $solved_tasks) ){
                        $selected_task = $key;
                        $selected_task_pos = $i;
                        break;
                    }
                    $i++;
                }
                unset($i);   
			} else {
				$selected_task = -1;
			}
		}



        // проверим доступ к этому вопросу
        if($selected_theme_growing && $selected_task_pos > $selected_theme_solved + 1){
            $i = 1;
            // если че, найдем первый, к которому есть доступ
            foreach($tasks_list AS $key => $val){
                // получаем первую нерешеную или последнюю доступную
                if (($i > $selected_theme_solved) || !array_key_exists($key, $solved_tasks) ){
                    $selected_task = $key;
                    break;
                }
                $i++;
            }
            $selected_task_pos = $i;
            unset($i);
        }


		// запрос описания базы
		if ($selected_task != -1){
			$query = mysql_query(	'SELECT `databases`.`id`, `databases`.`description` '.
									'FROM `databases` '.
                                    'JOIN `tasks` ON `databases`.`id` = `tasks`.`database` '.
									'WHERE `tasks`.`id` = '.$selected_task.' LIMIT 1;');
			$row = mysql_num_rows($query);
			if ($row > 0){
				$result = mysql_fetch_array($query);
				$selected_theme_db_description = $result['description'];
                // выполняем замену
                $rfrom = array('{', '}', '[', ']', "\n");
                $rto = array('<div class="code">', '</div>', '<span class="ac">', '</span>', '<br />');
                $selected_theme_db_description = str_replace($rfrom, $rto, $selected_theme_db_description);
				$selected_theme_db = $result['id'];
			}
		}


		// проверка, решен ли этот вопрос
		if ($selected_task != -1){
			$query = mysql_query(	'SELECT * FROM `solutions` '.
									'WHERE `responder` = '.$userid.' AND `task` = '.$selected_task.' '.
									'ORDER BY `timestamp` DESC LIMIT 1');
			$row = mysql_num_rows($query);
			if ($row > 0){
				$result = mysql_fetch_array($query);
				$selected_task_solutions = $row;
				$last_solution_time = $result['timestamp'];
			} else {
				$selected_task_solutions = 0;
			}
		} else {
			$selected_task_solutions = 0;
		}
		
	} else {
		$start_from_first = true;
		$selected_task = -1;
        $selected_theme_solved = 0;
        $selected_task_solutions = 0;
        $selected_theme_db_description = '';
        $solved_tasks = Array();
	}
} else {
	// random mode?
    $start_from_first = true;
    $selected_task = -1;
    $selected_task_pos = -1;
    $selected_theme_solved = 0;
    $selected_task_solutions = 0;
    $selected_theme_db_description = '';
    $solved_tasks = Array();
    $selected_theme = 0;
    $tasks_count = 0;
}


// список уровней сложности
$query = mysql_query('SELECT * FROM `grades`;');
$row = mysql_num_rows($query);
if ($row > 0){
	while ($result = mysql_fetch_array($query)){
		$grades_list[$result['id']] = $result['caption'];
	}
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
    var currTask = <?php echo $selected_task?>;
    var currPos = <?php echo $selected_task_pos?>;
    var currTheme = <?php echo $selected_theme?>;

    var solved = Array();
<?php // формируем массив с решенными задачами
if ($selected_theme_solved > 0){
    foreach ($solved_tasks as $key => $val){
        echo '    solved['.$key.'] = '.$val.';'."\n";
    }
}?>
    var tasks = Array();
<?php // формируем массив с задачами и сложностями
if ($tasks_count > 0){
    $i = 1;
    foreach ($tasks_list as $key => $val){
        echo '    tasks['.$i++.'] = '.$val['grade'].';'."\n";
        //echo '    tasks['.$i++.']["grade"] = '.$val['grade'].';'."\n";
    }
}
/*?>
    var grades = Array();
<?php // формируем массив со сложностями
foreach ($grades_list as $key => $val){
    echo '    grades['.$key."] = '".$val."';\n";
}
*/?>

	var responseDisplaying = false;
	$("#query_submit").click(function(){
		$('#query_submit').addClass('inactive');
		$('#query_status').removeClass('inactive');
		$.post('/sql.php', {query: $("#query").val(), task: currTask}, function(json){
			if (!responseDisplaying) {
				$('#response').css('opacity', 0);
				$('#response').animate({opacity: 1}, "slow");
				responseDisplaying = true;
			}
            
            $('#response').html('');
            switch(json.status){
                    case 0: $('#response').append('Запрос выполнен без ошибок за: '+ json.gentime + ' сек.'); break;
                    case 1: $('#response').append('Ошибка авторизации на сервере'); break;
                    case 2: $('#response').append('Задача не найдена'); break;
                    case 3: $('#response').append('Ошибка в запросе:<br/><div class="code">' + json.error+'</div>'); break;
                    case 4: $('#response').append('Неверный ответ, количество строк в вашем ответе не верно'); break;
                    case 5: $('#response').append('Неверный ответ'); break;
                    case 6: $('#response').append('Неверный ответ, количество столбцов в вашем ответе не верно'); break;
                    case 8: $('#response').append('В запросе содержатся выражения, запрещенные в целях безопасности:'+
                                                  '<br/><div class="code">' + json.error+'</div>'); break;
                    case 9: $('#response').append('Необрабатываемая ошибка'); break;
            }
            $('#response').append('<br/><br/>');

			if (json.status == 0){
                $('#response').append(serializeTable(json.arr_answer, 'Правильный ответ'));
			}
            // print tables
			if ( json.status == 4 || json.status == 5 || json.status == 6 ){
				$('#response')  .append(serializeTable(json.arr_testing, 'Ваш ответ'))
                                .append('<br />')
                                .append(serializeTable(json.arr_answer, 'Правильный ответ'));
			}

			if (json.status == 0){
				// перестраиваем окно
				$('#query_status').html('Верный ответ, выберите следующую задачу').css('font-weight', 'bold');
				$('#query_container').animate({height: 0}, "slow");
				$('#query_container').addClass('inactive');
				$('#select_task_container').animate({height: 370}, "slow");
				$('#task_sel').attr("size", 13);
				$('#task_sel').animate({width: 320}, "slow");
				$('#task_container2').animate({opacity: 0}, "slow");
				$('#task_container').animate({height: 0}, "slow");
				$('#task_container').addClass('inactive');
				$('#task_container2').addClass('inactive');

                // чуть чуть редактируем список
                if (solved[currTask]){
                    solved[currTask]++;
                } else {
                    solved[currTask] = 1;
                }
                var solvedlength = 0;
                $.each(solved, function(ind, val){
                    if (val > 0){
                        solvedlength++;
                    }
                });
                var n = 1;
                var lastgrade = -1;
                $('#task_sel option').each(function(i,elem) {
                    if (lastgrade != tasks[n]){
                        lastgrade = tasks[n];
                    } else {
                        if (n == currPos) $(elem).html('№ '+n+' ✔');
                        if (n == solvedlength + 1) {
                            $(elem).removeAttr('disabled');
                            if ($(elem).html() != '№ '+n+' ✔')
                                $(elem).html('№ '+n);
                            // быдлокод еще тот :]
                        }
                        n++;
                    }
                });

			} else {
				$('#query_submit').removeClass('inactive');
				$('#query_status').addClass('inactive');
			}
			
			colorize();
        }, 'json'); 
	}); // end submit
	
	// auto insert
	$('.ac').click(function(){
        var dom = document.getElementById('query');
        var selst = dom.selectionStart;
        var selend = dom.selectionEnd;
		var text = $("#query").val();
        var ltext = text.substr(0, selst);
        var rtext = text.substr(selend);
        var ctext = $(this).html();
        if (ltext.substr(ltext.length - 1) != " " && ltext.length > 0) ltext += ' ';
        if (rtext.substr(0, 1) != " " && rtext.length > 0) rtext = ' ' + rtext;
        $("#query").val(ltext+ctext+rtext);
        dom.selectionEnd = ltext.length + ctext.length;
        dom.selectionStart = dom.selectionEnd;
        $("#query").focus();
	});
    $('.ac').mouseover(function(){
		//alert('in'); 
		$(this).addClass('bold');
	});
    $('.ac').mouseout(function(){
		//alert('out'); 
		$(this).removeClass('bold');
	});

    // textarea resize
    $('#query').attr('cols', ($('#bb_left').width() - 10) / 8.6 );// by default
    $(window).resize(function(){
        $('#query').attr('cols', ($('#bb_left').width() - 10) / 8.6 );
    });
	
});
</script>
    
</head>
<body>
<div id="body_wide">
    <div id="topShadowWide">
		<p class="navigation">
			<?php echo $username ?> - <a href="?logout">Выход</a>
		</p>
		<p class="navigation">
			<?php
			if ($random_mode){
				echo 'Случайное задание';
			} else {
				echo 'Выбрана тема: '. $selected_theme_name;
			}
            if($user_editor){
                echo ' | <a href="/taskeditor.php?act=themes&theme='.$selected_theme.'&task='
                        .$selected_task.'&from=bb">редактировать</a>';
            }
            echo  ' - <a href="/lobby.php">на главную </a>'
            ?>
		</p>
		<p class="navigation">
			<?php 
			if ($selected_theme_solved > 0){
				echo 'Вы решили '.$selected_theme_solved.' задач из этой темы';
			} else {
				echo 'Вы не решили ни одной задачи из этой темы';
			}
			?>
		</p>
		<div class="spacer"></div>
	</div>
    <div id="bodyPannelWide">
		<div id="bb_left">
			<div id="select_task_container">
				<h3>Текущая задача: №<?php echo $selected_task_pos; ?></h3>
				<?php if($selected_task_solutions != 0) {echo '<p>Задача решена '.$last_solution_time.'</p>';}?> 
				<form method="GET" action="/blackboard.php" style="width: 305px">
					<input type="hidden" name="theme" value="<?php echo $selected_theme; ?>" />
					<select id="task_sel" name="task">
				
<?php /// вывод списка вопросов
if ($selected_task != -1){
	$lastkey = '-1';
    $number = 1; // для запрета перескакивания через задачи и вывода списка начиная с 1
	foreach($tasks_list as $key => $var){
		// выводим уровень сложности
		if	($var['grade'] != $lastkey){
			$lastkey = $var['grade']; 
			echo '<option disabled="disabled">'.$grades_list[$lastkey].'</option>';
		}
        // ✓✔✗✘
        // вывод эл-в списка
		if ($key == $selected_task){
			if (array_key_exists($key, $solved_tasks)){
                echo '<option class="default" selected="selected" value="'.$key.'">№ '.$number.' ✔</option>';
            } else {
                echo '<option class="default" selected="selected" value="'.$key.'">№ '.$number.'</option>';
            }
		} else if (array_key_exists($key, $solved_tasks)){
            echo '<option class="solved" value="'.$key.'">№ '.$number.' ✔</option>';
        } else {
			if ($selected_theme_growing){
                if ($number > $selected_theme_solved + 1){ // делаем некоторые темы недоступными
                    echo '<option disabled="disabled" value="'.$key.'">№ '.$number.' ✘</option>';
                } else {
                    echo '<option value="'.$key.'">№ '.$number.'</option>';
                }
            } else {
                echo '<option value="'.$key.'">№ '.$number.'</option>';
            }
		}
        $number++;
	}
	unset ($lastkey);
} else {
	echo '<option value="-1" disabled="disabled" selected="selected">Нет вопросов</option>';
}
?>

					</select>
					<input type="submit" value="перейти" title="перейти" class="go" />
				</form>
			</div>
			<div id="task_container">
				<div id="task_container2">
					<h3>Текст задачи</h3>
					<p><?php if($tasks_count > 0) echo $tasks_list[$selected_task]['question']; ?>
                        <?php if($user_editor){ ?>
                        <br/>
                        <a href="taskeditor.php?act=tasks&theme=<?php
                            echo $selected_theme ?>&task=<?php echo $selected_task ?>&from=bb">
                            [редактировать]
                        </a>
                        <?php } ?>
                    </p>
					
					<h3>Описание базы данных</h3>
					<p><?php echo $selected_theme_db_description; ?>
                        <?php if($user_editor){ ?>
                        <br/>
                        <a href="taskeditor.php?act=databases&database=<?php echo $selected_theme_db ?>&theme=<?php
                            echo $selected_theme ?>&task=<?php echo $selected_task ?>&from=bb">
                            [редактировать]
                        </a>
                        <?php } ?>
                    </p>
				</div>
			</div>
		</div>
		<div id="bb_right">
			<h3>Запрос</h3>
			<div id="query_container">
				<p>Впишите запрос</p>
                <div class="code">
                    <span class="ac">WHERE</span>
                    <span class="ac">LIKE</span>
                    <span class="ac">UNION</span>
                    <span class="ac">JOIN</span>
                    <span class="ac">LEFT</span>
                    <span class="ac">RIGHT</span>
                    <span class="ac">GROUP BY</span>
                </div>

				<textarea name="query" id="query" rows="15" cols="65">SELECT * FROM </textarea>
			</div>
			<div class="spacer" ></div>
			<div style="height: 30px">
				<a id="query_submit">Выполнить</a>
				<p id="query_status" class="inactive">Запрос выполняется...</p>
			</div>
			<div id="response"></div>
		</div>
		<div class="spacer"></div>
    </div>
    <div id="bottomShadowWide"></div>
    <div class="spacer" ></div>
</div>
<div id="footerWide">
    <p class="tworld">
    <!--<a href="http://edro.me/" target="_blank">О нас</a><br />
        Оформление: <a href="http://www.templateworld.com/" target="_blank">Template World</a>--> 
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