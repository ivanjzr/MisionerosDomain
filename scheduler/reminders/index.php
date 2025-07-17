<?php


use \App\Charters\Charters;
use \App\Charters\ChartersDestinations;
//
use \App\Charters\ChartersAutobusesOcupacion;
use \App\Charters\ChartersChoferesOcupacion;
use App\Helpers\Helper;


try {





    //
    $api_mode = "dev";



    // log file
    $log_file = PATH_SCHEDULER.DS.'log.txt';


    // obtiene params para uso
    $charter_id = @$argv[3];
    $autobus_ocupacion_id = @$argv[4];
    //$param5 	= @$argv[5];

    //echo " $charter_id $autobus_ocupacion_id "; exit;
    /*-----------------------EJECUTA LA TAREA--------------------*/










    $charter_info = Charters::GetCharterInfoForReminder($charter_id);
    //var_dump($charter_info); exit;
    if ( isset($charter_info['error']) && $charter_info['error'] ){
        logContent($log_file, $charter_info['error']); exit;
    }
    $origin_name = $charter_info['origin_name'];
    $return_name = $charter_info['return_name'];
    $invoice_number = $charter_info['custom_invoice_number'];
    $customer_name = $charter_info['nombre_razon_social'];


    //
    $charter_destinations = ChartersDestinations::GetAll($charter_id);
    //var_dump($charter_destinations); exit;
    if ( isset($charter_destinations['error']) && $charter_destinations['error'] ){
        logContent($log_file, $charter_destinations['error']); exit;
    }
    //var_dump($charter_destinations); exit;
    //
    $str_destinations = "";
    foreach($charter_destinations as $destination) {
        //var_dump($destination); exit;
        $str_destinations .= "Destino: " . $destination['ciudad'] . ", " . $destination['estado'];
    }
    //$str_text = " recordatorio de salida de " . $customer_name . ", Origen: " . $origin_name . $str_destinations . ", Retorno: " . $return_name;
    //echo $str_text; exit;







    //
    $autobus_ocupacion = ChartersAutobusesOcupacion::GetRecordById($charter_id, $autobus_ocupacion_id);
    //var_dump($autobus_ocupacion); exit;
    if ( isset($autobus_ocupacion['error']) && $autobus_ocupacion['error'] ){
        logContent($log_file, $autobus_ocupacion['error']); exit;
    }
    //
    $autobus_clave = $autobus_ocupacion['autobus_clave'];
    $fecha_hora_inicio = DateTime::createFromFormat("Y-m-d H:i:s", $autobus_ocupacion['fecha_hora_inicio'])->format("d/m/Y");
    $fecha_hora_fin = DateTime::createFromFormat("Y-m-d H:i:s", $autobus_ocupacion['fecha_hora_fin'])->format("d/m/Y");





    //
    $choferes_results = ChartersChoferesOcupacion::GetAll($autobus_ocupacion['id']);
    //var_dump($choferes_results); exit;
    if ( isset($choferes_results['error']) && $choferes_results['error'] ) {
        logContent($log_file, $choferes_results['error']);
    }
    //
    foreach($choferes_results as $chofer) {
        //var_dump($chofer); exit;
        //
        $nombre = $chofer['nombre'];
        $phone_cc = $chofer['phone_cc'];
        $telefono = $chofer['telefono'];


        /*
         * ENVIA RECORDATORIO AL SR. GOMEZ
         * */
        $j_gomez_phone = "+19156139899";
        $send_msg2 =  "Recordatorio Misioneros a $nombre salida Del $fecha_hora_inicio Al $fecha_hora_fin para $customer_name $str_destinations ";
        Helper::SendSMS($j_gomez_phone, $send_msg2);


        //
        if ( $phone_cc && $telefono ){
            //var_dump($phone_cc.$telefono); exit;

            //
            $full_phone_number = $phone_cc . $telefono;

            //
            $send_msg =  "$nombre, Recordatorio Misioneros, salida Del $fecha_hora_inicio Al $fecha_hora_fin para $customer_name $str_destinations ";
            //echo $send_msg;

            //
            $the_results = Helper::SendSMS($full_phone_number, $send_msg);
            //var_dump($the_results); exit;
            if ( $the_results && isset($the_results['error'])){
                //
                $str_err_msg = "";
                if (strpos($the_results['error'], 'is not a mobile number') !== false) {
                    $str_err_msg = "El Telefono " . $phone_cc.$telefono . " no es un numero telefonico, favor de revisar";
                } else {
                    $str_err_msg = $the_results['error'];
                }
                //
                logContent($log_file, $str_err_msg);
            }
            if ( $the_results && isset($the_results['id']) ){
                //logContent($log_file, "Mensaje enviado con id: " . $the_results['id']);
            }
        }
        //
        else {
            //
            logContent($log_file, "No se envio SMS, Sin telefono o telefono invalido para $nombre en ocupacion con folio $autobus_ocupacion_id");
        }

    }
    //logContent($log_file, "---DONE OK"); exit;







    /*-----------------------ELIMINA LA TAREA--------------------*/
    // syncronize time zone to server
    $scheduler_timezone = "America/Cancun";

    //
    $sch = new scheduler($scheduler_timezone);


    //$scheduler_user_name = "DESKTOP-SB472M2\\HP12";
    //$scheduler_password = "Arxvht2Xy";
    $scheduler_user_name = "WIN-0MOTJ81DPK0\\Administrator";
    $scheduler_password = "=ECi6hCTewz";


    //
    $sch->auth($scheduler_user_name, $scheduler_password);


    // lista las tareas
    $sch->query('csv', true);

    //$sch->debug(); exit;
    $search_results = $sch->run();
    //var_dump($search_results);  exit;


    // si existe la tarea
    if ( $sch->helperFindTaskByName($search_results, $task_id) ){

        // Elimina la tarea
        $sch->delete($task_id);

        //$sch->debug(); exit;
        $delete_results = $sch->run();
        //var_dump($delete_results); exit;

        //
        if ( str_contains($delete_results, "valor a validar") ){
            echo "error en los argumentos";
        } else if ( str_contains($delete_results, "SUCCESS") ){
            echo "tarea '$task_id' eliminada exitosamente";
            logContent($log_file, "task with id $task_id removed succesfully");
        } else {
            echo "no se pudo eliminar la tarea";
        }

    }

    //
    else {
        echo "No existe la tarea $task_id" ;
    }






}
catch (Exception $e){
    $err_msg = "Error codigo: " . $e->getCode() . ", linea: " . $e->getLine() . ", mensaje:" . $e->getMessage() . ", trace" .$e->getTraceAsString();
    logContent($log_file, $err_msg);
}
finally {
    if ( isset($mysqldb) && $mysqldb){
        $mysqldb->closeConnection();
    }
}