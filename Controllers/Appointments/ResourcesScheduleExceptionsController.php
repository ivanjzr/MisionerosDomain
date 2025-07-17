<?php
namespace Controllers\Appointments;

//
use App\Paths;


use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;

//
class ResourcesScheduleExceptionsController extends BaseController
{






    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.description like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];



        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);

        //
        $resource_id = $args['resource_id'];

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "v_resources_schedule_exceptions",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.resource_id = ? {$search_clause}";
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
                                            And t.resource_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $resource_id,
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
        $resource_id = $args['resource_id'];


        //
        $schedule_exception_id = Helper::safeVar($request->getParsedBody(), 'schedule_exception_id');




        //
        $exception_active = Query::Single("Select * From schedule_exceptions where app_id = ? And id = ? And activo = 1", [$app_id, $schedule_exception_id]);
        //var_dump($exception_active); exit;
        if ( !($exception_active && isset($exception_active['id'])) ){
            $results['error'] = "no se encontro la excepcion de horario o esta se encuentra inactiva";
            return $response->withJson($results, 200);
        }



        //
        $search_res = Query::Single("Select * From v_resources_schedule_exceptions where app_id = ? And resource_id = ? And schedule_exception_id = ?", [$app_id, $resource_id, $schedule_exception_id]);
        //var_dump($search_res); exit;
        if ( $search_res && isset($search_res['id']) ){
            $results['error'] = "ya existe la excepcion de horario con motivo '" . $search_res['motivo'] . "' para este recurso";
            return $response->withJson($results, 200);
        }


        

        //
        if ( !is_numeric($schedule_exception_id) ){
            $results['error'] = "proporciona la excepcion";
            return $response->withJson($results, 200);
        }

        
        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into calendar_resources_schedule_exceptions
                  ( app_id, resource_id, schedule_exception_id, datetime_created )
                  Values
                  ( ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id, $resource_id, $schedule_exception_id
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
    public function DeleteRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        $results = array();


        $resource_id = $args['resource_id'];



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
            "stmt" => "Delete FROM calendar_resources_schedule_exceptions Where app_id = ? And resource_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $resource_id,
                $id
            ],
            "parse" => function($updated_rows, &$query_results) use($id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$id;
            }
        ]);
        //var_dump($results); exit;



        //
        return $response->withJson($results, 200);
    }








}