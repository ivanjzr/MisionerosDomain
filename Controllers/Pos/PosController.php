<?php
namespace Controllers\Pos;

use App\App;
use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;
use App\Empleados\Empleados;
use App\Empleados\EmpleadosSucursalesPermisos;


class PosController extends BaseController
{
    
    
    
    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/pos_list/index.phtml', [
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
        return $this->container->php_view->render($response, 'admin/pos_list/edit.phtml', $view_data);
    }







    //
    public static function configSearchClause($search_value, $resource_type, $location_id){
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
        if ( $resource_type ){
            //
            $search_clause .= " And t.resource_type = '$resource_type' ";
        }

        //
        if ( is_numeric($location_id) ){
            //
            $search_clause .= " And t.sucursal_id = $location_id ";
        }

        //
        //echo $search_clause; exit;
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "v_pos";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']), trim($request->getQueryParam("res-type")), $request->getQueryParam("lid"));
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
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();



        //
        $location_id = Helper::safeVar($request->getParsedBody(), 'location_id');
        //
        $name = Helper::safeVar($request->getParsedBody(), 'name');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : null;


        

        
        //
        if ( !$name ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }

        
        
        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => false,
            "stmt" => "
                   Insert Into pos
                  ( app_id, sucursal_id, name, description, active, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id, $location_id, $name, $description, $active
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
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : null;


        

        
        //
        if ( !$name ){
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
                    pos
                  Set 
                    name = ?,
                    description = ?,
                    active = ?
                    
                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $name, $description, $active,
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
        $schedule = Query::Single("Select * From v_pos where app_id = ? And id = ?", [$app_id, $record_id]);
        
        //
        return $response->withJson($schedule, 200);
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
            "stmt" => "Delete FROM pos Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $id
            ],
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "FK_sales_payments_pos_registers" => "No se pueden eliminar el punto de venta por que tiene pagos de ventas asociadas",
                "FK_sales_items_pos_registers" => "No se pueden eliminar el punto de venta por que tiene productos/servicios de ventas asociadas",
                "FK_sales_pos_registers" => "No se pueden eliminar el punto de venta por que tiene ventas asociadas"
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