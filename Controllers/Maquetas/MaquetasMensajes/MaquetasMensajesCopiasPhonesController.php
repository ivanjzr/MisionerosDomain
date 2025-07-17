<?php
namespace Controllers\Maquetas\MaquetasMensajes;

//
use App\Maquetas\MaquetasMensajes\MaquetasMensajes;
use App\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasPhones;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class MaquetasMensajesCopiasPhonesController extends BaseController
{







    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.phone_number like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {
        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);

        //
        $mensaje_id = $args['mensaje_id'];

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "maquetas_mensajes_copias_phones",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.mensaje_id = ? {$search_clause}";
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
                                      
                                            Where t.mensaje_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $mensaje_id
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
    public function GetAll($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];

        //
        $results = MaquetasMensajesCopiasPhones::GetAll($app_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }












    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = MaquetasMensajesCopiasPhones::GetRecordById( $app_id, $args['mensaje_id'], $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = array();


        //
        $phone_cc = Helper::safeVar($request->getParsedBody(), 'phone_cc2');
        $phone_number = Helper::safeVar($request->getParsedBody(), 'phone_number2');
        $active = Helper::safeVar($request->getParsedBody(), 'active2') ? 1 : 0;
        //
        if ( !$phone_cc ){
            $results['error'] = "proporciona la clave del pais";
            return $response->withJson($results, 200);
        }
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
            return $response->withJson($results, 200);
        }


        //
        $mensaje_id = $args['mensaje_id'];
        $mensaje_info = MaquetasMensajes::GetRecordById($app_id, $mensaje_id);
        //var_dump($mensaje_info); exit;
        if ( isset($mensaje_info['error']) && $mensaje_info['error'] ){
            $results['error'] = $mensaje_info['error'];
            return $response->withJson($results, 200);
        }



        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "stmt" => "
                   Insert Into maquetas_mensajes_copias_phones
                  ( app_id, maqueta_id, mensaje_id, phone_cc, phone_number, active, 
                    datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, ?,
                    GETDATE() )
                  ;SELECT SCOPE_IDENTITY()   
                ",
            "params" => [
                $app_id,
                $mensaje_info['maqueta_id'],
                $mensaje_id,
                $phone_cc,
                $phone_number,
                $active
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);



        //
        return $response->withJson($insert_results, 200);
    }









    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = array();


        //
        $phone_cc = Helper::safeVar($request->getParsedBody(), 'phone_cc2');
        $phone_number = Helper::safeVar($request->getParsedBody(), 'phone_number2');
        $active = Helper::safeVar($request->getParsedBody(), 'active2') ? 1 : 0;
        //
        if ( !$phone_cc ){
            $results['error'] = "proporciona la clave del pais";
            return $response->withJson($results, 200);
        }
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
            return $response->withJson($results, 200);
        }



        //
        $mensaje_id = $args['mensaje_id'];
        $record_id = $args['id'];



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update 
                    maquetas_mensajes_copias_phones
                  
                  Set
                    phone_cc = ?,
                    phone_number = ?,
                    active = ?
                  
                  Where app_id = ?
                  And mensaje_id = ?
                  And id = ?

                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $phone_cc,
                $phone_number,
                $active,
                //
                $app_id,
                $mensaje_id,
                $record_id
            ],
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }















    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


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
            "stmt" => "Delete FROM maquetas_mensajes_copias_phones
                        Where app_id = ?
                        And mensaje_id = ?
                        And id = ?
                     ;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $args['mensaje_id'],
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