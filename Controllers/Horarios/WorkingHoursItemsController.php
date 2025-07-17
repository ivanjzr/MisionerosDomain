<?php
namespace Controllers\Horarios;

//
use App\Products\ProductsTags;

use Controllers\BaseController;
use App\App;
use Helpers\Helper;
use Helpers\Query;


//
class WorkingHoursItemsController extends BaseController
{




    
    

    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.name like '%$search_value%' )
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
        $working_hours_id = $args['working_hours_id'];
        //echo $working_hours_id; exit;

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "working_hours_list",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.working_hours_id = ? {$search_clause}";
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
                                            And t.working_hours_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $working_hours_id
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



        $working_hours_id = $args['working_hours_id'];
        //echo $working_hours_id; exit;

        


        //
        $name = Helper::safeVar($request->getParsedBody(), 'name');
        $week_day = Helper::safeVar($request->getParsedBody(), 'week_day');
        //
        $hora_inicio = Helper::safeVar($request->getParsedBody(), 'hora_inicio');
        $hora_fin = Helper::safeVar($request->getParsedBody(), 'hora_fin');
        //
        $activo = Helper::safeVar($request->getParsedBody(), 'activo') ? 1 : null;

        
        //
        if ( !$name ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$week_day ){
            $results['error'] = "proporciona el dia";
            return $response->withJson($results, 200);
        }


        //
        $hora_inicio_obj = Helper::is_valid_date($hora_inicio, "g:i A", null);
        $hora_fin_obj = Helper::is_valid_date($hora_fin, "g:i A", null);
        /*
        var_dump($hora_inicio_obj);
        var_dump($hora_fin_obj);
        exit;
        */

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
        if ($hora_fin_obj <= $hora_inicio_obj){
            $results['error'] = "la hora de fin no puede se menor o igual a la hora de inicio";
            return $response->withJson($results, 200);
        }

        // Crear una copia de la hora inicio para comparar
        $hora_inicio_mas_una_hora = clone $hora_inicio_obj;
        // Añadir una hora
        $hora_inicio_mas_una_hora->modify('+1 hour');

        // Verificar si la hora fin es menor que la hora inicio + 1 hora
        if ($hora_fin_obj < $hora_inicio_mas_una_hora) {
            $results['error'] = "La duración mínima debe ser de 1 hora. Por favor seleccione una hora de fin al menos una hora después de la hora de inicio";
            return $response->withJson($results, 200);
        }


        //
        $validation_res = Query::Single("Select id, hora_inicio, hora_fin From working_hours_list where app_id = ? And working_hours_id = ? And week_day = ?", [$app_id, $working_hours_id, $week_day]);
        //var_dump($validation_res); exit;
        if ($validation_res && isset($validation_res['id'])){
    
            //
            $existing_inicio = $validation_res['hora_inicio'];
            $existing_fin = $validation_res['hora_fin'];


            // fecha base para comparaciones
            $base_date = new \DateTime('2000-01-01');

            // Copiar solo la hora y minutos a nuevos objetos
            $nuevo_inicio = clone $base_date;
            $nuevo_inicio->setTime($hora_inicio_obj->format('H'), $hora_inicio_obj->format('i'));
            
            $nuevo_fin = clone $base_date;
            $nuevo_fin->setTime($hora_fin_obj->format('H'), $hora_fin_obj->format('i'));
            
            $existente_inicio = clone $base_date;
            $existente_inicio->setTime($existing_inicio->format('H'), $existing_inicio->format('i'));
            
            $existente_fin = clone $base_date;
            $existente_fin->setTime($existing_fin->format('H'), $existing_fin->format('i'));
            
            // Una única validación que cubre todos los casos de solapamiento
            if (
                // Caso 1: hora_inicio del nuevo está entre inicio y fin del existente
                ($nuevo_inicio >= $existente_inicio && $nuevo_inicio < $existente_fin) ||
                // Caso 2: hora_fin del nuevo está entre inicio y fin del existente
                ($nuevo_fin > $existente_inicio && $nuevo_fin <= $existente_fin) ||
                // Caso 3: El nuevo horario envuelve completamente al existente
                ($nuevo_inicio <= $existente_inicio && $nuevo_fin >= $existente_fin)
            ) {
                $existing_inicio_str = $existing_inicio->format('g:i A');
                $existing_fin_str = $existing_fin->format('g:i A');
                
                $results['error'] = "El horario propuesto se solapa con un horario existente de $existing_inicio_str a $existing_fin_str para este día";
                return $response->withJson($results, 200);
            }
        }

        // al final convertimos para insertar a la bd
        $hora_inicio_val = $hora_inicio_obj->format("H:i");
        $hora_fin_val = $hora_fin_obj->format("H:i");



        // debug dates
        //echo $hora_inicio_val . " - " . $hora_fin_val; exit;



        //
        $contact_id = null;


        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into working_hours_list
                  ( app_id, working_hours_id, name, week_day, hora_inicio, hora_fin, activo )
                  Values
                  ( ?, ?, ?, ?, ?, ?, ? )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id, $working_hours_id, $name, $week_day, $hora_inicio_val, $hora_fin_val, $activo
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
                "valor_nuevo" => "cliente folio: $cliente_id, name: $name, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($insert_results, 200);
    }






    //
    public function EditRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();



        //
        $name = Helper::safeVar($request->getParsedBody(), 'name');
        $week_day = Helper::safeVar($request->getParsedBody(), 'week_day');
        $activo = Helper::safeVar($request->getParsedBody(), 'activo') ? 1 : null;


        //
        if ( !$name ){
            $results['error'] = "proporciona el name";
            return $response->withJson($results, 200);
        }
        if ( !$week_day ){
            $results['error'] = "proporciona el week_day";
            return $response->withJson($results, 200);
        }
        

        
        
        //
        $working_hours_id = $args['working_hours_id'];
        $record_id = $args['id'];


        //echo "$record_id"; exit;


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    working_hours_list
                  Set 
                    name = ?,
                    week_day = ?,
                    activo = ?

                  Where app_id = ?
                  And working_hours_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $name,
                $week_day,
                $activo,
                //
                $app_id,
                $working_hours_id,
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
                "valor_nuevo" => "cliente folio: $cliente_id, name: $name, email: $email, telefono1: $telefono1, telefono2: $telefono2"
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
        $working_hours_id = $args['working_hours_id'];
        $record_id = $args['id'];
        //
        $schedule = Query::Single("Select * From working_hours_list where app_id = ? And working_hours_id = ? And id = ?", [$app_id, $working_hours_id, $record_id]);
        
        //
        return $response->withJson($schedule, 200);
    }










    //
    public function PostDeleteRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
        


        //
        $results = array();


        $working_hours_id = $args['working_hours_id'];


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
            "stmt" => "Delete FROM working_hours_list Where app_id = ? And working_hours_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $working_hours_id,
                $id
            ],
            "exeptions_msgs" => [
                "FK_empleados_sucursales_sucursales" => "No se puede eliminar la sucursal por que esta asociada a un usuario"
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