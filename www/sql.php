<?php 	
/* GLOBAL */
define('LEGAL', true);

/* TIMER */
$start_time = microtime();
$start_array = explode(" ",$start_time);
$start_time = $start_array[1] + $start_array[0];

/* Заголовки */
header('Content-Type: application/json; charset=utf-8');
//header('Content-Type: text/html; charset=utf-8');

/* БД */
require_once("mysql.php");

/* auth */
require_once("authorize.php");


if (!$logged) {
	$answer['status'] = 1;
	$answer['error'] = 'Auth fail';
} else {
    /**
     * Фильтруем пришедшее поле
     * если находим запрешенное слово - останавливаемся с ошибкой
     */
    require_once("badwords.php");
    $query_text = $_POST['query'];
    $bwtest = bwfilter($query_text);

    /*
     * Иначе начинаем обработку
     */
    if ($bwtest === false){
        /*
         * Выполнять ли только тестовый запрос
         * без проверки правильноcти ответа?
         */
        $sandbox = array_key_exists('sandbox', $_POST);

        if ($sandbox){
            /**
             * Запрашиваем имя базы для работы
             */
            if (array_key_exists('database', $_POST)){
                $database = intval($_POST['database']);
            } else {
                $database = 0;
            }
            $query = mysql_query('SELECT * FROM `databases` where `id` = '.$database);
            if ($query){
                if (mysql_num_rows($query) > 0){
                    $result = mysql_fetch_array($query);
                    $db_name_1 = $result['db_1'];
                    $db_name_2 = $result['db_2'];

                    /* выполняем запрос к базе */
                    /* Подключаем защищенного пользователя */
                    $db_limited_connect = mysql_connect($MYSQL_SERVER, $MYSQL_LIMITED_USER, $MYSQL_LIMITED_PASSWORD);
                    mysql_query("SET NAMES utf8", $db_limited_connect);

                    // выполняем пришедший запрос
                    mysql_select_db($db_name_1, $db_limited_connect);
                    $testing_query_1 = mysql_query($query_text, $db_limited_connect);
                    mysql_select_db($db_name_2, $db_limited_connect);
                    $testing_query_2 = mysql_query($query_text, $db_limited_connect);
                    if (!$testing_query_1 || !$testing_query_2){
                        // если словили ошибку
                        $answer['status'] = 3;
                        $answer['error'] = 'Mysql error: '.mysql_error($db_limited_connect);
                    } else {
                        /**
                         * Проверяем количество строк и выдаем ответ сервера
                         */
                        if ($testing_query_1 !== true){ // mysql возвращает true на запросы типа инсерт etc.
                            $testing_rows_1 = mysql_num_rows($testing_query_1);
                        } else {
                            $testing_rows_1 = 0;
                        }
                        if ($testing_query_2 !== true){ // mysql возвращает true на запросы типа инсерт etc.
                            $testing_rows_2 = mysql_num_rows($testing_query_2);
                        } else {
                            $testing_rows_2 = 0;
                        }
                        if ($testing_rows_1 != 0) {
                            while ($row_testing = mysql_fetch_assoc($testing_query_1))
                                $array_testing_1[] = $row_testing;
                        } else {
                            $array_testing_1[] = Array('Empty'=>'Пустой ответ');
                        }
                        if ($testing_rows_2 != 0) {
                            while ($row_testing = mysql_fetch_assoc($testing_query_2))
                                $array_testing_2[] = $row_testing;
                        } else {
                            $array_testing_2[] = Array('Empty'=>'Пустой ответ');
                        }
                        $answer['arr_testing_1'] = $array_testing_1;
                        $answer['arr_testing_2'] = $array_testing_2;
                        $answer['status'] = 0;
                        $answer['error'] = 'None';
                    }

                } else {
                    // no such database
                    $answer['status'] = 9;
                }
            } else {
                // error query
                $answer['status'] = 9;
            }
        } else {
            /* Не sandbox, делаем все как положено */
            /* Запрашиваем ответ на вопрос и базу */
            $selected_task = intval($_POST['task']);
            $query = mysql_query(	'SELECT `answer`, `db_1`, `db_2` FROM `tasks` `a`, `databases` `b` '.
                                    'WHERE `a`.`database` = `b`.`id` '.
                                    'AND `a`.`id` =' .$selected_task);
                                    /*'SELECT  `answer` FROM `tasks` '.
                                    'WHERE `id` = '.$selected_task.' LIMIT 1'*/

            $row = mysql_num_rows($query);
            if ($row > 0){
                $result = mysql_fetch_array($query);
                //$selected_task_grade = $result['grade'];
                $selected_task_answer = $result['answer'];
                $db_name_1 = $result['db_1'];
                $db_name_2 = $result['db_2'];

                /* Подключаем защищенного пользователя */
                $db_limited_connect = mysql_connect($MYSQL_SERVER, $MYSQL_LIMITED_USER, $MYSQL_LIMITED_PASSWORD);
                mysql_query("SET NAMES utf8", $db_limited_connect);
                //mysql_select_db($MYSQL_DB_1, $db_limited_connect);

                // выполняем пришедший запрос на базах
                mysql_select_db($db_name_1, $db_limited_connect);
                $testing_query_1 = mysql_query($query_text, $db_limited_connect);
                mysql_select_db($db_name_2, $db_limited_connect);
                $testing_query_2 = mysql_query($query_text, $db_limited_connect);

                if (!$testing_query_1 || !$testing_query_2){
                    // если словили ошибку
                    $status = 3;
                    $answer['error'] = 'Mysql error: '.mysql_error($db_limited_connect);
                } else {
                    // если выполнилось без ошибок выполняем эталонный запросs
                    mysql_select_db($db_name_1, $db_limited_connect);
                    $answer_query_1 = mysql_query($selected_task_answer, $db_limited_connect);
                    mysql_select_db($db_name_2, $db_limited_connect);
                    $answer_query_2 = mysql_query($selected_task_answer, $db_limited_connect);

                    // начинаем проверять ответы
                    $correct_answer = true; // пока

                    // сверяем ответы - определяем количество строк и столбцов
                    if ($testing_query_1 !== true){ // mysql возвращает true на запросы типа инсерт etc.
                        $testing_rows_1 = mysql_num_rows($testing_query_1);
                        $testing_fields_1 = mysql_num_fields($testing_query_1);
                    } else {
                        $testing_rows_1 = 0;        // и в них 0 строк
                        $testing_fields_1 = 0;
                    }
                    if ($testing_query_2 !== true){
                        $testing_rows_2 = mysql_num_rows($testing_query_2);
                        $testing_fields_2 = mysql_num_fields($testing_query_2);
                    } else {
                        $testing_rows_2 = 0;
                        $testing_fields_2 = 0;
                    }
                    if ($answer_query_1 !== true){
                        $answer_rows_1 = mysql_num_rows($answer_query_1);
                        $answer_fields_1 = mysql_num_fields($testing_query_1);
                    } else {
                        $answer_rows_1 = 0;
                        $answer_rows_2 = 0;
                    }
                    if ($answer_query_2 !== true){
                        $answer_rows_2 = mysql_num_rows($answer_query_2);
                        $answer_fields_2 = mysql_num_fields($testing_query_2);
                    }

                    // количество строк
                    if ($testing_rows_1 <> $answer_rows_1 || $testing_rows_2 <> $answer_rows_2) {
                        $status = 4;
                        $answer['error'] = 'Wrong rowcount';
                        $correct_answer = false;
                    }
                    // количество столбцов
                    if ($testing_fields_1 <> $answer_fields_1 || $testing_fields_2 <> $answer_fields_2) {
                        $status = 6;
                        $answer['error'] = 'Wrong colcount';
                        $correct_answer = false;
                    }


                    // сверяем данные если не встретили ошибки
                    if($correct_answer){
                        // 1 db
                        if ($answer_rows_1 > 0){
                            $resume = true;
                            while (true){
                                $row_answer = mysql_fetch_assoc($answer_query_1);
                                $row_testing = mysql_fetch_assoc($testing_query_1);
                                if( !($resume = $row_answer && $row_testing) ) break;
                                $array_answer[] = $row_answer;
                                $array_testing[] = $row_testing;
                                // если не было ошибки - сверяем построчно
                                if ($correct_answer){
                                    foreach($row_answer AS $key => $val){
                                        if ($val != $row_testing[$key]){
                                            $correct_answer = false;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        // 2 db
                        if ($answer_rows_2 > 0){
                            $resume = true;
                            while (true && $correct_answer){ // пропускаем если всретили ошибку ранее
                                $row_answer = mysql_fetch_assoc($answer_query_2);
                                $row_testing = mysql_fetch_assoc($testing_query_2);
                                if( !($resume = $row_answer && $row_testing) ) break;
                                // если не было ошибки - сверяем построчно
                                if ($correct_answer){
                                    foreach($row_answer AS $key => $val){
                                        if ($val != $row_testing[$key]){
                                            $correct_answer = false;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    } else { // иначе просто копируем
                        while ($row_answer = mysql_fetch_assoc($answer_query_1))
                            $array_answer[] = $row_answer;
                        if ($testing_rows_1 != 0) {
                            while ($row_testing = mysql_fetch_assoc($testing_query_1))
                                $array_testing[] = $row_testing;
                        } else {
                            $array_testing[] = Array('Empty'=>'Пустой ответ');
                        }
                    }
                    $answer['arr_answer'] = $array_answer;
                    $answer['arr_testing'] = $array_testing;

                    // если не было ошибки - решение верно
                    if ($correct_answer){
                        $status = 0;
                        $answer['error'] = 'None';
                    } else {
                        $status = 5;
                        $answer['error'] = 'Wrong answer';
                    }
                }
                $answer['status'] = $status;

                // записываем ответ в базу
                $query = mysql_query(   'INSERT INTO `solutions`(`responder`, `task`, `status`) '.
                                        'VALUES ('.$userid.','.$selected_task.','.$status.')',
                                    $db_master_connect);
            } else {
                $answer['status'] = 2;
                $answer['error'] = 'Wrong task';
            }
        }

    } else {
        $answer['status'] = 8;
        $answer['error'] = $bwtest;
    }
}



$end_time = microtime();
$end_array = explode(" ",$end_time);
$end_time = $end_array[1] + $end_array[0];
$answer['gentime'] = $end_time - $start_time;

echo json_encode($answer);
?>