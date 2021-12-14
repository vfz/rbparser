<?php

date_default_timezone_set('Europe/Moscow');

@$target=$argv[1];//htmlspecialchars($_GET['target']);


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

        while($id<438){
            $id_brake=$id+100;
            echo "
            ===================
            Обработанно ".$id." страниц (".date("Y-m-d H:i:s").")
            ===================
            ";
            if($id_brake>400)
            {
                $id_brake=437;
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
    
    foreach($arr_url as $url)
    {
        //"https://ros-bilet.ru".
        $response=getUrl($url);

        $test_desable = mb_substr_count($response, "В данный момент бронирование невозможно");
        $test_enable = mb_substr_count($response, "Для данного рейса доступны следующие виды брони");
        echo "
        Рейс:".$url."
        Рейс недоступен:".$test_desable."
        Рейс доступен:".$test_enable."
        ";
        $i++;
        if ($i>10){
            break;
        }
    }   

}

?>