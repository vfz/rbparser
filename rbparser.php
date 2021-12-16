<?php

date_default_timezone_set('Europe/Moscow');
header("Content-Type: application/json; charset=UTF-8");
@$target=$argv[1];//htmlspecialchars($_GET['target']);
if (empty($argv[1])){
    @$target=htmlspecialchars($_GET['target']);
}


///////////////
//
//  Base functions
//
//////////////

function getUrl($url){

    $defaults = array(
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => "Mozilla/4.0",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/plain"
        ),
        );
        
    $curl = curl_init();   
    curl_setopt_array($curl, $defaults);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function get_list_str_ticket($str){
    $arr_table=explode("<a href=\"/reys/", $str);
    foreach($arr_table AS $str_u){
        $pos_stop = mb_strpos($str_u, "\"", 0);
        $result[] = "/reys/".mb_substr($str_u, 0, $pos_stop );
        unset($result[0]);
    }
    return $result;
}

// function get_list_tikets($str){
    
//     return $result;
// }

// function get_list_stations($str){
    
//     return $result;
// }

function getTicketList(){
    $list_url_tikets=array();
    $id=0;

        while($id<439){
            $id_brake=$id+100;
            echo "
            ===================
            Обработанно ".$id." страниц (".date("Y-m-d H:i:s").")
            ===================
            ";
            if($id_brake>400)
            {
                $id_brake=439;
            }

            for($id; $id<=$id_brake; $id++ ){
                
                    
                    $response=getUrl("https://ros-bilet.ru/perevozchik/evrotrans-ip-yacunov-sp?field_city_tid=&field_city_arrival_tid=&page=0,".$id."/");
                    
                    $list_url_tikets=array_merge($list_url_tikets, get_list_str_ticket($response));//get_list_str_ticket($response);
                    
                }
            
        }

    echo "
    =========================
    Запись в файл url_list.json (".date("Y-m-d H:i:s").")
    =========================";
    file_put_contents('url_list.json',json_encode($list_url_tikets));
    
    echo "
    =========================
    конец обработки (".date("Y-m-d H:i:s").")
    =========================
    ";

}


//////////////
//
//  Targets
//
//////////////

if ($target=="count_json"){
    $string_for_file=file_get_contents('url_list.json');
    $arr_url=json_decode($string_for_file);

    echo "
    =========================
    кол-во записей в url_list.json: ".count($arr_url)."
    =========================
    ";
    exit;
}

if ($target=="save_ticket_list"){
    
    getTicketList();
}

if ($target=="gate_race_and_ticket"){
    

    $string_for_file=file_get_contents('url_list.json');
    $arr_url=json_decode($string_for_file);
    $i=0;
    $iplus=0;
    $arr_races=array();
    foreach($arr_url as $url)
    {
        //"https://ros-bilet.ru".
        $response=getUrl("https://ros-bilet.ru".$url);
        // Определяем активность рейса опираясь на доступность бронирования
        $test_desable = mb_substr_count($response, "В данный момент бронирование невозможно");
        $test_enable = mb_substr_count($response, "Для данного рейса доступны следующие виды брони");
        
        // Вырезаем часть страницы с описанием маршрута
        $pos_start = mb_strpos($response, '<a name="route"></a>', 0);
        $pos_stop = mb_strpos($response, '<a name="baggage"></a>', 0);
        $raceGlobal = mb_substr($response, $pos_start, $pos_stop );
        
        // Находим весь маршрут и складываем его в массив (делим по запятым)
        $pos_start = mb_strpos($raceGlobal, '<div class="field-item even">', 0);
        $pos_stop = mb_strpos($raceGlobal, '</div>', $pos_start+29);
        $race_route=explode(',',trim(mb_substr($raceGlobal, $pos_start+29, $pos_stop-$pos_start-29)));
        $end_stat=count($race_route)-1; //ID элемента масива с последней станцией в маршруте
        
        // Получаем информацию о билете откуда куда время отправления время в пути цены
        // Время отправления по билету $start_tiket
        $pos_start = mb_strpos($response, '<div class="tline even">', 0);
        $pos_stop = mb_strpos($response, '</div>', $pos_start+24);
        $pos_stop_global = $pos_stop;
        for($b=1;$b<7;$b++)
        {
            $pos_stop = mb_strpos($response, '</div>', $pos_stop+6);
        }
        $start_tiket_bloc = mb_substr($response, $pos_start+24, $pos_stop-$pos_start-24);

        $pos_start = mb_strpos($start_tiket_bloc, '<div class="time-default">', 0);
        $pos_stop = mb_strpos($start_tiket_bloc, '</div>', $pos_start+26);
        $start_tiket = trim(mb_substr($start_tiket_bloc, $pos_start+26, $pos_stop-$pos_start-26));


        // echo "

        // Отправление: ".$start_tiket."
        // ";

        // Время прибытия по билету $stop_tiket
        $pos_start = mb_strpos($response, '<div class="tline odd">', $pos_stop_global);
        $pos_stop = mb_strpos($response, '</div>', $pos_start+23);
        $pos_stop_global = $pos_stop;
        for($b=1;$b<7;$b++)
        {
            $pos_stop = mb_strpos($response, '</div>', $pos_stop+6);
        }
        $stop_tiket_bloc = mb_substr($response, $pos_start+23, $pos_stop-$pos_start-23);

        $pos_start = mb_strpos($stop_tiket_bloc, '<div class="time-default">', 0);
        $pos_stop = mb_strpos($stop_tiket_bloc, '</div>', $pos_start+26);
        $stop_tiket = trim(mb_substr($stop_tiket_bloc, $pos_start+26, $pos_stop-$pos_start-26));


        // echo "
        // Прибытие: ".$stop_tiket."
        // ";


        // Время в пути по билету $time_go_tiket
        $pos_start = mb_strpos($response, '<div class="tline even">', $pos_stop_global);
        $pos_stop = mb_strpos($response, '</div>', $pos_start+24);
        $pos_stop_global = $pos_stop;
        for($b=1;$b<3;$b++)
        {
            $pos_stop = mb_strpos($response, '</div>', $pos_stop+6);
        }
        $time_go_tiket_bloc = mb_substr($response, $pos_start+24, $pos_stop-$pos_start-24);

        $pos_start = mb_strpos($time_go_tiket_bloc, '<div class="two tap">', 0);
        $pos_stop = mb_strpos($time_go_tiket_bloc, '</div>', $pos_start+21);
        $time_go_tiket = trim(mb_substr($time_go_tiket_bloc, $pos_start+21, $pos_stop-$pos_start-21));
        $time_go_tiket = str_replace(' ч. ',':',$time_go_tiket);
        $time_go_tiket = str_replace(' мин.','',$time_go_tiket);
        // echo "
        // Время в пути: ".$time_go_tiket."
        // ";

        //Информация о станции отправления $station_from
        $pos_start = mb_strpos($response, '<div class="t-otkuda', $pos_stop_global);
        $pos_stop = mb_strpos($response, '</div>', $pos_start+20);
        $pos_stop_global = $pos_stop;
        for($b=1;$b<9;$b++)
        {
            $pos_stop = mb_strpos($response, '</div>', $pos_stop+6);
        }
        $station_from_block = mb_substr($response, $pos_start+20, $pos_stop-$pos_start-20);


        $pos_start = mb_strpos($station_from_block, 'bus-stantion-info-text', 0);
        $pos_stop = mb_strpos($station_from_block, ',', $pos_start+24);
        $station_from = trim(mb_substr($station_from_block, $pos_start+24, $pos_stop-$pos_start-24));// Название станции отправления

        $pos_start = mb_strpos($station_from_block, '>', $pos_stop);
        $pos_stop = mb_strpos($station_from_block, '</a>', $pos_start+1);
        $station_from_info = trim(mb_substr($station_from_block, $pos_start+1, $pos_stop-$pos_start-1));// Информация о станции отправления
        
        // //Информация о яндекс координатах станции отправления $station_yam_from
        // $pos_start = mb_strpos($station_from_block, 'll=', $pos_stop);
        // $pos_stop = mb_strpos($station_from_block, '&amp', $pos_start+3);
        // $station_yam_from = trim(mb_substr($station_from_block, $pos_start+3, null));// Информация о YAM станции отправления

        //Информация о станции прибытия $station_to
        $pos_start = mb_strpos($response, '<div class="t-kuda', $pos_stop_global);
        $pos_stop = mb_strpos($response, '</div>', $pos_start+20);
        $pos_stop_global = $pos_stop;
        for($b=1;$b<9;$b++)
        {
            $pos_stop = mb_strpos($response, '</div>', $pos_stop+6);
        }
        $station_to_block = mb_substr($response, $pos_start+20, $pos_stop-$pos_start-20);


        $pos_start = mb_strpos($station_to_block, 'bus-stantion-info-text', 0);
        $pos_stop = mb_strpos($station_to_block, ',', $pos_start+24);
        $station_to = trim(mb_substr($station_to_block, $pos_start+24, $pos_stop-$pos_start-24));// Название станции отправления

        $pos_start = mb_strpos($station_to_block, '>', $pos_stop);
        $pos_stop = mb_strpos($station_to_block, '</a>', $pos_start+1);
        $station_to_info = trim(mb_substr($station_to_block, $pos_start+1, $pos_stop-$pos_start-1));// Информация о станции отправления

        //Информация о ценах на билеты $full_price $child_price
        $pos_start = mb_strpos($response, 'Цены</div>', $pos_stop_global);
        $pos_stop = mb_strpos($response, '</div>', $pos_start+10);
        $pos_stop_global = $pos_stop;
        for($b=1;$b<13;$b++)
        {
            $pos_stop = mb_strpos($response, '</div>', $pos_stop+6);
        }
        $price_block = mb_substr($response, $pos_start+10, $pos_stop-$pos_start-10);

        $pos_start = mb_strpos($price_block, '<div class="field-item even">', 0);
        $pos_stop = mb_strpos($price_block, '</div>', $pos_start+29);
        $full_price = trim(mb_substr($price_block, $pos_start+29, $pos_stop-$pos_start-29));// Полная цена
        
        $pos_start = mb_strpos($price_block, '<div class="field-item even">', $pos_stop);
        $pos_stop = mb_strpos($price_block, '</div>', $pos_start+29);
        $child_price = trim(mb_substr($price_block, $pos_start+29, $pos_stop-$pos_start-29));// Детская цена


        // echo "
        // Рейс:".$url."
        // Рейс из ".$race_route[0]." в ".$race_route[$end_stat]."
        // билет из: ".$station_from."(".$station_from_info.") 
        // билет В: ".$station_to."(".$station_to_info.") 
        // Цена: ".$full_price."/".$child_price."
        // Отправление: ".$start_tiket."
        // Время в пути: ".$time_go_tiket."
        // Прибытие: ".$stop_tiket."
        // Рейс недоступен:".$test_desable."
        // Рейс доступен:".$test_enable."
        // ";
        $tiket=array(
            'station_from'=>$station_from,
            'station_from_info'=>$station_from_info,
            'station_to'=>$station_to,
            'station_to_info'=>$station_to_info,
            'time_dispatch'=>$start_tiket,
            'time_arival'=>$stop_tiket,
            'time_go'=>$time_go_tiket,
            'full_price'=>preg_replace('/[^0-9]/', '', $full_price),
            'child_price'=>preg_replace('/[^0-9]/', '',$child_price)
        );

        if($test_desable==1){
            $type_race="No active";
        }
        if($test_enable==1){
            $type_race="Active";
        }

        $route=$race_route[0]."-".$race_route[$end_stat];
        $arr_races[$type_race][$route]['route']=$race_route;
        $arr_races[$type_race][$route]['tickets'][]=$tiket;


        $i++;
        if ($i>99){
            //break;
            $iplus=$iplus+$i;
            echo "
            =========================
            Обработанно ".$iplus." из  ".count($arr_url)."
            (".date("Y-m-d H:i:s").")
            =========================
            ";

            echo "
            =========================
            Запись в файл Races_info.json (".date("Y-m-d H:i:s").")
            =========================";
            // if($iplus>10){
            //     $procces_races=file_get_contents('Races_info.json');
            //     $arr_procces_races=json_decode($procces_races);
                
                
            //     $arr_races_save=array_merge_recursive($arr_procces_races, $arr_races);
            //     echo json_encode($arr_races_save);
            //     file_put_contents('Races_info.json',json_encode($arr_races_save));
            //     break;
            // }else{
            //     file_put_contents('Races_info.json',json_encode($arr_races));
                    
            // }
            
            //$arr_races=array();

            $i=0;
        }
    }   
    file_put_contents('Races_info.json',json_encode($arr_races));
    //echo json_encode($arr_races);

}

?>