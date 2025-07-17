<?php
namespace App\Utils;


//
use Helpers\Helper;
use Helpers\Query;


//
class Utils
{





    



    public static function searchOrigenDestino($ruta_id, $origen_ciudad_id, $destino_ciudad_id, $fecha_hora_busqueda, $return_destinos_list = false){
        
        //
        $arr_destinos = Query::Multiple("select * from dbo.udf_SearchOrigenDestino(?, ?, ?, ?) t order by t.orden_num Asc", [
            $ruta_id,
            $origen_ciudad_id,
            $destino_ciudad_id,
            $fecha_hora_busqueda,
        ]);
        //Helper::printFull($arr_destinos); exit;

        //  && $res_totals && isset($res_totals['cant_destinos']) && $res_totals['cant_destinos'] > 0
        if ( $arr_destinos && is_array($arr_destinos) && count($arr_destinos) > 0 ){


            /*
            //
            $res_totals = Query::Single("
               Select
                   --
                   count(*) cant_destinos,
                   --
                   sum(t.distancia_metros) sum_distancia_metros,
                   --dbo.ConvertirDistancia(sum(t.distancia_metros)),
                   --
                   sum(t.duracion_mins) sum_duracion_mins,
                   --@sum_duracion_mins = dbo.ConvertirDuracion(sum(t.duracion_mins)),
                   --
                   sum(t.duracion_mins_espera) sum_duracion_mins_espera,
                   --dbo.ConvertirDuracion(sum(t.duracion_mins_espera)),
                   --
                   sum(t.precio_regular) sum_precio_regular,
                   sum(t.precio_prom_distancia) sum_precio_prom_distancia,
                   sum(t.precio_prom_ambos) sum_precio_prom_ambos,
                   --
                   sum(t.precio_final) sum_precio_final

                   from dbo.udf_SearchOrigenDestino(?, ?, ?, ?) t
               ", [
               $ruta_id,
               $origen_ciudad_id,
               $destino_ciudad_id,
               $fecha_hora_busqueda,
            ]);
            //Helper::printFull($res_totals); exit;
            //
            $sum_distancia_metros = $res_totals['sum_distancia_metros'];
            $sum_duracion_mins = $res_totals['sum_duracion_mins'];
            $sum_duracion_mins_espera = $res_totals['sum_duracion_mins_espera'];
            //
            $sum_precio_regular = $res_totals['sum_precio_regular'];
            $sum_precio_prom_distancia = $res_totals['sum_precio_prom_distancia'];
            $sum_precio_prom_ambos = $res_totals['sum_precio_prom_ambos'];
            $total = $res_totals['sum_precio_final'];
            */


            //
            $first_index_elem = 0;
            $res_origen = $arr_destinos[$first_index_elem];
            //Helper::printFull($res_origen); exit;


            //
            //$last_index_elem = ($res_totals['cant_destinos'] - 1);
            $last_index_elem = (count($arr_destinos) - 1);
            $res_destino = $arr_destinos[$last_index_elem];
            //Helper::printFull($res_destino); exit;


            // Si el orden del origen es 1 = (origen inicial);
            $is_origen_inicial = ( $res_origen["orden_num"] === 1 ) ? 1 : 0;
            //echo $is_origen_inicial; exit;


            //
            $destino_aplica_comision = 0;
            $destino_comision_monto = 0;
            //
            if ($res_destino && isset($res_destino["id"]) && $res_destino["aplica_comision"] && $res_destino["comision_monto"] > 0 ){
                $destino_aplica_comision = 1;
                $destino_comision_monto = $res_destino["comision_monto"];                
            }
            //echo "$destino_aplica_comision $destino_comision_monto"; exit;




            //
            $sum_distancia_metros = 0;
            $sum_duracion_mins = 0;
            $sum_duracion_mins_espera = 0;
            //
            $sum_precio_regular = 0;
            $sum_precio_prom_distancia = 0;
            $sum_precio_prom_ambos = 0;
            //
            $sub_total = 0;
            $ext_destino_precio = 0;
            $ext_origen_precio = 0;
            $total = 0;


            //
            foreach ($arr_destinos as $dato) {
                
                //
                $sum_distancia_metros += (float)$dato['distancia_metros'];
                $sum_duracion_mins += (float)$dato['duracion_mins'];
                $sum_duracion_mins_espera += (float)$dato['duracion_mins_espera'];
                
                //
                if ($dato['ext_destino_ciudad_id']){
                    $ext_origen_precio = (float)$dato['precio_final'];                    
                }
                else if ($dato['ext_origen_ciudad_id']){
                    $ext_destino_precio = (float)$dato['precio_final'];                    
                } else {
                    $sum_precio_regular += (float)$dato['precio_regular'];
                    $sum_precio_prom_distancia += (float)$dato['precio_prom_distancia'];
                    $sum_precio_prom_ambos += (float)$dato['precio_prom_ambos'];
                    $sub_total += (float)$dato['precio_final'];
                }
                //echo " *** $sub_total *** ";
            }
            //exit;
            //echo "$sum_distancia_metros, $sum_duracion_mins, $sum_duracion_mins_espera, $sum_precio_regular, $sum_precio_prom_distancia, $sum_precio_prom_ambos ------ $sub_total $ext_origen_precio, $ext_destino_precio, "; exit;


             /**
             * 
             * Si es origen inicial reseteamos los valores a Sin Calculo
             * 
             */
            if ($is_origen_inicial){
                //
                $sub_total = $res_destino['precio'];
                //
                $sum_precio_regular = 0;
                $sum_precio_prom_distancia = 0;
                $sum_precio_prom_ambos = 0;
            }
            //echo $sub_total; exit;


            //
            $total = ( $sub_total + $ext_origen_precio + $ext_destino_precio );


            //            
            if ( $total <= 0 ){
                $results['error'] = "no se encontraron destinos";
                return $results;
            }
            //echo "$sub_total $total" ; exit;


            //
            $ret_obj = [
                //
                "ruta_id" => $ruta_id,
                //
                "is_origen_inicial" => $is_origen_inicial,
                "origen_ciudad_id" => $origen_ciudad_id,
                "origen_orden_num" => $res_origen["orden_num"],
                "origen_ciudad_info" => $res_origen["ciudad_nombre"],
                "ext_destino_ciudad_id" => $res_origen["ext_destino_ciudad_id"],
                "ext_destino_info" => $res_origen["ext_destino_info"],
                "fecha_hora_salida" => $res_origen["fecha_hora_salida"],
                //
                "destino_ciudad_id" => $destino_ciudad_id,
                "destino_orden_num" => $res_destino["orden_num"],
                "destino_ciudad_info" => $res_destino["ciudad_nombre"],
                "ext_origen_ciudad_id" => $res_destino["ext_origen_ciudad_id"],
                "ext_origen_info" => $res_destino["ext_origen_info"],
                "fecha_hora_llegada" => $res_destino["fecha_hora_llegada"],
                //
                "sum_distancia_metros" => $sum_distancia_metros,
                "sum_duracion_mins" => $sum_duracion_mins,
                "sum_duracion_mins_espera" => $sum_duracion_mins_espera,
                //
                "sum_precio_regular" => $sum_precio_regular,
                "sum_precio_prom_distancia" => $sum_precio_prom_distancia,
                "sum_precio_prom_ambos" => $sum_precio_prom_ambos,
                "sub_total" => $sub_total,
                "ext_origen_precio" => $ext_origen_precio,
                "ext_destino_precio" => $ext_destino_precio,
                "total" => $total,
                //
                "destino_aplica_comision" => $destino_aplica_comision,
                "destino_comision_monto" => $destino_comision_monto
            ];

            //
            if ($return_destinos_list){
                $ret_obj['arr_destinos'] = $arr_destinos;
            }

            //
            return $ret_obj;
        }
        //
        $results['error'] = "no se encontraron destinos";
        //
        return $results;
    }









    public static function getCalcInfo($app_id, $passanger_age, $precio_salida){
        //
        $passanger_age_res = Query::Single("Select dbo.udf_GetAgeFromDate(?) passanger_age", [$passanger_age]);
        $passanger_age = ( $passanger_age_res && isset($passanger_age_res['passanger_age']) ) ? $passanger_age_res['passanger_age'] : 0;
        //
        $res = Query::Single("Select t.* From dbo.udf_CalcPasajeroMonto(?, ?, ?) t", [$app_id, $passanger_age, $precio_salida]);
        $res['passanger_age'] = $passanger_age;
        return $res;
    }



    public static function getExpiryMinutosLimite(){
        //
        return Query::SingleRetValue("expiry_minutos_limite", "Select dbo.getExpiryMinutosLimite() expiry_minutos_limite", []);
    }






}