<?php
namespace Controllers\Stores;


//
use App\Paths;
use App\Stores\Stores;
use Controllers\BaseController;
use Google\Service\Compute\Help;
use Helpers\Helper;
//
use App\Coupons\Coupons;
use Helpers\Query;
use Helpers\ValidatorHelper;



//
class StoresScheduleController extends BaseController
{









    //
    public function postUpdateScheduleInfo($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $sale_type_id = $ses_data['sale_type_id'];
        $store_id = $ses_data['id'];


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;



        // All Day
        $mon_type = $v->safeVar($body_data, 'mon_type');
        $tue_type = $v->safeVar($body_data, 'tue_type');
        $wed_type = $v->safeVar($body_data, 'wed_type');
        $thu_type = $v->safeVar($body_data, 'thu_type');
        $fri_type = $v->safeVar($body_data, 'fri_type');
        $sat_type = $v->safeVar($body_data, 'sat_type');
        $sun_type = $v->safeVar($body_data, 'sun_type');

        //
        $mon_open = $v->safeVar($body_data, 'mon_open');
        $mon_close = $v->safeVar($body_data, 'mon_close');
        //
        $tue_open = $v->safeVar($body_data, 'tue_open');
        $tue_close = $v->safeVar($body_data, 'tue_close');
        //
        $wed_open = $v->safeVar($body_data, 'wed_open');
        $wed_close = $v->safeVar($body_data, 'wed_close');
        //
        $thu_open = $v->safeVar($body_data, 'thu_open');
        $thu_close = $v->safeVar($body_data, 'thu_close');
        //
        $fri_open = $v->safeVar($body_data, 'fri_open');
        $fri_close = $v->safeVar($body_data, 'fri_close');
        //
        $sat_open = $v->safeVar($body_data, 'sat_open');
        $sat_close = $v->safeVar($body_data, 'sat_close');
        //
        $sun_open = $v->safeVar($body_data, 'sun_open');
        $sun_close = $v->safeVar($body_data, 'sun_close');






        // Lunes
        $str_mon_ad = null;
        $str_mon_open = null;
        $str_mon_close = null;
        //
        if ( $mon_type === "mon_horario" ){
            //
            if ( !($str_mon_open = Helper::is_valid_date($mon_open, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de apertura de Lunes"; return $response->withJson($results, 200);
            }
            if ( !($str_mon_close = Helper::is_valid_date($mon_close, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de cierrede Lunes"; return $response->withJson($results, 200);
            }
        }
        //
        else if ( $mon_type === "mon_24hrs" ){
            $str_mon_ad = 1;
        }
        //echo " $str_mon_open $str_mon_close $str_mon_ad"; exit;




        // Martes
        $str_tue_ad = null;
        $str_tue_open = null;
        $str_tue_close = null;
        //
        if ( $tue_type === "tue_horario" ){
            //
            if ( !($str_tue_open = Helper::is_valid_date($tue_open, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de apertura de Lunes"; return $response->withJson($results, 200);
            }
            if ( !($str_tue_close = Helper::is_valid_date($tue_close, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de cierrede Lunes"; return $response->withJson($results, 200);
            }
        }
        //
        else if ( $tue_type === "tue_24hrs" ){
            $str_tue_ad = 1;
        }
        //echo " $str_tue_open $str_tue_close $str_tue_ad"; exit;



        // Miercoles
        $str_wed_ad = null;
        $str_wed_open = null;
        $str_wed_close = null;
        //
        if ( $wed_type === "wed_horario" ){
            //
            if ( !($str_wed_open = Helper::is_valid_date($wed_open, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de apertura de Lunes"; return $response->withJson($results, 200);
            }
            if ( !($str_wed_close = Helper::is_valid_date($wed_close, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de cierrede Lunes"; return $response->withJson($results, 200);
            }
        }
        //
        else if ( $wed_type === "wed_24hrs" ){
            $str_wed_ad = 1;
        }
        //echo " $str_wed_open $str_wed_close $str_wed_ad"; exit;


        // Jueves
        $str_thu_ad = null;
        $str_thu_open = null;
        $str_thu_close = null;
        //
        if ( $thu_type === "thu_horario" ){
            //
            if ( !($str_thu_open = Helper::is_valid_date($thu_open, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de apertura de Lunes"; return $response->withJson($results, 200);
            }
            if ( !($str_thu_close = Helper::is_valid_date($thu_close, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de cierrede Lunes"; return $response->withJson($results, 200);
            }
        }
        //
        else if ( $thu_type === "thu_24hrs" ){
            $str_thu_ad = 1;
        }
        //echo " $str_thu_open $str_thu_close $str_thu_ad"; exit;


        // Viernes
        $str_fri_ad = null;
        $str_fri_open = null;
        $str_fri_close = null;
        //
        if ( $fri_type === "fri_horario" ){
            //
            if ( !($str_fri_open = Helper::is_valid_date($fri_open, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de apertura de Lunes"; return $response->withJson($results, 200);
            }
            if ( !($str_fri_close = Helper::is_valid_date($fri_close, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de cierrede Lunes"; return $response->withJson($results, 200);
            }
        }
        //
        else if ( $fri_type === "fri_24hrs" ){
            $str_fri_ad = 1;
        }
        //echo " $str_fri_open $str_fri_close $str_fri_ad"; exit;


        // Sabado
        $str_sat_ad = null;
        $str_sat_open = null;
        $str_sat_close = null;
        //
        if ( $sat_type === "sat_horario" ){
            //
            if ( !($str_sat_open = Helper::is_valid_date($sat_open, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de apertura de Lunes"; return $response->withJson($results, 200);
            }
            if ( !($str_sat_close = Helper::is_valid_date($sat_close, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de cierrede Lunes"; return $response->withJson($results, 200);
            }
        }
        //
        else if ( $sat_type === "sat_24hrs" ){
            $str_sat_ad = 1;
        }
        //echo " $str_sat_open $str_sat_close $str_sat_ad"; exit;


        // Domingo
        $str_sun_ad = null;
        $str_sun_open = null;
        $str_sun_close = null;
        //
        if ( $sun_type === "sun_horario" ){
            //
            if ( !($str_sun_open = Helper::is_valid_date($sun_open, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de apertura de Lunes"; return $response->withJson($results, 200);
            }
            if ( !($str_sun_close = Helper::is_valid_date($sun_close, "H:i", "H:i")) ){
                $results['error'] = "Se require la hora de cierrede Lunes"; return $response->withJson($results, 200);
            }
        }
        //
        else if ( $sun_type === "sun_24hrs" ){
            $str_sun_ad = 1;
        }
        //echo " $str_sun_open $str_sun_close $str_sun_ad"; exit;



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update stores_schedule 
                       Set
                           
						mon_ad = ?,
						tue_ad = ?,
						wed_ad = ?,
						thu_ad = ?,
						fri_ad = ?,
						sat_ad = ?,
						sun_ad = ?,
						
						mon_open = ?,
						mon_close = ?,
						
						tue_open = ?,
						tue_close = ?,
						
						wed_open = ?,
						wed_close = ?,
						
						thu_open = ?,
						thu_close = ?,
						
						fri_open = ?,
						fri_close = ?,
						
						sat_open = ?,
						sat_close = ?,
						
						sun_open = ?,
						sun_close = ?,
						
						datetime_updated = GETDATE()
                   
                      Where store_id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                //
                $str_mon_ad,
                $str_tue_ad,
                $str_wed_ad,
                $str_thu_ad,
                $str_fri_ad,
                $str_sat_ad,
                $str_sun_ad,
                //
                $str_mon_open,
                $str_mon_close,
                //
                $str_tue_open,
                $str_tue_close,
                //
                $str_wed_open,
                $str_wed_close,
                //
                $str_thu_open,
                $str_thu_close,
                //
                $str_fri_open,
                $str_fri_close,
                //
                $str_sat_open,
                $str_sat_close,
                //
                $str_sun_open,
                $str_sun_close,
                //
                $store_id
            ],
            "parse" => function($updated_rows, &$query_results) use($store_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$store_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }










}
