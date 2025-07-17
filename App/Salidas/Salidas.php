<?php
namespace App\Salidas;





//
use App\Buses\Buses;
use App\Tasks\Tasks;
use App\Utils\Utils;
use Helpers\Helper;
use Helpers\Query;

class Salidas
{





    /*
        CADA QUE AGREGA UNA NUEVA RESERVACION ACTUALIZA SI HAN PASADO MAS DE 5 MINUTOS 
        Y SI ES ASI ELIMINAR EL REGISTRO PARA LIBERAR EL LUGAR EN LA TABLA "temp_salidas_ocupacion"
    */
    public static function liberaAsientosOcupadosTemporalmente($app_id){
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "all",
            "debug" => true,
            "stmt" => function(){
                return "{call usp_ActualizarOcupacion(?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "debug" => true,
            "params" => function() use($app_id){
                //
                return [
                    array($app_id, SQLSRV_PARAM_IN)
                ];
            }
        ]);
        //Helper::printFull($sp_res); exit;
        //
        return $sp_res;        
    }


    


    public static function getSalidaInfo($app_id, $ruta_id, $salida_id, $origen_ciudad_id, $destino_ciudad_id, $show_bus_info = false, $return_destinos_list = false){
        //echo " $app_id, $ruta_id, $salida_id, $origen_ciudad_id, $destino_ciudad_id "; exit;

        //
        $res_salida = Query::Single("
            Select 
                t.*
            from ViewSalidas t
                Where t.app_id = ?
                And t.ruta_id = ?
                And t.id = ?                                         ", [
            $app_id,
            $ruta_id,
            $salida_id
        ]);
        //Helper::printFull($res_salida); exit;

        //
        if ($res_salida && isset($res_salida['id'])){

            //
            $fecha_salida = $res_salida['fecha_salida'];
            $hora_salida = $res_salida['hora_salida'];
            //
            $iter_fecha_hora_salida = $fecha_salida->format("Y-m-d") . " " . $hora_salida->format("H:i");
            //echo $iter_fecha_hora_salida; exit;
            //
            $search_results = Utils::searchOrigenDestino($ruta_id, $origen_ciudad_id, $destino_ciudad_id, $iter_fecha_hora_salida, $return_destinos_list);
            //Helper::printFull($search_results); exit;

            //
            if ( $search_results && isset($search_results['ruta_id']) && $search_results['ruta_id'] ){

                //
                $search_results['id'] = $res_salida['id'];
                $search_results['autobus_id'] = $res_salida['autobus_id'];
                $search_results['autobus_clave'] = $res_salida['bus_clave'];

                //
                if ( $show_bus_info ){
                    //
                    if ($search_results['ext_destino_ciudad_id']){
                        $origen_orden_num = ( $search_results['origen_orden_num'] + 1 );
                    } else {
                        $origen_orden_num = $search_results['origen_orden_num'];
                    }
                    //
                    if ($search_results['ext_destino_ciudad_id']){
                        $destino_orden_num = ($search_results['destino_orden_num'] - 1);
                    } else {
                        $destino_orden_num = $search_results['destino_orden_num'];
                    }
                    //
                    $search_results['bus_results'] = Buses::getBusSeats($app_id, $res_salida['autobus_id'], $ruta_id, $salida_id, $origen_orden_num, $destino_orden_num);
                }

                //
                return $search_results;
            }
        }

        //
        return null;
    }









}