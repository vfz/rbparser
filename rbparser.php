<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('Europe/Moscow');
function get_my_str($str, $start_str, $stop_str, $minus_leght, $plus_leght, $offset_str){

    if(empty($offset_str)){
        $offset=0;
    }else{
        $offset= mb_strpos($str, $offset_str);
    }
    $pos_start = mb_strpos($str, $start_str, $offset);
     if($offset>0){
        $pos_start1=$pos_start-$minus_leght+$plus_leght;
     }else{
        $pos_start1=$pos_start-$minus_leght+$plus_leght;
     }
    

    if(empty($stop_str)){
        $pos_stop=0;
    }else{
        $pos_stop = mb_strpos($str, $stop_str, $offset); 
    }

    if($offset>0){
        $leght=$pos_stop-$pos_start1-5;
    }else{
        $leght=$pos_stop-$pos_start1;
    }

    if($pos_stop==0){
        $leght=NULL;
    }


    $result = mb_substr($str, $pos_start1, $leght);
    $ups="";
    if($pos_start1==0){
        return $ups;
    }else{
        return trim($result);
    }
}

function get_list_str_ticket($site){
    $arr_table=explode("<a href=\"/reys/", $site);
    foreach($arr_table AS $str_u){
        $pos_stop = mb_strpos($str_u, "\"", 0);
        $result[] = "/reys/".mb_substr($str_u, 0, $pos_stop );
        unset($result[0]);
    }
    return $result;
}

$id=0;

while($id<429){
    $id_brake=$id+100;

    if($id_brake>400)
    {
        $id_brake=428;
    }
    echo"        +-+-+-+-+-+-+-+-+-+-+-
    ";
    for($id; $id<=$id_brake; $id++ ){
        $defaults = array(
            CURLOPT_URL => "https://ros-bilet.ru/perevozchik/evrotrans-ip-yacunov-sp?field_city_tid=&field_city_arrival_tid=&page=0,".$id."/",
            //CURLOPT_URL => "https://oto-register.autoins.ru/oto/index.xhtml#oto=10787",
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
              //CURLOPT_POSTFIELDS =>"{\n  \"command\" : \"trip\",\n  \"from_id\" : \"".$select1."\",\n  \"to_id\" : \"".$select2."\",\n  \"date\" : \"".$date_start."\"\n}",
              CURLOPT_HTTPHEADER => array(
                "Content-Type: text/plain"
              ),
            );
            
            
            $curl = curl_init();
            
            curl_setopt_array($curl, $defaults);
            
            $response = curl_exec($curl);
            
            curl_close($curl);
            //$response1=iconv("windows-1251","UTF-8",$response);
            
            $list_url_tikets=get_list_str_ticket($response);

            echo json_encode($list_url_tikets);
            exit;

            $result = get_my_str($response1, '<h3 class="mt-5">Контактные данные</h3>', '<h3 class="mt-5">Информация об организации</h3>', 0, 0, '');
        
            $result_table = get_my_str($result, '<tbody>', '</tbody>', 0, 7, '');
        
            $arr_table=explode("<tr>", $result_table);
             $i=1;   
            foreach ($arr_table as $str_table){
        
                
                // 
                if($i>1){
                    $result_table_str = get_my_str($str_table, '<b>', '</b>', 0, 3, '');
                    $first_arr[$result_table_str]=get_my_str($str_table, '<td>', '</td>', 0, 4, '</b>');
                    // echo "строка №".$i. " Вот:".$str_table."
                    // ";
                }
        
                $i++;
            }
            $tel_str=$first_arr["Телефон"];
            $mail_str=$first_arr["Эл. почта"];
            $number_str=$first_arr["Номер в реестре"];
            $first_arr["Телефон"]=get_my_str($tel_str, '>', '</', 0, 1, '');
            $first_arr["Эл. почта"]=get_my_str($mail_str, '>', '</', 0, 1, '');
            $first_arr["Номер в реестре"]=get_my_str($number_str, '№', '', 0, 1, '');
            $first_arr["Телефон2"]=preg_replace("/[^0-9]/", '', $first_arr["Телефон"]);
            foreach ($first_arr as $key => $str){
                
               // $first_arr_and[$key]=str_replace( '</td>', '', $str );
                //$first_arr_and[$key]=str_replace( '</td', '', $str );
                //$first_arr_and[$key]=str_replace( '/td', '', $str );
            }
            
            if ($first_arr["Номер в реестре"]>0){
                if($first_arr["Статус"]=="Не действующ"){
                    $all_arr_desabled[$id]=$first_arr;
                }
        
                if($first_arr["Статус"]=="Действующ"){
                    $all_arr_eabled[$id]=$first_arr;
                }
            }
            
           
            //echo $result;
            echo $id." ";
        }
        echo"
        +-+-+-+-+-+-+-+-+-+-+-
    ";
        $markerid=$id-101;
        echo "
        Сброс от ".$markerid." до ".$id_brake." (".date("Y-m-d H:i:s").")
        Работают: ".count($all_arr_eabled)."
        "; 
        // var_dump($all_arr_eabled);
        
        if($id_brake<300){
            $string_for_file='"Номер в реестре";"Адрес";"Телефон";"Телефон2";"Эл. почта"
';
        }else{
            $string_for_file=file_get_contents('enabled_STO.csv');
        }
        
        foreach ($all_arr_eabled as $arr_sto){
                
             $string_for_file .=$arr_sto['Номер в реестре'].';"'.$arr_sto['Адрес'].'";'.$arr_sto['Телефон'].';'.$arr_sto['Телефон2'].';'.$arr_sto['Эл. почта'].'
        ';
        
         }

         file_put_contents('enabled_STO.csv', $string_for_file);
         unset($all_arr_eabled);
         //fclose($file);
         echo "
         =========================
         
         =========================
         НЕ Работают: ".count($all_arr_desabled)."
         ";
        // var_dump($all_arr_desabled);
        if($id_brake<300){
            $string_for_file='"Номер в реестре";"Адрес";"Телефон";"Телефон2";"Эл. почта"
';
        }else{
            $string_for_file=file_get_contents('desabled_STO.csv');
        }
        foreach ($all_arr_desabled as $arr_sto){
                  
            $string_for_file .=$arr_sto['Номер в реестре'].';"'.$arr_sto['Адрес'].'";'.$arr_sto['Телефон'].';'.$arr_sto['Телефон2'].';'.$arr_sto['Эл. почта'].'
';
               //fputcsv($file, $string_for_file, ";");
          
           }
        file_put_contents('desabled_STO.csv', $string_for_file);
        unset($all_arr_desabled);
        unset($first_arr);
        echo "
        =========================
        конец Сброса от ".$markerid." до ".$id_brake." (".date("Y-m-d H:i:s").")
        =========================
        **************************
           ";
           

}
echo "
           =========================
           конец обработки (".date("Y-m-d H:i:s").")
           =========================";
?>