<?php
namespace Controllers\Appointments;

//

use Controllers\BaseController;

//
use App\App;
//
use Helpers\Helper;
use App\Empleados\EmpleadosSucursales;
//
use App\Promociones\Promociones;
use Helpers\Query;


//
class AppointmentsController extends BaseController
{


    public static $sql_date_format = "Y-m-d H:i:s";



    //
    public function ViewIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        return $this->container->php_view->render($response, 'admin/appointments/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "usd" => $request->getAttribute("usd")
        ]);
    }




    //
    public function ViewAppointmentsList($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        return $this->container->php_view->render($response, 'admin/appointments/index-list.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "usd" => $request->getAttribute("usd")
        ]);
    }


    //
    public function ViewEdit($request, $response, $args) {
        //
        $view_data = [
            "App" => new App(null, $request->getAttribute("ses_data")),
            'record_id' => $args['id']
        ];
        //
        return $this->container->php_view->render($response, 'admin/appointments/edit.phtml', $view_data);
    }







    //
    public static function configSearchClause($search_value, $resource_id, $location_id, $status_id, $date_from, $date_to){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.name like '%$search_value%' )
                    )";
        }

        //
        if ( is_numeric($resource_id) ){
            //
            $search_clause .= " And t.resource_id = $resource_id ";
        }

        //
        if ( is_numeric($location_id) ){
            //
            $search_clause .= " And t.sucursal_id = $location_id ";
        }

        //
        if ( is_numeric($status_id) ){
            //
            $search_clause .= " And t.status_id = $status_id ";
        }

        //
        if ( $date_from && $date_to ){
            //
            $search_clause .= " And (CAST(t.start_datetime AS DATE) >= '$date_from' AND CAST(t.start_datetime AS DATE) <= '$date_to') ";
        }


        //
        //echo $search_clause; exit;
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "v_appointments";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause(
            trim($request->getQueryParam("search")['value']), 
            trim($request->getQueryParam("rid")), 
            $request->getQueryParam("lid"), 
            $request->getQueryParam("sid"),
            $request->getQueryParam("date_from"),
            $request->getQueryParam("date_to"),
        );
        //echo $search_clause; exit;
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];




        



        //
        $results = Query::PaginateRecordsV2(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            $request->getQueryParam("draw"),
            [

                /*
                 * Count Statement
                 * */
                "deb" => true,
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? {$search_clause}";
                },
                //
                "cnt_params" => array(
                    $app_id
                ),

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $table_name, $search_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.*
                                        
                                        From {$table_name} t
                                      
                                            Where t.app_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                //
                "params" => array(
                    $app_id
                ),

                //
                "parseRows" => function(&$row) use($app_id){
                    //dd($row); exit;
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public function GetPaginatePrimeReactCustomerAppointments($request, $response, $args) {

        //
        $table_name = "v_appointments";

        //
        $order_field = "id";
        $order_direction = "Desc";

        //
        $search_clause = "";

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];
        $customer_id = $ses_data['id'];
        //echo " $customer_id "; exit;

        //
        $start_record = (int)$request->getQueryParam("s");
        $num_records = (int)$request->getQueryParam("n");
        //echo " $start_record $num_records "; exit;



        //
        $results = Query::ScrollPaginate(
            $start_record,
            $num_records,
            [
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.customer_id = ? {$search_clause}";
                },
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $table_name, $search_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.*
                                      
                                        From {$table_name} t
                                                
                                            Where t.app_id = ?
                                            And t.customer_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                "params" => array(
                    $app_id,
                    $customer_id
                ),
                "parseRows" => function(&$row){
                    //dd($row); exit;
                    //
                }
            ]
        );


        //dd($results); exit;
        return $response->withJson($results, 200);
    }







    
        

    //
    public function PostGetLocationResources($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        

        //
        $user_type = (isset($ses_data['t']) && $ses_data['t']) ? $ses_data['t'] : null;
        //
        if (!$user_type){
            $results['error'] = "not a valid user type";
            return $response->withJson($results, 400);
        }



        /*
        //
        $allowed_sucursal_ids = [];
        //
        if ($user_type === "b" ){
            //
            $user_id = (int)$ses_data['id'];
            $user_is_admin = $ses_data['is_admin'];
            //
            if ($user_is_admin){
                //
                $allowed_sucursal_ids = Query::Multiple("Select * from sucursales Where account_id = ? And active = 1", [$account_id]);
            } else {
                //
                $allowed_sucursal_ids =  EmpleadosSucursales::GetAll($account_id, $user_id);
            }
        }
        // Si es front se trae todas las sucursales
        else if ($user_type === "f" ){            
            //
            $allowed_sucursal_ids = Query::Multiple("Select * from sucursales Where account_id = ? And active = 1", [$account_id]);
        }
        //dd($allowed_sucursal_ids); exit;
        $ids_string = implode(',', array_column($allowed_sucursal_ids, 'id'));
        //echo $ids_string; exit;
        */




    
        //
        $cal_date = Helper::safeVar($request->getParsedBody(), 'cal_date');
        $location_id = $args['location_id'];
        //echo " $cal_date $location_id"; exit;



        //
        $whereClause = "Where app_id = ? And sucursal_id = ? And active = 1";
        $params = [$app_id, $location_id];
        //
        $resource_id = null;
        if ( isset($args['resource_id']) && $args['resource_id'] ){
            $resource_id = $args['resource_id'];
            $whereClause .= " And id = ?";
            $params[] = $resource_id;
        }
        //echo $resource_id; exit;

        $resources = Query::Multiple("Select 
            id, 
            name as title,
            working_hours_id
        from calendar_resources $whereClause", $params);
        //dd($results);




        // Obtener el día de la semana del cal_date (1=Lunes, 7=Domingo)
        $date_obj = new \DateTime($cal_date);
        $calendar_date_fmt = $date_obj->format('Y-m-d');
        $week_day = $date_obj->format('N');
        //dd($date_obj);
        //echo $week_day; exit;
        //echo $calendar_date_fmt; exit;

       
        
        
        

        
        //
        $min_hour = null;
        $max_hour = null;


        // Obtener min/max directamente desde la BD
        $working_hours_ids = array_column($resources, 'working_hours_id');

        if (!empty($working_hours_ids)) {
            $working_hours_ids_placeholders = str_repeat('?,', count($working_hours_ids) - 1) . '?';
            
            $time_range = Query::Single("
                SELECT 
                    MIN(hora_inicio) as min_hour,
                    MAX(hora_fin) as max_hour
                FROM working_hours_list 
                WHERE app_id = ? 
                AND working_hours_id IN ($working_hours_ids_placeholders)
                AND week_day = ? 
                AND activo = 1
            ", array_merge([$app_id], $working_hours_ids, [$week_day]));
            
            $min_hour = $time_range && $time_range['min_hour'] ? $time_range['min_hour']->format("H:i") : null;
            $max_hour = $time_range && $time_range['max_hour'] ? $time_range['max_hour']->format("H:i") : null;
            
        } else {
            $min_hour = null;
            $max_hour = null;
        }



        //
        if ($min_hour === null || $max_hour === null) {
            $min_hour = "08:00";
            $max_hour = "18:00";
        }


        // Retornar eventos con información de horarios
        return $response->withJson([
            'resources' => $resources,
            'slot_min_time' => $min_hour . ':00', // Formato HH:mm:ss
            'slot_max_time' => $max_hour . ':00',
        ], 200);
    }



    //
    public function GetEventInfo($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $customer_or_user_id = $ses_data['id'];
    

        //
        $is_front_page = $request->getQueryParam("fp", null);
        //echo $is_front_page; exit;


        //
        $location_id = $args['location_id'];
        $resource_id = $args['resource_id'];
        $appointment_id = $args['appointment_id'];
        //
        //echo " $app_id, $location_id, $resource_id, $appointment_id "; exit;


        //
        $appointment_info = Query::Single("Select * From v_appointments where app_id = ? And sucursal_id = ? and resource_id = ? And id = ?", 
        [
            $app_id, 
            $location_id,
            $resource_id,
            $appointment_id
        ]);
        //dd($appointment_info);
        if ( !($appointment_info && isset($appointment_info['id'])) ){
            $results['error'] = "no se encontro la cita";
            return $response->withJson($results, 200);
        }

        $cita_customer_id = (int)$appointment_info['customer_id'];

        

        /**
         * 
         * Si es para mostrar en front-page (reactjs page)
         */
        //echo " $customer_or_user_id $cita_customer_id "; exit;
        if ( $is_front_page && ($customer_or_user_id !== $cita_customer_id) ){
            return $response->withJson([], 200);
        }

        //
        return $response->withJson($appointment_info, 200);
    }



    //
    public function GetEventInfoWithAppointmentIdOnly($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
    
        //
        $appointment_id = $args['appointment_id'];
        //echo " $app_id, $appointment_id "; exit;


        //
        $appointment_info = Query::Single("Select * From v_appointments where app_id = ? And id = ?", 
        [
            $app_id, 
            $appointment_id
        ]);
        //dd($appointment_info);
        if ( !($appointment_info && isset($appointment_info['id'])) ){
            $results['error'] = "no se encontro la cita";
            return $response->withJson($results, 200);
        }

        //
        return $response->withJson($appointment_info, 200);
    }






    //
    public function GetInitialResources($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        

        //
        $user_type = (isset($ses_data['t']) && $ses_data['t']) ? $ses_data['t'] : null;
        //
        if (!$user_type){
            $results['error'] = "not a valid user type";
            return $response->withJson($results, 400);
        }
        
        


        /**
         * 
         * Trae configuraciones para vista de calendario
         */
        // resourceTimeGridDay y timeGridWeek
        $config_initial_view = null;
        $calendar_views = [];
        //
        $calendar_config = Query::Single("Select default_calendar_view_id from calendar_config Where app_id = ?", [$app_id]);
        //dd($calendar_config); 
        if ($calendar_config && isset($calendar_config['default_calendar_view_id'])){

            //
            $default_calendar_view_id = $calendar_config['default_calendar_view_id'];            
            //
            $cat_calendar_views = Query::Multiple("Select * from sys_cat_calendar_views Where active = 1");
            //dd($cat_calendar_views); 
            foreach($cat_calendar_views as $indexs => $calendar_view){
                //dd($calendar_view); 
                if ($calendar_view['id'] == $default_calendar_view_id){
                    $calendar_view['selected'] = "selected";
                    $config_initial_view = $calendar_view;
                }
                //
                unset($calendar_view['active']);
                array_push($calendar_views, $calendar_view);
            }

        }


        //
        $locations_res = [];
        $user_id = null;
        $customer_id = null;


        /**
         * 
         * Si es backend se trae las locations/sucurasles segun el user
         */
        if ($user_type === "b" ){
            //
            $user_id = (int)$ses_data['id'];
            $user_is_admin = $ses_data['is_admin'];
            //
            if ($user_is_admin){
                //
                $locations_res = Query::Multiple("Select * from sucursales Where account_id = ? And active = 1", [$account_id]);
            } else {
                //
                $locations_res =  EmpleadosSucursales::GetAll($account_id, $user_id);
            }
        }

        /**
         * 
         * Si es front se trae todas las sucursales
         */
        else if ($user_type === "f" ){            
            //
            $locations_res = Query::Multiple("Select * from sucursales Where account_id = ? And active = 1", [$account_id]);
        }


        



        /**
         * 
         * Trae el catalogo con informacion de estados
         */
        //
        $cal_status_res = Query::Multiple("Select * from sys_cat_appointments_status Where active = 1", [$app_id]);

        


        /**
         * 
         * Se trae la duracion inicial de slots
         */
        $duracion_res = Query::SingleRetValue("min_servicio_duracion_minutos", "
            select 
                min(servicio_duracion_minutos) as min_servicio_duracion_minutos
            from products 
                Where app_id = ?
                And tipo_producto_servicio = 's'
                And (servicio_duracion_minutos is not null and servicio_duracion_minutos > 0)
                And active = 1
        ", [$app_id]);
        //dd($duracion_res); exit;
        //
        $slot_min_duration = "00:15:00"; // valor por defecto
        //
        if ($duracion_res) {
            $hours = floor($duracion_res / 60);
            $minutes = $duracion_res % 60;
            $slot_min_duration = sprintf("%02d:%02d:00", $hours, $minutes);
        }
        //echo $slot_min_duration; exit;


        


        //
        return $response->withJson([
            "calendar_views" => $calendar_views,
            "config_initial_view" => $config_initial_view,
            'slot_min_duration' => $slot_min_duration,
            "locations" => $locations_res,
            "cal_status" => $cal_status_res,
        ], 200);
    }






    

    /**
     * 
     * Nos traemos la disponbilidad del recurso
     */
    public static function getResurceAvaiability($app_id, $resource_id, $working_hours_id, $week_day, $calendar_date_fmt){
        //
        $arr_avail = [];
        //
        $working_hours = Query::Multiple("
            SELECT * 
            FROM working_hours_list 
            WHERE app_id = ? 
            AND working_hours_id = ? 
            AND week_day = ? 
            AND activo = 1
        ", [$app_id, $working_hours_id, $week_day]);
        //dd($working_hours); exit;
        
        //                
        foreach($working_hours as $working_hour){
            //dd($working_hour); exit;
            
            // Obtener horas para min/max
            $hora_inicio = $working_hour['hora_inicio']->format("H:i");
            $hora_fin = $working_hour['hora_fin']->format("H:i");
            
            //
            array_push($arr_avail, [
                "resourceId" => $resource_id,
                "title" => "",
                "start" => $calendar_date_fmt . " " . $hora_inicio, 
                "end" => $calendar_date_fmt . " " . $hora_fin,
                "backgroundColor" => "rgba(76, 175, 80, 0.3)",
                "borderColor" => "rgba(76, 175, 80, 0.6)", 
                "textColor" => "transparent",
                "display" => "background", /** NOTA: AL PONER BACKGROUND NO SE DETECTARA EL EVENTO eventClick DE FULL CALENDAR SINO SOLO EL EVENTO select */
                "extendedProps" => [
                    "resource_id" => $resource_id,
                    "title" => "availavility",
                    "type" => "avail",
                    "hide_time_title" => 1
                ]
            ]);
        }
        //
        return $arr_avail;
    }




    /**
     * 
     * Buscamos excepciones de citas del recurso
     */
    public static function getResurceScheduleExceptions($app_id, $location_id, $resource_id, $today_obj, $iter_date, $week_day, $calendar_date_fmt, $calendar_date_fmt_year){
        //
        $arr_sch_ex = [];
        //
        $excepcioes_horarios = Query::Multiple("
            SELECT * 
                FROM v_resources_schedule_exceptions
            WHERE app_id = ?
            And sucursal_id = ?
            AND resource_id = ?
            And (
                excepcion_tipo = 'solo_dia' Or excepcion_tipo = 'solo_dia_con_horario'
            )
            AND recurrencia_tipo = ''
            And CAST(fecha AS DATE) = ?
        ", [$app_id, $location_id, $resource_id, $calendar_date_fmt]);
        //echo $resource_id; exit;
        //dd($excepcioes_horarios); exit;
        if ($excepcioes_horarios && isset($excepcioes_horarios['error'])){
            $results['error'] = $excepcioes_horarios['error'];
            return $response->withJson($results, 200);
        }
        //
        if ($excepcioes_horarios && is_array($excepcioes_horarios)){
            //
            foreach($excepcioes_horarios as $item){
                //dd($item); exit;

                //
                $extended_props = [
                    "resource_id" => $resource_id,
                    "title" => $item['title'],
                    "type" => "sch_ex",
                    "ex_type" => $item['excepcion_tipo'],
                    "recur_type" => false,
                ];

                
                //
                if ( $item['excepcion_tipo'] === "solo_dia" ){
                    // 
                    $start_datetime = $item['fecha']->format("Y-m-d") . " 00:00:00";
                    $end_datetime = $item['fecha']->format("Y-m-d") . " 23:29:00";
                    
                    //
                    $extended_props['hide_time_title'] = 1;

                    // 
                    array_push($arr_sch_ex, [
                        "id" => "ex-" . $item['id'],
                        "title" => $item['motivo'],
                        "start" => $start_datetime,
                        "end" => $end_datetime,
                        "resourceId" => $item['resource_id'],
                        "backgroundColor" => "#ddd",
                        "textColor" => "#00f",
                        "extendedProps" => $extended_props
                    ]);
                }
                //
                else if ( $item['excepcion_tipo'] === "solo_dia_con_horario" ){
                    //dd($item); exit;

                    // 
                    $start_datetime = $item['fecha']->format("Y-m-d") . " " . $item['hora_inicio']->format("H:i");
                    $end_datetime = $item['fecha']->format("Y-m-d") . " " . $item['hora_fin']->format("H:i");
                    
                    // 
                    array_push($arr_sch_ex, [
                        "id" => "ex-" . $item['id'],
                        "title" => $item['motivo'],
                        "start" => $start_datetime,
                        "end" => $end_datetime,
                        "resourceId" => $item['resource_id'],
                        "backgroundColor" => "#ddd",
                        "textColor" => "#00f",
                        "extendedProps" => $extended_props
                    ]);


                }
            }
        }

        //
        $excepcioes_horarios = Query::Multiple("
            SELECT * 
                FROM v_resources_schedule_exceptions
            WHERE app_id = ?
            And sucursal_id = ?
            AND resource_id = ?
            And ? >= CAST(fecha AS DATE)
            And (
                excepcion_tipo = 'solo_dia' Or excepcion_tipo = 'solo_dia_con_horario'
            )
            And recurrencia_tipo = 'diaria'
        ", [$app_id, $location_id, $resource_id, $calendar_date_fmt]);
        // calendar_date_fmt
        //dd($excepcioes_horarios); exit;

        if ($excepcioes_horarios && isset($excepcioes_horarios['error'])){
            $results['error'] = $excepcioes_horarios['error'];
            return $response->withJson($results, 200);
        }
        //
        if ($excepcioes_horarios && is_array($excepcioes_horarios)){
            //
            foreach($excepcioes_horarios as $item){
                //dd($item); exit;
                //
                $extended_props_2 = [
                    "resource_id" => $resource_id,
                    "title" => $item['motivo'],
                    "type" => "sch_ex",
                    "ex_type" => $item['excepcion_tipo'],
                    "recur_type" => $item['recurrencia_tipo'],
                ];
                //
                $ex_fecha_inicio = $item['fecha'];
                //dd($ex_fecha_inicio);
                //
                if ($today_obj >= $ex_fecha_inicio){
                    
                    //
                    $arr_recurrencia_days = [];
                    if($item['recurrencia_mon']) array_push($arr_recurrencia_days, 1);
                    if($item['recurrencia_tue']) array_push($arr_recurrencia_days, 2);
                    if($item['recurrencia_wed']) array_push($arr_recurrencia_days, 3);
                    if($item['recurrencia_thu']) array_push($arr_recurrencia_days, 4);
                    if($item['recurrencia_fri']) array_push($arr_recurrencia_days, 5);
                    if($item['recurrencia_sat']) array_push($arr_recurrencia_days, 6);
                    if($item['recurrencia_sun']) array_push($arr_recurrencia_days, 7);
                    //dd($arr_recurrencia_days);

                    //
                    $aplica_recurrencia = false;
                    //
                    if ($item['recurrencia_fecha_limite']) {
                        $aplica_recurrencia = ($iter_date < $item['recurrencia_fecha_limite']) && in_array($week_day, $arr_recurrencia_days);
                    } else {
                        $aplica_recurrencia = in_array($week_day, $arr_recurrencia_days);
                    }

                    //
                    if ($aplica_recurrencia) {
                        //
                        $start_datetime = $calendar_date_fmt . " 00:00:00";
                        $end_datetime = $calendar_date_fmt . " 23:29:00";
                        
                        //
                        if ($item['excepcion_tipo'] === "solo_dia") {
                            $extended_props_2['hide_time_title'] = 1;
                        }
                        else if ($item['excepcion_tipo'] === "solo_dia_con_horario") {
                            $start_datetime = $calendar_date_fmt . " " . $item['hora_inicio']->format("H:i");
                            $end_datetime = $calendar_date_fmt . " " . $item['hora_fin']->format("H:i");
                        }

                        // 
                        array_push($arr_sch_ex, [
                            "id" => "ex-" . $item['id'],
                            "title" => $item['motivo'],
                            "start" => $start_datetime,
                            "end" => $end_datetime,
                            "resourceId" => $item['resource_id'],
                            "backgroundColor" => "#ddd",
                            "textColor" => "#00f",
                            "extendedProps" => $extended_props_2
                        ]);
                    }
                }
            }
        }

        //
        $excepcioes_horarios = Query::Multiple("
            SELECT * 
                FROM v_resources_schedule_exceptions
            WHERE app_id = ?
            And sucursal_id = ?
            AND resource_id = ?
            And (
                excepcion_tipo = 'solo_dia' Or excepcion_tipo = 'solo_dia_con_horario'
            )
            And recurrencia_tipo = 'anual'
        ", [$app_id, $location_id, $resource_id]);
        // calendar_date_fmt
        //dd($excepcioes_horarios); exit;

        if ($excepcioes_horarios && isset($excepcioes_horarios['error'])){
            $results['error'] = $excepcioes_horarios['error'];
            return $response->withJson($results, 200);
        }
        //
        if ($excepcioes_horarios && is_array($excepcioes_horarios)){
            //
            foreach($excepcioes_horarios as $item){
                //dd($item); exit;
                
                //
                $extended_props_2 = [
                    "resource_id" => $resource_id,
                    "title" => $item['motivo'],
                    "type" => "sch_ex",
                    "ex_type" => $item['excepcion_tipo'],
                    "recur_type" => $item['recurrencia_tipo'],
                ];
                
                //
                $fecha_excepcion = $calendar_date_fmt_year."-".$item['fecha']->format("m-d");
                
                //echo "$calendar_date_fmt $fecha_excepcion"; exit;
                if ( $calendar_date_fmt === $fecha_excepcion ){

                    //
                    $start_datetime = $fecha_excepcion . " 00:00:00";
                    $end_datetime = $fecha_excepcion . " 23:29:00";
                    
                    //
                    if ( $item['excepcion_tipo'] === "solo_dia" ){
                        $extended_props_2['hide_time_title'] = 1;
                    }
                    //
                    else if ( $item['excepcion_tipo'] === "solo_dia_con_horario" ){
                        //dd($item); exit;
                        $start_datetime = $fecha_excepcion . " " . $item['hora_inicio']->format("H:i");
                        $end_datetime = $fecha_excepcion . " " . $item['hora_fin']->format("H:i");
                    }

                    // 
                    array_push($arr_sch_ex, [
                        "id" => "ex-" . $item['id'],
                        "title" => $item['motivo'],
                        "start" => $start_datetime,
                        "end" => $end_datetime,
                        "resourceId" => $item['resource_id'],
                        "backgroundColor" => "#ddd",
                        "textColor" => "#00f",
                        "extendedProps" => $extended_props_2
                    ]);
                }
                

            }
        }



        //
        return $arr_sch_ex;
    }

    


    /**
     *  
     * Buscamos las citas  del recurso en base a la fecha
     */
    public static function getResourcesAppointments($app_id, $location_id, $resource_id, $calendar_date_fmt, $ignore_cancelled, $is_front_page, $customer_id = null){
        //
        $arr_appmnts = [];

        //
        $str_where_cancelled = "";
        //
        if ($ignore_cancelled){
            $str_where_cancelled = "And status_id != " . CITA_STATUS_CANCELLED;
        }
        //echo $str_where_cancelled; exit;
        
        //
        $appointments = Query::Multiple("
            SELECT * 
            FROM v_appointments
            WHERE app_id = ? 
            And sucursal_id = ? 
            AND resource_id = ?
            AND (
                CAST(start_datetime AS DATE) = ? Or CAST(end_datetime AS DATE) = ?
            )
            {$str_where_cancelled}
        ", [$app_id, $location_id, $resource_id, $calendar_date_fmt, $calendar_date_fmt]);
        //dd($appointments);
        if ($appointments && isset($appointments['error'])){
            $results['error'] = $appointments['error'];
            return $response->withJson($results, 200);
        }
        //
        if ($appointments && is_array($appointments)){
            //
            foreach($appointments as $item){

                //
                $cita_customer_id = (int)$item['customer_id'];

                //
                $extended_props = [
                    "type" => "appmnt",
                    "location_id" => $item['sucursal_id'],
                    "resource_id" => $item['resource_id'],
                    "service_id" => $item['service_id'],
                    "customer_id" => $cita_customer_id,
                    "title" => $item['title'],
                    "status_id" => $item['status_id'],
                    "status" => $item['status'],
                    "status_color" => $item['status_color'],
                    "notes" => $item['notes']
                ];

                //var_dump("Status color for event " . $item['id'] . ": ", $item['status_color']); exit;
                $appointment_params = [
                    "id" => $item['id'],
                    "start" => $item['start_datetime']->format(self::$sql_date_format),
                    "end" => $item['end_datetime']->format(self::$sql_date_format),
                    "resourceId" => $item['resource_id'],
                    "extendedProps" => $extended_props
                ];

                /**
                 * 
                 * Si es para mostrar en front-page (reactjs page)
                 */
                if ( $is_front_page ){

                    // es el mismo customer
                    if ( $customer_id === $cita_customer_id ){
                        //
                        $appointment_params['title'] = $item['title'];
                        $appointment_params['backgroundColor'] = $item['status_color'];
                        $appointment_params['extendedProps']['isOwner'] = true;
                    } 
                    // es otro customer
                    else {
                        //
                        $appointment_params['title'] = 'reservado';
                        $appointment_params['backgroundColor'] = '#ddd';
                        $appointment_params['textColor'] = '#666';
                    }
                    
                    
                } 
                /**
                 * 
                 * Si es para mostrar en admin
                 */
                else {
                    //
                    $appointment_params['title'] = $item['title'];
                    $appointment_params['backgroundColor'] = $item['status_color'];
                    //$appointment_params['borderColor'] = '#333';
                }

                // 
                array_push($arr_appmnts, $appointment_params);

            }
        }
        //
        return $arr_appmnts;
    }


    //
    public function PostGetLocationResourcesEvents($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        

        //
        $user_type = (isset($ses_data['t']) && $ses_data['t']) ? $ses_data['t'] : null;
        //
        if (!$user_type){
            $results['error'] = "not a valid user type";
            return $response->withJson($results, 400);
        }
        


        //
        $is_front_page_user = false;
        $user_id = null;
        $customer_id = null;

        // Si es usuario tipo front-end
        if ($user_type === "f"){
            $is_front_page_user = true;
            $customer_id = (int)$ses_data['id'];
        }
        



    
        //
        $cal_date = Helper::safeVar($request->getParsedBody(), 'cal_date');
        $cal_type = Helper::safeVar($request->getParsedBody(), 'cal_type');
        $resources_ids = Helper::safeVar($request->getParsedBody(), 'resources_ids');
        $location_id = $args['location_id'];        
        //dd($resources_ids); exit;
        // debug resources
        //$resources_ids = [3,4];

        //
        if ( !$resources_ids || (is_array($resources_ids) && count($resources_ids) === "0") ){
            $results['error'] = "no resources provided";
            return $response->withJson($results, 200);
        }

                
        // Crear placeholders para los parámetros (?, ?, ?)
        $placeholders = str_repeat('?,', count($resources_ids) - 1) . '?';

        $resources = Query::Multiple("
            SELECT 
                id, 
                name as title,
                working_hours_id

            FROM calendar_resources 
            WHERE app_id = ? 
            AND sucursal_id = ? 
            AND active = 1 
            AND id IN ($placeholders)
        ", array_merge([$app_id, $location_id], $resources_ids));
        //dd($resources); exit;



        //
        $today_obj = new \DateTime('today');
        $date_obj = new \DateTime($cal_date);        
        //dd($date_obj);

        /**
         * 
         * Si es semanal generamos el end date en base al start date + 7 dias
         */
        if ($cal_type==="week"){
            $add_days = "+6 days";            
        } else {
            $add_days = "+0 days";
        }
        
        //
        $end_date_obj = (clone $date_obj)->modify($add_days);
        $iter_date = clone $date_obj;
        //dd($end_date_obj);

        //
        $arr_avail = [];
        $arr_sch_ex = [];
        $arr_appmnts = [];

        //
        $ignore_cancelled = ($is_front_page_user) ? true : false;

        // Iterar el rango de fechas        
        while ($iter_date <= $end_date_obj) {

            //
            $calendar_date_fmt = $iter_date->format('Y-m-d');
            $week_day = $iter_date->format('N');
            $calendar_date_fmt_year = $iter_date->format('Y');
            //echo $calendar_date_fmt; exit;
            //
            foreach($resources as $resource){
                //
                $resource_id = $resource['id'];
                $working_hours_id = $resource['working_hours_id'];
                //
                $arr_avail_res = self::getResurceAvaiability($app_id, $resource_id, $working_hours_id, $week_day, $calendar_date_fmt);
                $arr_sch_ex_res = self::getResurceScheduleExceptions($app_id, $location_id, $resource_id, $today_obj, $iter_date, $week_day, $calendar_date_fmt, $calendar_date_fmt_year);                
                $arr_appmnts_res = self::getResourcesAppointments($app_id, $location_id, $resource_id, $calendar_date_fmt, $ignore_cancelled, $is_front_page_user, $customer_id);
                //dd($arr_sch_ex_res);
                // Agregar resultados usando spread operator (más eficiente)
                $arr_avail = [...$arr_avail, ...$arr_avail_res];
                $arr_sch_ex = [...$arr_sch_ex, ...$arr_sch_ex_res];
                $arr_appmnts = [...$arr_appmnts, ...$arr_appmnts_res];
            };

            // Avanzar un día
            $iter_date->modify('+1 day');
        }

        //
        $all_events = [...$arr_avail, ...$arr_sch_ex, ...$arr_appmnts];
        //dd($all_events);


        // 
        return $response->withJson([
            'meta' => [
                "count_avail" => count($arr_avail),
                "count_sch_ex" => count($arr_sch_ex),
                "count_appmnts" => count($arr_appmnts),
                "sd" => $date_obj->format("Y-m-d"),
                "ed" => $end_date_obj->format("Y-m-d"),
            ],
            'events' => $all_events
        ], 200);
    }









    /**
     * 
     * Calcula si se intersecatan la fecha de la cita de inicio fin con un slot dado (de disponibilidad, excepcion de horario o de citas actuales)
     */
    private static function calculateOverlap($appointment_start, $appointment_end, $slots) {
        foreach($slots as $item) {
            $start = new \DateTime($item['start']);
            $end = new \DateTime($item['end']);

            // Calcular intersección
            $overlap_start = max($appointment_start, $start);
            $overlap_end = min($appointment_end, $end);
            
            if ($overlap_start < $overlap_end) {
                // Cálculo CORRECTO de minutos
                $overlap_interval = $overlap_start->diff($overlap_end);
                $overlap_minutes = ($overlap_interval->h * 60) + $overlap_interval->i;
                
                $total_interval = $appointment_start->diff($appointment_end);
                $total_appointment_minutes = ($total_interval->h * 60) + $total_interval->i;
                
                return [
                    'slot' => $item,
                    'overlap_minutes' => $overlap_minutes,
                    'missing_minutes' => $total_appointment_minutes - $overlap_minutes,
                    'fits_completely' => ($appointment_start >= $start && $appointment_end <= $end),
                    'has_overlap' => true
                ];
            }
        }
        
        return ['has_overlap' => false];
    }




    //
    public function AddAppointment($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        

        //
        $user_type = (isset($ses_data['t']) && $ses_data['t']) ? $ses_data['t'] : null;
        //
        if (!$user_type){
            $results['error'] = "not a valid user type";
            return $response->withJson($results, 400);
        }

        


        



        


        //
        $results = array();


        $location_id = $args['location_id'];
        $resource_id = $args['resource_id'];



        //
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');
        //
        $start_datetime = Helper::safeVar($request->getParsedBody(), 'start_datetime');
        //
        $resource_service_id = Helper::safeVar($request->getParsedBody(), 'resource_service_id');
        $post_customer_id = Helper::safeVar($request->getParsedBody(), 'customer_id');
        //
        $post_customer_person_id = Helper::safeVar($request->getParsedBody(), 'customer_person_id');
        $customer_person_id = ($post_customer_person_id) ? (int) $post_customer_person_id : null;

        // default status
        $cita_status_id = CITA_STATUS_IN_PROGRESS;


        



        //
        $is_front_page_user = false;
        $user_id = null;
        $customer_id = null;

        // Si es usuario tipo backend
        if ($user_type === "b" ){
            //
            $is_front_page_user = false;
            $user_id = (int)$ses_data['id'];
            // 
            if ( !is_numeric($post_customer_id) ){
                $results['error'] = "proporciona el customer_id";
                return $response->withJson($results, 200);
            }
            //
            $customer_id = $post_customer_id;
        }
        
        // Si es usuario tipo front-end
        else if ($user_type === "f" ){
            $is_front_page_user = true;
            $customer_id = (int)$ses_data['id'];
        }
        //echo " $resource_service_id, $customer_id, $customer_person_id "; exit;





        
        //
        if ( !is_numeric($resource_service_id) ){
            $results['error'] = "proporciona el servicio del recurso";
            return $response->withJson($results, 200);
        }





        //
        $appointment_start_datetime_obj = Helper::is_valid_date($start_datetime, self::$sql_date_format, null);
        //dd($appointment_start_datetime_obj);
        if ( !$appointment_start_datetime_obj ){
            $results['error'] = "proporciona fecha y hora validos";
            return $response->withJson($results, 200);
        }
        //dd($appointment_start_datetime_obj);


        
        // Validar que no sea fecha pasada
        $now = new \DateTime();
        if ($appointment_start_datetime_obj <= $now) {
            $results['error'] = "No se pueden crear citas en fechas pasadas, agende despues de las " . $now->format("g:i A") . " del " . $now->format("d M Y");
            return $response->withJson($results, 200);
        }


        

        /**
         * 
         * buscamos el servicio
         */
        //
        $service_info = Query::Single("
                Select 
                    *
                From
                     v_resources_services 
                where 
                    app_id = ? 
                And
                    resource_id = ?
                And 
                    id = ?
                And 
                    service_active = 1 
            ", [$app_id, $resource_id, $resource_service_id]);
        //dd($service_info);
        if ( !($service_info && isset($service_info['id'])) ){
            $results['error'] = "no se encontro el servicio, no esta asignado al recurso o esta inactivo";
            return $response->withJson($results, 200);
        }
        //
        $service_name = $service_info['service_name'];
        $servicio_duracion_minutos = $service_info['servicio_duracion_minutos'];



        /**
         * 
         * Get Customer Info 
         */
        //
        $customer_info = Query::Single("Select id, name, email, phone_cc, phone_number from v_customers where app_id = ? And id = ?", [$app_id, $customer_id]);
        //dd($customer_info);
        if ( !($customer_info && isset($customer_info['id'])) ){
            $results['error'] = "no se encontro el client";
            return $response->withJson($results, 200);
        }
        //
        $customer_name = $customer_info['name'];


        /**
         * 
         * Get Customer Person Info si proporciono el $customer_person_id
         */
        $customer_person_name_and_type = null;
        //
        if ($customer_person_id){
            // 
            $customer_person_info = Query::Single("Select * From v_customer_people where app_id = ? And customer_parent_id = ? And id = ?", [$app_id, $customer_id, $customer_person_id]);
            //dd($customer_person_info);
            if ( !($customer_person_info && isset($customer_person_info['id'])) ){
                $results['error'] = "no se encontro el familiar/amigo del cliente";
                return $response->withJson($results, 200);
            }
            //
            $customer_person_name_and_type  = $customer_person_info['name'] . " (" . $customer_person_info['relative_type'] . ")";
        }
        //echo $customer_person_name_and_type; exit;

        
        
        
       

        /**
         * 
         * Valida que cliente no tenga cita activa
         * 
         * Agregar *And resource_id = ? para que se pueda agendar en otros recursos
         * 
         */
        if ( is_numeric($customer_person_id) && $customer_person_id > 0 ){

            //echo "es customer person: "; exit;

            //
            $active_cita_res = Query::Single("
                Select Top 1 * From v_appointments 
                where 
                    app_id = ? And sucursal_id = ? And customer_id = ? and customer_person_id = ?
                And 
                    -- la cita tiene fecha y hora mayor a la actual
                    start_datetime > GETDATE()
                And 
                    -- su estatus sea pendiente o en progreso
                    status_id In (?, ?)
                ", 
                [$app_id, $location_id, $customer_id, $customer_person_id, CITA_STATUS_PENDING, CITA_STATUS_IN_PROGRESS]);
            //dd($active_cita_res);
            //
            if ( $active_cita_res && $active_cita_res > 0 ){
                //
                $results['error'] = "Ya cuentas con una Cita activa para " . $active_cita_res['person_name'] . " en " . $active_cita_res['resource_name'] . " si deseas re-agendar, cancela e inetenta de nuevo";
                return $response->withJson($results, 200);
            }
        } else {


            //echo "es customer: "; exit;

            /**
             * 
             * Valida que cliente no tenga cita activa
             * 
             * Agregar *And resource_id = ? para que se pueda agendar en otros recursos
             * 
             */
            $active_cita_res = Query::Single("
                Select Top 1 * From v_appointments 
                where 
                    app_id = ? And sucursal_id = ? And customer_id = ? and customer_person_id Is Null
                And 
                    -- la cita tiene fecha y hora mayor a la actual
                    start_datetime > GETDATE()
                And 
                    -- su estatus sea pendiente o en progreso
                    status_id In (?, ?)
                ", 
                [$app_id, $location_id, $customer_id, CITA_STATUS_PENDING, CITA_STATUS_IN_PROGRESS]);
            //dd($active_cita_res);
            //
            if ( $active_cita_res && $active_cita_res > 0 ){
                $results['error'] = "Ya cuentas con una Cita activa para " . $active_cita_res['customer_name'] . " en " . $active_cita_res['resource_name'] . " si deseas re-agendar, cancela e inetenta de nuevo";
                return $response->withJson($results, 200);
            }



        }
        //echo "Ok,continue"; exit;
        


        



        /**
         * 
         * Get End datetime
         * sumamos la duracion del servicio en minutos para obtener la fecha/hora fin de la cita
         */
        //
        $appointment_end_datetime_obj = (clone $appointment_start_datetime_obj)->modify("+{$servicio_duracion_minutos} minutes");
        //dd($appointment_end_datetime_obj);
        



        
        /**
         * 
         * Set Cita Title
         */
        //
        if ($customer_person_name_and_type){
            $appointment_title = $customer_person_name_and_type . " - " . $service_name;
        } else {
            $appointment_title = $customer_name . " - " . $service_name;
        }
        //echo $appointment_title; exit;



        /**
         * 
         * Nos traemos info del recurso 
         * para revisar disponibilidad
         */
        $resources = Query::Single("
            SELECT 
                id, 
                name as title,
                working_hours_id

            FROM calendar_resources 
            WHERE app_id = ? 
            AND sucursal_id = ? 
            AND active = 1 
            AND id = ?
        ", [$app_id, $location_id, $resource_id]);
        //dd($resources);
        if ( !($resources && isset($resources['id'])) ){
            $results['error'] = "no se encontro el recurso";
            return $response->withJson($results, 200);
        }
        //
        $working_hours_id = $resources['working_hours_id'];



        //
        $today_obj = new \DateTime('today');
        $date_obj = new \DateTime($start_datetime);        
        //dd($date_obj);
        //
        $calendar_date_fmt = $date_obj->format('Y-m-d');
        $week_day = $date_obj->format('N');
        $calendar_date_fmt_year = $date_obj->format('Y');
        //echo $calendar_date_fmt; exit;
        





        // 1. Verificar disponibilidad de horario (debe encajar completamente)
        $arr_avail_res = self::getResurceAvaiability($app_id, $resource_id, $working_hours_id, $week_day, $calendar_date_fmt);
        //dd($arr_avail_res);

        if (empty($arr_avail_res)) {
            $results['error'] = "No hay horarios de trabajo configurados para este recurso el " . $date_obj->format('d/m/Y');
            return $response->withJson($results, 200);
        }

        $availability_check = self::calculateOverlap($appointment_start_datetime_obj, $appointment_end_datetime_obj, $arr_avail_res);
        //dd($availability_check);

        if (!$availability_check['has_overlap'] || !$availability_check['fits_completely']) {
            $results['error'] = "El horario seleccionado no está dentro de las horas de trabajo disponibles";
            return $response->withJson($results, 200);
        }







        // 2. Verificar excepciones de horario (NO debe haber intersección)
        $arr_sch_ex_res = self::getResurceScheduleExceptions($app_id, $location_id, $resource_id, $today_obj, $date_obj, $week_day, $calendar_date_fmt, $calendar_date_fmt_year);
        //dd($arr_sch_ex_res);

        if (!empty($arr_sch_ex_res)) {
            $exception_check = self::calculateOverlap($appointment_start_datetime_obj, $appointment_end_datetime_obj, $arr_sch_ex_res);
            //dd($exception_check);
            
            if ($exception_check['has_overlap'] && $exception_check['overlap_minutes'] > 0) {
                $results['error'] = "No disponible por: " . $exception_check['slot']['title'];
                return $response->withJson($results, 200);
            }
        }







        /**
         * 
         * Aqui en add appintments establecemos el 5to parametro "ignore_cancelled" (ignorar canceladas) a true
         */
        // 3. Verificar citas existentes (NO debe haber intersección)
        $igore_cancelled = true;  // ignora cancelados para que nos permita reservar
        $arr_appmnts_res = self::getResourcesAppointments($app_id, $location_id, $resource_id, $calendar_date_fmt, $igore_cancelled, false);
        //dd($arr_appmnts_res);

        if (!empty($arr_appmnts_res)) {
            $appointment_check = self::calculateOverlap($appointment_start_datetime_obj, $appointment_end_datetime_obj, $arr_appmnts_res);
            //dd($appointment_check);
            
            if ($appointment_check['has_overlap'] && $appointment_check['overlap_minutes'] > 0) {

                //
                $cita_customer_id = (isset($appointment_check['slot']['extendedProps']) && isset($appointment_check['slot']['extendedProps']['customer_id'])) ? (int)$appointment_check['slot']['extendedProps']['customer_id'] : null;
                //echo $cita_customer_id; exit;

                $cita_err_msg = "";

                /**
                 *  
                 * Si es para mostrar en front-page (reactjs page)
                 */
                if ( $is_front_page_user ){

                    // es el mismo customer
                    if ( $customer_id === $cita_customer_id ){
                        //
                        $cita_err_msg = "Ya existe una cita en ese horario: " . $appointment_check['slot']['title'];
                    } 
                    // es otro customer
                    else {
                        //
                        $cita_err_msg = "Ya existe una cita en ese horario";
                    }                    
                } 
                /**
                 * 
                 * Si es para mostrar en admin
                 */
                else {
                    //
                    $cita_err_msg = "Ya existe una cita en ese horario: " . $appointment_check['slot']['title'];
                }

                //
                $results['error'] = $cita_err_msg;
                return $response->withJson($results, 200);
            }
        }





        

        //
        $params = [
            $app_id, $location_id, $resource_id, $customer_id, $customer_person_id, $appointment_start_datetime_obj, $appointment_end_datetime_obj,
            $appointment_title, $notes, $resource_service_id, $cita_status_id
        ];
        //dd($params);
        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into calendar_appointments
                  ( app_id, sucursal_id, resource_id, customer_id, customer_person_id, start_datetime, end_datetime,
                    title, notes, resource_service_id, status_id, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => $params,
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);


        /*
        //
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_EDIT,
                "valor_nuevo" => "cliente folio: $cliente_id, name: $title, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($insert_results, 200);
    }





    //
    public function EditAppointment($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();


        $location_id = $args['location_id'];
        $resource_id = $args['resource_id'];
        $id = $args['appointment_id'];



        //
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');
        // status
        $cita_status_id = 1;


        $params = [
            $notes, $app_id, $location_id, $resource_id, $id
        ];
        //dd($params);
        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    calendar_appointments
                  Set 
                    notes = ?

                  Where app_id = ?
                  And location_id = ?
                  And resource_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => $params,
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;


        /*
        //
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_EDIT,
                "valor_nuevo" => "cliente folio: $cliente_id, name: $title, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($update_results, 200);
    }







    //
    public function PostUpdateAdminAppointmentStatus($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();


        $location_id = $args['location_id'];
        $resource_id = $args['resource_id'];
        $record_id = $args['appointment_id'];

        //
        $new_status_id = Helper::safeVar($request->getParsedBody(), 'new_status');


        //
        $appointment_data = Query::Single("
            SELECT * 
            FROM calendar_appointments
            WHERE app_id = ? 
            And sucursal_id = ? 
            AND resource_id = ?
            And id = ?
        ", [$app_id, $location_id, $resource_id, $record_id]);
        //dd($appointment_data);
        //
        if ( $appointment_data && isset($appointment_data['error']) ){
            $results['error'] = $appointment_data['error'];
            return $response->withJson($results, 200);
        }
        if ( !($appointment_data && isset($appointment_data['id'])) ){
            $results['error'] = "cita no encontrada";
            return $response->withJson($results, 200);
        }

        //
        if ( $appointment_data['sale_id'] && $appointment_data['sale_item_id'] ){
            $results['error'] = "No se puede cambiar estado de citas ya cobradas";
            return $response->withJson($results, 200);
        }

        //
        $service_id = $appointment_data['resource_service_id'];
        $customer_id = $appointment_data['customer_id'];
        //
        $date_obj = $appointment_data['start_datetime'];
        $end_datetime = $appointment_data['end_datetime'];
        //dd($end_datetime);

        // 
        $today_obj = new \DateTime('today');
        
        

        

        $now = new \DateTime();

        //
        if ($new_status_id === "reschedule"){
        
            
            


            /**
             * 
             * Nos traemos info del recurso 
             * para revisar disponibilidad
             */
            $resources = Query::Single("
                SELECT 
                    id, 
                    name as title,
                    working_hours_id

                FROM calendar_resources 
                WHERE app_id = ? 
                AND sucursal_id = ? 
                AND active = 1 
                AND id = ?
            ", [$app_id, $location_id, $resource_id]);
            //dd($resources);
            if ( !($resources && isset($resources['id'])) ){
                $results['error'] = "no se encontro el recurso";
                return $response->withJson($results, 200);
            }
            //
            $working_hours_id = $resources['working_hours_id'];


            
            //
            $calendar_date_fmt = $date_obj->format('Y-m-d');
            $week_day = $date_obj->format('N');
            $calendar_date_fmt_year = $date_obj->format('Y');
            //echo $calendar_date_fmt_year; exit;
            


            // 1. Verificar disponibilidad de horario (debe encajar completamente)
            $arr_avail_res = self::getResurceAvaiability($app_id, $resource_id, $working_hours_id, $week_day, $calendar_date_fmt);
            //dd($arr_avail_res);

            if (empty($arr_avail_res)) {
                $results['error'] = "No hay horarios de trabajo configurados para este recurso el " . $date_obj->format('d/m/Y');
                return $response->withJson($results, 200);
            }

            $availability_check = self::calculateOverlap($date_obj, $end_datetime, $arr_avail_res);
            //dd($availability_check);

            if (!$availability_check['has_overlap'] || !$availability_check['fits_completely']) {
                $results['error'] = "El horario seleccionado no está dentro de las horas de trabajo disponibles";
                return $response->withJson($results, 200);
            }







            // 2. Verificar excepciones de horario (NO debe haber intersección)
            $arr_sch_ex_res = self::getResurceScheduleExceptions($app_id, $location_id, $resource_id, $today_obj, $date_obj, $week_day, $calendar_date_fmt, $calendar_date_fmt_year);

            if (!empty($arr_sch_ex_res)) {
                $exception_check = self::calculateOverlap($date_obj, $end_datetime, $arr_sch_ex_res);
                
                if ($exception_check['has_overlap'] && $exception_check['overlap_minutes'] > 0) {
                    $results['error'] = "No disponible por: " . $exception_check['slot']['title'];
                    return $response->withJson($results, 200);
                }
            }







            // 3. Verificar citas existentes (NO debe haber intersección)
            $ignore_cancelled = true;
            $is_front_page = false;
            $arr_appmnts_res = self::getResourcesAppointments($app_id, $location_id, $resource_id, $calendar_date_fmt, $ignore_cancelled, $is_front_page);
            //dd($arr_appmnts_res);

            if (!empty($arr_appmnts_res)) {                
                $appointment_check = self::calculateOverlap($date_obj, $end_datetime, $arr_appmnts_res);
                //dd($appointment_check);
                // si es el mismo id no procesamos
                if ( isset($appointment_check['slot']) && isset($appointment_check['slot']['id']) && $appointment_check['slot']['id'] == $record_id ){
                    
                    /* SI es el mismo Id asi que no checamos */

                } else {

                    /* NO es el mismo Id asi que SI checamos */
                    if ($appointment_check['has_overlap'] && $appointment_check['overlap_minutes'] > 0) {
                        $results['error'] = "Ya existe una cita en ese horario: " . $appointment_check['slot']['title'];
                        return $response->withJson($results, 200);
                    }

                }
            }

            
            
            /**
             * 
             * Si paso las validaciones entonces si podemos cambiar el estatus 
             */
            $new_status_id = CITA_STATUS_IN_PROGRESS;
        }

        


        $params = [
            $new_status_id, $app_id, $location_id, $resource_id, $record_id
        ];
        //dd($params);
        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    calendar_appointments
                  Set 
                    status_id = ?

                  Where app_id = ?
                  And sucursal_id = ?
                  And resource_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => $params,
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;


        /*
        //
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_EDIT,
                "valor_nuevo" => "cliente folio: $cliente_id, name: $title, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        // 
        return $response->withJson($update_results, 200);
    }












    //
    public function PostUpdateCustomerAppointmentStatus($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];
        $customer_id = (int)$ses_data['id'];
        


        $record_id = $args['appointment_id'];


        //
        $results = array();


        //
        $param_status_id = Helper::safeVar($request->getParsedBody(), 'new_status_id');
        $new_status_id = ($param_status_id) ? (int)$param_status_id : null;
        //echo $new_status_id; exit;


        //        
        if ($new_status_id !== CITA_STATUS_CANCELLED){
            $results['error'] = "Solo se permite cancelar citas";
            return $response->withJson($results, 200);
        }

        //
        $appointment_data = Query::Single("SELECT * FROM v_appointments WHERE app_id = ? And id = ?", [$app_id, $record_id]);
        //dd($appointment_data); exit;
        //
        if ( $appointment_data && isset($appointment_data['error']) ){
            $results['error'] = $appointment_data['error'];
            return $response->withJson($results, 200);
        }
        if ( !($appointment_data && isset($appointment_data['id'])) ){
            $results['error'] = "appointment not found";
            return $response->withJson($results, 200);
        }

        //
        $cita_customer_id = $appointment_data['customer_id'];
        $cita_current_status_id = $appointment_data['status_id'];
        //dd($status_id);

        //echo " $customer_id $cita_customer_id " ; exit;
        if ( (int)($cita_customer_id) !== $customer_id ){
            $results['error'] = "no se permite cancelar citas de otros clientes";
            return $response->withJson($results, 200);
        }
        //echo "Ok si puede!"; exit;


        //
        $ok_continue_cancel = false;

        /**
         * 
         * Solo si la cita esta en progreso o pendiente cancelamos
         */
        if ( (int)$cita_current_status_id === CITA_STATUS_CANCELLED ){
            $results['error'] = "Cita ya ha sido cancelada previamente";
            return $response->withJson($results, 200);
        }
        //
        else if ( in_array((int)$cita_current_status_id, [CITA_STATUS_CONFIRMED]) ){
            $results['error'] = "No se pueden cancelar citas confirmadas";
            return $response->withJson($results, 200);
        } 
        //
        else if ( in_array((int)$cita_current_status_id, [CITA_STATUS_IN_PROGRESS, CITA_STATUS_PENDING]) ){
            //
            $ok_continue_cancel = true;
        }
        //echo $ok_continue_cancel; exit;

        

        $params = [
            $new_status_id, $app_id, $record_id
        ];
        //dd($params);
        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    calendar_appointments
                  Set 
                    status_id = ?

                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => $params,
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;


        /*
        //
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_EDIT,
                "valor_nuevo" => "cliente folio: $cliente_id, name: $title, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($update_results, 200);
    }






    
     /*
    public function GetDurationSlots($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
        //
        $results = Query::Multiple("
            select 
                servicio_duracion_minutos as sdm
            from products 
                Where tipo_producto_servicio = 's'
                And (servicio_duracion_minutos is not null and servicio_duracion_minutos > 0)
                And active = 1
                Group By servicio_duracion_minutos
                Order by servicio_duracion_minutos Asc
        ", [$app_id, $record_id]);
        //dd($results);
        //
        foreach($results as $index => $item){
            //
            $service_duration_mins = $item['sdm'];
            //
            $hours = floor($service_duration_mins / 60);
            $minutes = $service_duration_mins % 60;
            $duration_parts = [];
            //
            if ($hours > 0) {
                $duration_parts[] = $hours . ($hours == 1 ? ' hr' : ' hrs');
            }
            //
            if ($minutes > 0) {
                $duration_parts[] = $minutes . ($minutes == 1 ? ' min' : ' mins');
            }
            //
            $results[$index]['duration'] = sprintf("%02d:%02d:00", $hours, $minutes);
            $results[$index]['duration_text'] = !empty($duration_parts) ? implode(' ', $duration_parts) : '0 mins';
        }
        //
        return $response->withJson($results, 200);
    }
    */





}
