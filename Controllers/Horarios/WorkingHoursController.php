<?php
namespace Controllers\Horarios;

//
use App\Products\ProductsTags;

use Controllers\BaseController;
use App\App;
use Helpers\Helper;
use Helpers\Query;


//
class WorkingHoursController extends BaseController
{


    

    //
    public function ViewIndex($request, $response, $args) {
        //
        //$ses = $request->getAttribute("ses_data"); dd($ses);
        //
        return $this->container->php_view->render($response, 'admin/working_hours/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
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
        return $this->container->php_view->render($response, 'admin/working_hours/edit.phtml', $view_data);
    }


    
    

    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' )
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
                "table_name" => "working_hours",

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
    public function GetAllAvailable($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        

        //
        $results = Query::Multiple("Select * from working_hours Where app_id = ? And active = 1", [$app_id]);
        //var_dump($results); exit;

        //
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
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : null;


        
        


        
        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }

        
        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into working_hours
                  ( app_id, nombre, active, datetime_created )
                  Values
                  ( ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id, $nombre, $active
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
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : null;


        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
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
                    working_hours
                  Set 
                    nombre = ?,
                    active = ?

                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $nombre,
                $active,
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
        $record_id = $args['id'];

        //
        $schedule = Query::Single("Select * From working_hours where app_id = ? And id = ?", [$app_id, $record_id]);
        
        //
        return $response->withJson($schedule, 200);
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
            "stmt" => "Delete FROM working_hours Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $id
            ],
            "exeptions_msgs" => [
                "FK_calendar_resources_working_hours" => "No se puede eliminar horario por que esta asociado a un recurso"
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