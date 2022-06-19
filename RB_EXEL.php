<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
include("../../inc2/conf.inc.php");
require("../../inc2/functions.inc.php");

#Устанавливаем свзь с базой данных
if(($lnk=db_connect($cfg["db_host"],$cfg["db_user"],$cfg["db_pass"],$cfg["db_db"]))===false) exit;
mysql_query("SET NAMES 'utf8';");
mysql_query("SET CHARACTER SET 'utf8';");
mysql_query("SET SESSION collation_connection = 'utf8_general_ci';");
$auth_messg="";

#Проверка авторизации
$login=checkAuth($lnk);

# Если тестирование на авторизацию не пройдено выдать соответствующее сообщение
if(!$login)
{
echo "Вы не прошли авторизацию.";
exit;
}

//получаем данные бита root прав на модули
$root_perm=mysql_query ("SELECT * FROM modules WHERE name='root'", $lnk);
$root_perm_bit = mysql_fetch_array ($root_perm, 0);

// Проверяем пользователя на права рута
if (CheckPerm($login['dostup'], $root_perm_bit['bit']))
{
	$root=true;
}else
{
	$root=false;
}

//получаем данные модуля в масив $modul_bit
$modul=mysql_query ("SELECT * FROM modules WHERE name='destination'", $lnk);
$modul_bit = mysql_fetch_array ($modul, 0);

// Проводим проверку доступен ли данный модуль авторизировавшемуся пользователю в соответствии с его правами 
// Если прав на использование модуля нет то выдать соответствующее сообщение
// if (!CheckPerm($login['dostup'], $modul_bit['bit']) and !$root)
// {
// echo "У вас нет доступа к этому модулю.";
// exit;
// }
date_default_timezone_set("Europe/Moscow");
$date_now=date("Y-m-d");
$tomorrow=date("Y-m-d", strtotime("tomorrow"));
$esterday=date("Y-m-d", strtotime("-1 day"));

// Билет,Время отправления,Время прибытия,Время в дороге,Маршрут,Взрослый,Детский

$csv = array_map('str_getcsv', file('2.csv')); 
//echo json_encode($csv);
$str=count($csv);
$collum=count($csv['0']);

// 1 взять список станций ПРИБЫТИЯ (станция + время = Москва 16:30)
// 2 формируем строку: пустая ячейка + все выбранные станции
// 3 Взять список станций отправления отсортированный по времени отправления
// 4 по итерации взять станцию из списка станций отправления++ внести в новую строчку (станция ОТПРАВЛЕНИЯ + время = Ставрополь 15:30)
// 5 произвести поиск билета из текущая станция отправления + певая++ станция прибытия из списка станций прибытия с итерацией по списку станций прибытия
//     если билет найден взять его цены и внести в следующую ячейку текущей строки
//     если билет не найден вносим в ячейку 0

for($i=1;$i<$str; $i++)
{
	$reisy[$csv[$i]['5']][]=$csv[$i];
}

foreach($reisy AS  $key=>$reis)
{
	//$new_table[$key][]='';
	foreach($reis as $valStation)
	{
		$new_table[$key][$valStation[0].' '.$valStation[2]][]=$valStation[1].' '.$valStation[3];
	}

	
}
echo json_encode($new_table);
	exit;	
echo json_encode($reisy);