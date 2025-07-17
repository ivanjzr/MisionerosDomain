<?php
namespace Controllers\Utils;

//
use App\Salidas\Salidas;
use App\Tasks\Tasks;
use App\Utils\Utils;
use Controllers\BaseController;
//
use Helpers\Helper;
use Helpers\Query;
use Stripe\Util\Util;


//
class UtilsController extends BaseController
{




    //
    public function GetOrigenesList($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;
        //$app_id = $ses_data['id'];
        //$account_id = $ses_data['account_id'];        
        

        //
        $app_id = APP_ID_MISSIONEXPRESS;
        //
        $results = Query::Multiple("Select * from ViewOrigenes Where app_id = ?", [$app_id]);


        //
        return $response->withJson($results, 200);
    }






    //
    public function GetDestinosList($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;
        //$app_id = $ses_data['id'];
        //$account_id = $ses_data['account_id'];


        $app_id = APP_ID_MISSIONEXPRESS;


        //
        $query_string = $request->getQueryParam("q");
        $origen_ciudad_id = $args['origen_ciudad_id'];
        //echo $query_string; exit;


        //
        $sp_res = Query::StoredProcedure([
            "ret" => "all",
            "debug" => true,
            "stmt" => function(){
                return "{call usp_GetDestinos(?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "debug" => true,
            "params" => function() use(
                $app_id,
                $origen_ciudad_id,
                $query_string
            ){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($origen_ciudad_id, SQLSRV_PARAM_IN),
                    array($query_string, SQLSRV_PARAM_IN)
                ];
            }
        ]);
        //dd($sp_res); exit;
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }

        //
        return $response->withJson($sp_res, 200);
    }














    //
    public function PostBuscar($request, $response, $args) {
         
        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;
        //$app_id = $ses_data['id'];
        //$account_id = $ses_data['account_id'];
 



        $app_id = APP_ID_MISSIONEXPRESS;



        //
        $origen_ciudad_id = Helper::safeVar($request->getParsedBody(), 'origen_ciudad_id');
        $destino_ciudad_id = Helper::safeVar($request->getParsedBody(), 'destino_ciudad_id');
        $fecha_hora_busqueda = Helper::safeVar($request->getParsedBody(), 'fecha_hora_busqueda');



        //
        if ( !is_numeric($origen_ciudad_id) ){
            $results['error'] = "proporciona el origen";
            return $response->withJson($results, 200);
        }
        //
        if ( !is_numeric($destino_ciudad_id) ){
            $results['error'] = "proporciona el destino";
            return $response->withJson($results, 200);
        }
        // is_valid_date($str_date, $format = "Y-m-d H:i:s", $ret_formmated = "Y-m-d H:i:s"){
        $str_fecha_hora_busqueda = Helper::is_valid_date($fecha_hora_busqueda, "Y-m-d");
        if ( !$str_fecha_hora_busqueda ){
            $results['error'] = "proporciona la hora de salida";
            return $response->withJson($results, 200);
        }
        //echo " $origen_ciudad_id, $destino_ciudad_id, $str_fecha_hora_busqueda "; exit;


        //
        $res_salidas = Query::Multiple("
            Select 
                id,
                ruta_id,
                autobus_id,
                bus_clave,
                fecha_salida,
                hora_salida 
            from ViewSalidas t
                Where t.app_id = ?
                And CAST(fecha_salida AS DATE) >= CAST(? AS DATE)
                                                                     ", [
            $app_id,
            $str_fecha_hora_busqueda
        ]);
        //dd($res_salidas); exit;
        //
        $arr_results = [];
        //
        foreach($res_salidas as $idx => $item){
            //dd($item); exit;

            //
            $ruta_id = $item['ruta_id'];
            $fecha_salida = $item['fecha_salida'];
            $hora_salida = $item['hora_salida'];
            //
            $iter_fecha_hora_salida = $fecha_salida->format("Y-m-d") . " " . $hora_salida->format("H:i");
            //echo $iter_fecha_hora_salida; exit;

            //
            $buscar_results = Utils::searchOrigenDestino($ruta_id, $origen_ciudad_id, $destino_ciudad_id, $iter_fecha_hora_salida, false);
            //dd($buscar_results); exit;
            //
            $buscar_results['id'] = $item['id'];
            $buscar_results['autobus_clave'] = $item['bus_clave'];

            //
            if ($buscar_results && isset($buscar_results['ruta_id'])){
                array_push($arr_results, $buscar_results);
            }
        }
        //dd($arr_results); exit;



        //
        return $response->withJson($arr_results);
    }










    //
    public function PostCalcDob($request, $response, $args) {

       //
       $ses_data = $request->getAttribute("app");
       //dd($ses_data); exit;
       //$app_id = $ses_data['id'];
       //$account_id = $ses_data['account_id'];


       $app_id = APP_ID_MISSIONEXPRESS;



        //
        $dob = Helper::safeVar($request->getParsedBody(), 'dob');
        $ruta_id = Helper::safeVar($request->getParsedBody(), 'ruta_id');
        $salida_id = Helper::safeVar($request->getParsedBody(), 'salida_id');
        $origen_ciudad_id = Helper::safeVar($request->getParsedBody(), 'origen_ciudad_id');
        $destino_ciudad_id = Helper::safeVar($request->getParsedBody(), 'destino_ciudad_id');
        //echo " $ruta_id, $salida_id, $origen_ciudad_id, $destino_ciudad_id "; exit;




        //
        $passanger_age = Helper::is_valid_date($dob, "Y-m-d", "Y-m-d");
        //
        if ( !$passanger_age ){
            $results['error'] = "proporciona la fecha de nacimiento";
            return $response->withJson($results, 200);
        }
        //echo $passanger_age; exit;

        
        //
        $salida_info = Salidas::getSalidaInfo($app_id, $ruta_id, $salida_id, $origen_ciudad_id, $destino_ciudad_id);
        //dd($salida_info); exit;
        //
        if ( $salida_info && isset($salida_info['total']) && $salida_info['total'] > 0 ){
            //
            $calc_res = Utils::getCalcInfo($app_id, $passanger_age, $salida_info['total']);
            //dd($calc_res); exit;
            //
            return $response->withJson($calc_res, 200);
        }


        //
        return $response->withJson([], 200);
    }





}
