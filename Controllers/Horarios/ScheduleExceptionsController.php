<?php
namespace Controllers\Horarios;

//
use App\Products\ProductsTags;

use Controllers\BaseController;
use App\App;
use Helpers\Helper;
use Helpers\Query;


//
class ScheduleExceptionsController extends BaseController
{





    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/schedule_exceptions/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }
    
    

    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.motivo like '%$search_value%' ) Or 
                        ( t.description like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "schedule_exceptions",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($table_name, $order_field, $order_direction, $search_clause, $where_row_clause){
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
                "parseRows" => function(&$row){

                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }








    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();



        //
        $motivo = Helper::safeVar($request->getParsedBody(), 'motivo');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $prioridad = Helper::safeVar($request->getParsedBody(), 'prioridad');
        //
        $fecha = Helper::safeVar($request->getParsedBody(), 'fecha');
        $fecha_fin = Helper::safeVar($request->getParsedBody(), 'fecha_fin');
        //
        $hora_inicio = Helper::safeVar($request->getParsedBody(), 'hora_inicio');
        $hora_fin = Helper::safeVar($request->getParsedBody(), 'hora_fin');
        //
        $excepcion_tipo = Helper::safeVar($request->getParsedBody(), 'excepcion_tipo');
        $recurrencia_tipo = Helper::safeVar($request->getParsedBody(), 'recurrencia_tipo');
        $recurrencia_duracion = Helper::safeVar($request->getParsedBody(), 'recurrencia_duracion');
        $recurrencia_fecha_limite = Helper::safeVar($request->getParsedBody(), 'recurrencia_fecha_limite');


        //
        $recur_mon = Helper::safeVar($request->getParsedBody(), 'recur_mon') ? 1 : null;
        $recur_tue = Helper::safeVar($request->getParsedBody(), 'recur_tue') ? 1 : null;
        $recur_wed = Helper::safeVar($request->getParsedBody(), 'recur_wed') ? 1 : null;
        $recur_thu = Helper::safeVar($request->getParsedBody(), 'recur_thu') ? 1 : null;
        $recur_fri = Helper::safeVar($request->getParsedBody(), 'recur_fri') ? 1 : null;
        $recur_sat = Helper::safeVar($request->getParsedBody(), 'recur_sat') ? 1 : null;
        $recur_sun = Helper::safeVar($request->getParsedBody(), 'recur_sun') ? 1 : null;

        $activo = Helper::safeVar($request->getParsedBody(), 'activo') ? 1 : null;


        
        


        
        //
        if ( !$motivo ){
            $results['error'] = "proporciona el motivo";
            return $response->withJson($results, 200);
        }


        //
        $fecha_obj = Helper::is_valid_date($fecha, "d/m/Y", null);
        $fecha_fin_obj = Helper::is_valid_date($fecha_fin, "d/m/Y", null);
        //
        $hora_inicio_obj = Helper::is_valid_date($hora_inicio, "g:i A", null);
        $hora_fin_obj = Helper::is_valid_date($hora_fin, "g:i A", null);
        //
        $recurrencia_fecha_limite_obj = Helper::is_valid_date($recurrencia_fecha_limite, "d/m/Y", null);

                   
        
        
        /*
        var_dump($fecha_obj);
        var_dump($fecha_fin_obj);
        var_dump($hora_inicio_obj);
        var_dump($hora_fin_obj);
        var_dump($recurrencia_fecha_limite_obj);
        exit;
        */




        
        

        //
        $fecha_val = null;
        $fecha_fin_val = null;
        $hora_inicio_val = null;
        $hora_fin_val = null;
        $recurrencia_fecha_limite_val = null;




        //
        if (!$fecha_obj) {
            $results['error'] = "Se requiere la fecha";
            return $response->withJson($results, 200);
        }
        //
        $fecha_val = $fecha_obj->format("Y-m-d");

        //
        if ($excepcion_tipo === "solo_dia_con_horario"){
            //
            if (!$hora_inicio_obj) {
                $results['error'] = "Se requiere la hora de inicio";
                return $response->withJson($results, 200);
            }
            //
            if (!$hora_fin_obj) {
                $results['error'] = "Se requiere la hora de fin";
                return $response->withJson($results, 200);
            }
            //
            $hora_inicio_val = $hora_inicio_obj->format("H:i");
            $hora_fin_val = $hora_fin_obj->format("H:i");
        }
        //
        else if ($excepcion_tipo === "rango_dias"){
            //
            if (!$fecha_fin_obj) {
                $results['error'] = "Se requiere la hora de fin";
                return $response->withJson($results, 200);
            }
            //
            $fecha_fin_val = $fecha_fin_obj->format("Y-m-d");
        }
        //
        else if ($excepcion_tipo === "rango_dias_con_horario"){

            //
            if (!$fecha_fin_obj) {
                $results['error'] = "Se requiere la fecha de fin";
                return $response->withJson($results, 200);
            }
            //
            if (!$hora_inicio_obj) {
                $results['error'] = "Se requiere la hora de inicio";
                return $response->withJson($results, 200);
            }
            //
            if (!$hora_fin_obj) {
                $results['error'] = "Se requiere la hora de fin";
                return $response->withJson($results, 200);
            }            

            //
            $fecha_fin_val = $fecha_fin_obj->format("Y-m-d");        
            //
            $hora_inicio_val = $hora_inicio_obj->format("H:i");
            $hora_fin_val = $hora_fin_obj->format("H:i");
        }

        

        //
        if ($recurrencia_duracion === "fecha_limite"){
            //
            if (!$recurrencia_fecha_limite_obj) {
                $results['error'] = "Se requiere la fecha limite de recurrencia";
                return $response->withJson($results, 200);
            }
            $recurrencia_fecha_limite_val = $recurrencia_fecha_limite_obj->format("Y-m-d");
        }



        //
        if ($recurrencia_tipo === "diaria"){
            //
            $cont_recur = 0;            
            //
            if ($recur_mon){$cont_recur += 1;}
            if ($recur_tue){$cont_recur += 1;}
            if ($recur_wed){$cont_recur += 1;}
            if ($recur_thu){$cont_recur += 1;}
            if ($recur_fri){$cont_recur += 1;}
            if ($recur_sat){$cont_recur += 1;}
            if ($recur_sun){$cont_recur += 1;}
            //

            if ($cont_recur===0) {
                $results['error'] = "Para recurrencia diaria se requiere seleccionar al menos un dia de la semana";
                return $response->withJson($results, 200);
            }
        }




        // Validación para rangos de fechas (con o sin horario)
        if (in_array($excepcion_tipo, ["rango_dias", "rango_dias_con_horario"])) {
            // Verificar que fecha_fin no sea menor o igual a fecha_inicio
            if ($fecha_fin_obj <= $fecha_obj) {
                $results['error'] = "La fecha fin no puede ser menor o igual a la fecha inicio";
                return $response->withJson($results, 200);
            }
            
            // Crear copia de fecha inicio y agregar 1 día
            $fecha_mas_un_dia = clone $fecha_obj;
            $fecha_mas_un_dia->modify('+1 day');
            
            // Verificar que haya al menos 1 día de diferencia
            if ($fecha_fin_obj < $fecha_mas_un_dia) {
                $results['error'] = "El rango debe incluir al menos un día completo de diferencia. La fecha fin debe ser al menos un día después de la fecha inicio";
                return $response->withJson($results, 200);
            }
        }

        // Validación de horarios cuando aplique
        if (in_array($excepcion_tipo, ["solo_dia_con_horario", "rango_dias_con_horario"])) {
            // Validar que la hora fin sea mayor a la hora inicio
            if ($hora_fin_obj <= $hora_inicio_obj) {
                $results['error'] = "La hora de fin no puede ser menor o igual a la hora de inicio";
                return $response->withJson($results, 200);
            }
            
            // Validar que haya al menos una hora de diferencia
            $hora_inicio_mas_una_hora = clone $hora_inicio_obj;
            $hora_inicio_mas_una_hora->modify('+1 hour');
            
            if ($hora_fin_obj < $hora_inicio_mas_una_hora) {
                $results['error'] = "La duración mínima debe ser de 1 hora. Por favor seleccione una hora de fin al menos una hora después de la hora de inicio";
                return $response->withJson($results, 200);
            }
        }


        // Validar que no existan duplicados según el tipo de excepción
        $conditions = [];
        $params = [$app_id];

        // Parámetros base para consulta 
        if ($excepcion_tipo === "solo_dia") {
            // Para día completo, verificar que no exista el mismo día
            $conditions[] = "fecha = ? AND excepcion_tipo = 'solo_dia'";
            $params[] = $fecha_val;
        } 
        else if ($excepcion_tipo === "solo_dia_con_horario") {
            // Para horario en día específico, verificar misma fecha y horario
            $conditions[] = "fecha = ? AND excepcion_tipo = 'solo_dia_con_horario' AND hora_inicio = ? AND hora_fin = ?";
            $params[] = $fecha_val;
            $params[] = $hora_inicio_val;
            $params[] = $hora_fin_val;
        }
        else if ($excepcion_tipo === "rango_dias") {
            // Para rango de días, verificar mismo rango
            $conditions[] = "fecha = ? AND fecha_fin = ? AND excepcion_tipo = 'rango_dias'";
            $params[] = $fecha_val;
            $params[] = $fecha_fin_val;
        }
        else if ($excepcion_tipo === "rango_dias_con_horario") {
            // Para horario en rango, verificar mismo rango y horario
            $conditions[] = "fecha = ? AND fecha_fin = ? AND excepcion_tipo = 'rango_dias_con_horario' AND hora_inicio = ? AND hora_fin = ?";
            $params[] = $fecha_val;
            $params[] = $fecha_fin_val;
            $params[] = $hora_inicio_val;
            $params[] = $hora_fin_val;
        }

        // Si tenemos condiciones para validar
        if (!empty($conditions)) {
            $whereClause = implode(" OR ", $conditions);
            $sql = "SELECT id FROM schedule_exceptions WHERE app_id = ? AND activo = 1 AND (" . $whereClause . ")";
            
            $validationResult = Query::Single($sql, $params);
            
            if ($validationResult && isset($validationResult['id'])) {
                // Personalizar el mensaje según el tipo de excepción
                if ($excepcion_tipo === "solo_dia") {
                    $results['error'] = "Ya existe una excepción para el día " . date("d/m/Y", strtotime($fecha_val)) . ". No puede haber dos días o excepciones del mismo tipo en una misma fecha.";
                } else if ($excepcion_tipo === "solo_dia_con_horario") {
                    $results['error'] = "Ya existe una excepción con exactamente el mismo horario para esta fecha. Por favor modifique la fecha o el horario.";
                } else if ($excepcion_tipo === "rango_dias") {
                    $results['error'] = "Ya existe una excepción para el rango de fechas seleccionado. Por favor modifique las fechas.";
                } else if ($excepcion_tipo === "rango_dias_con_horario") {
                    $results['error'] = "Ya existe una excepción con exactamente el mismo horario para este rango de fechas. Por favor modifique las fechas o el horario.";
                }
                
                return $response->withJson($results, 200);
            }
        }



        //echo "Okay!"; exit;





        // debug dates
        //echo $fecha_val . " - " . $fecha_fin_val . " - " . $hora_inicio_val . " - " . $hora_fin_val . " - " . $recurrencia_fecha_limite_val; exit;



        //
        $contact_id = null;


        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into schedule_exceptions
                  ( app_id, motivo, description, prioridad,
                    fecha, fecha_fin, hora_inicio, hora_fin,
                    excepcion_tipo, recurrencia_tipo, recurrencia_fecha_limite, 
                    recurrencia_mon, recurrencia_tue, recurrencia_wed, recurrencia_thu, recurrencia_fri, recurrencia_sat, recurrencia_sun, 
                    activo, datetime_created )
                  Values
                  ( ?, ?, ?, ?,
                    ?, ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id, $motivo, $description, $prioridad,
                $fecha_val, $fecha_fin_val, $hora_inicio_val, $hora_fin_val,
                $excepcion_tipo, $recurrencia_tipo, $recurrencia_fecha_limite_val,
                $recur_mon, $recur_tue, $recur_wed, $recur_thu, $recur_fri, $recur_sat, $recur_sun, 
                $activo
            ],
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
                "valor_nuevo" => "cliente folio: $cliente_id, motivo: $motivo, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($insert_results, 200);
    }






    //
    public function EditRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();



        //
        $motivo = Helper::safeVar($request->getParsedBody(), 'motivo');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $prioridad = Helper::safeVar($request->getParsedBody(), 'prioridad');
        //
        $recurrencia_tipo = Helper::safeVar($request->getParsedBody(), 'recurrencia_tipo');
        $recurrencia_duracion = Helper::safeVar($request->getParsedBody(), 'recurrencia_duracion');
        $recurrencia_fecha_limite = Helper::safeVar($request->getParsedBody(), 'recurrencia_fecha_limite');


        //
        $recur_mon = Helper::safeVar($request->getParsedBody(), 'recur_mon') ? 1 : null;
        $recur_tue = Helper::safeVar($request->getParsedBody(), 'recur_tue') ? 1 : null;
        $recur_wed = Helper::safeVar($request->getParsedBody(), 'recur_wed') ? 1 : null;
        $recur_thu = Helper::safeVar($request->getParsedBody(), 'recur_thu') ? 1 : null;
        $recur_fri = Helper::safeVar($request->getParsedBody(), 'recur_fri') ? 1 : null;
        $recur_sat = Helper::safeVar($request->getParsedBody(), 'recur_sat') ? 1 : null;
        $recur_sun = Helper::safeVar($request->getParsedBody(), 'recur_sun') ? 1 : null;

        $activo = Helper::safeVar($request->getParsedBody(), 'activo') ? 1 : null;


        //
        if ( !$motivo ){
            $results['error'] = "proporciona el motivo";
            return $response->withJson($results, 200);
        }
        //
        $recurrencia_fecha_limite_obj = Helper::is_valid_date($recurrencia_fecha_limite, "d/m/Y", null);

                   
        
        
        /*
        var_dump($recurrencia_fecha_limite_obj);
        exit;
        */
        

        //
        $recurrencia_fecha_limite_val = null;

        //
        if ($recurrencia_duracion === "fecha_limite"){
            //
            if (!$recurrencia_fecha_limite_obj) {
                $results['error'] = "Se requiere la fecha limite de recurrencia";
                return $response->withJson($results, 200);
            }
            $recurrencia_fecha_limite_val = $recurrencia_fecha_limite_obj->format("Y-m-d");
        }



        // debug dates
        //echo $recurrencia_fecha_limite_val; exit;



        //
        if ($recurrencia_tipo === "diaria"){
            //
            $cont_recur = 0;            
            //
            if ($recur_mon){$cont_recur += 1;}
            if ($recur_tue){$cont_recur += 1;}
            if ($recur_wed){$cont_recur += 1;}
            if ($recur_thu){$cont_recur += 1;}
            if ($recur_fri){$cont_recur += 1;}
            if ($recur_sat){$cont_recur += 1;}
            if ($recur_sun){$cont_recur += 1;}
            //
            if ($cont_recur===0) {
                $results['error'] = "Para recurrencia diaria se requiere seleccionar al menos un dia de la semana";
                return $response->withJson($results, 200);
            }
        }






        //
        $record_id = $args['id'];


        //echo "$record_id"; exit;


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    schedule_exceptions
                  Set 
                    motivo = ?,
                    description = ?,
                    prioridad = ?,
                    recurrencia_tipo = ?,
                    recurrencia_fecha_limite = ?,
                    recurrencia_mon = ?,
                    recurrencia_tue = ?,
                    recurrencia_wed = ?,
                    recurrencia_thu = ?,
                    recurrencia_fri = ?,
                    recurrencia_sat = ?,
                    recurrencia_sun = ?,
                    activo = ?

                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $motivo,
                $description,
                $prioridad,
                $recurrencia_tipo, 
                $recurrencia_fecha_limite_val,
                $recur_mon, 
                $recur_tue,
                $recur_wed, 
                $recur_thu, 
                $recur_fri, 
                $recur_sat, 
                $recur_sun, 
                $activo,
                //
                $app_id,
                $record_id
            ],
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
                "valor_nuevo" => "cliente folio: $cliente_id, motivo: $motivo, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($update_results, 200);
    }





   public function GetRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_id = $ses_data['app_id'];

        //
        $record_id = $args['id'];

        //
        $schedule = Query::Single("Select * From schedule_exceptions where app_id = ? And id = ?", [$app_id, $record_id]);
        
        //
        return $response->withJson($schedule, 200);
    }






    //
    public function GetAllAvailable($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = Query::Multiple("Select * from schedule_exceptions Where app_id = ? And activo = 1", [$app_id]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }







    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
        


        //
        $results = array();


        //
        $id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }

        //
        $results = Query::DoTask([
            "task" => "delete",
            "debug" => false,
            "stmt" => "Delete FROM schedule_exceptions Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $id
            ],
            "exeptions_msgs" => [
                "FK_calendar_resources_schedule_exceptions_schedule_exceptions" => "No se puede eliminar excepcion de horario por que esta asociado a un recurso"
            ],
            "parse" => function($updated_rows, &$query_results) use($id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$id;
            }
        ]);
        //var_dump($results); exit;



        /*
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_DEL,
                "valor_nuevo" => "cliente folio: $id"
            ));
        */



        //
        return $response->withJson($results, 200);
    }











}