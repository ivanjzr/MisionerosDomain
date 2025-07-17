<?php
namespace Controllers\TiposPrecios;

//

use Controllers\BaseController;

//
use App\App;
//
use Helpers\Helper;

//
use Helpers\Query;


//
class TiposPreciosController extends BaseController
{




    //
    public function ViewIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        //
        return $this->container->php_view->render($response, 'admin/tipos_precios/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "usd" => $request->getAttribute("usd")
        ]);
    }









    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";

        //
        if ( $search_value ){
            $search_clause .= " And (
                        ( t.clave like '%$search_value%' ) Or 
                        ( t.descripcion like '%$search_value%' )
                    )";
        }
        //
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        //
        $app_id = Helper::getAppByAccountId($account_id);



        //
        $table_name = "tipos_precios";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];



        //
        $results = Query::PaginateRecordsV2(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            $request->getQueryParam("draw"),
            [

                /*
                 * Count Statement
                 * */
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? {$search_clause}";
                },

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
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        //
        $app_id = Helper::getAppByAccountId($account_id);


        //
        $results = Query::Multiple("Select * from tipos_precios Where app_id = ? And active = 1", [$app_id]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }










    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        //
        $app_id = Helper::getAppByAccountId($account_id);


        $record_id = $args['id'];

        //
        $results = Query::Single("Select * From tipos_precios Where app_id = ? And id = ?", [$app_id, $record_id]);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }





    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        //
        $app_id = Helper::getAppByAccountId($account_id);





        //
        $results = array();





        //
        $descripcion = Helper::safeVar($request->getParsedBody(), 'descripcion');
        $clave = Helper::safeVar($request->getParsedBody(), 'clave');
        $edad_minima = Helper::safeVar($request->getParsedBody(), 'edad_minima');
        $edad_maxima = Helper::safeVar($request->getParsedBody(), 'edad_maxima');
        //
        $tipo_precio_id = Helper::safeVar($request->getParsedBody(), 'tipo_precio_id');
        //
        $es_porcentaje = Helper::safeVar($request->getParsedBody(), 'es_porcentaje') ? 1 : 0;
        $valor = Helper::safeVar($request->getParsedBody(), 'valor');
        $tipo_sum_rest = Helper::safeVar($request->getParsedBody(), 'tipo_sum_rest');
        //
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;


        //
        if (!$descripcion){
            $results['error'] = "proporciona la descripcion";
            return $response->withJson($results, 200);
        }
        //
        if (!$clave){
            $results['error'] = "proporciona la clave";
            return $response->withJson($results, 200);
        }


        // si es 1 aplica tipo de precio,so valida el tipo
        if ( (int)$tipo_precio_id === 1 ){


            //
            if ( !(is_numeric($edad_minima) && is_numeric($edad_maxima)) ){
                $results['error'] = "proporciona la edad minima y maxima";
                return $response->withJson($results, 200);
            }
            //
            if ( $edad_minima > $edad_maxima ){
                $results['error'] = "la edad minima no puede ser mayor a la maxima";
                return $response->withJson($results, 200);
            }
            //
            if ( !($tipo_sum_rest === "s" || $tipo_sum_rest === "r") ){
                $results['error'] = "proporciona el tipo suma o resta";
                return $response->withJson($results, 200);
            }

        }
        //echo " $edad_minima $edad_maxima $tipo_sum_rest"; exit;

        //
        $valor = ( is_numeric($valor) ) ? $valor: 0;


        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into tipos_precios
                  ( 
                   clave, descripcion, edad_minima, edad_maxima, tipo_precio_id,
                   es_porcentaje, valor, tipo_sum_rest, 
                   app_id, active, datetime_created
                  )
                  Values
                  ( 
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, GETDATE()
                  )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $clave,
                $descripcion,
                $edad_minima,
                $edad_maxima,
                //
                $tipo_precio_id,
                //
                $es_porcentaje,
                $valor,
                $tipo_sum_rest,
                //
                $app_id,
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
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);






        //
        $results = array();





        //
        $descripcion = Helper::safeVar($request->getParsedBody(), 'descripcion');
        $clave = Helper::safeVar($request->getParsedBody(), 'clave');
        //
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;


        //
        if (!$descripcion){
            $results['error'] = "proporciona la descripcion";
            return $response->withJson($results, 200);
        }
        //
        if (!$clave){
            $results['error'] = "proporciona la clave";
            return $response->withJson($results, 200);
        }

          //
        $record_id = $args['id'];



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update tipos_precios
                       Set
                        clave = ?,
                        descripcion = ?,
                        active = ?
                        
                      Where app_id = ?
                      And id = ?
                      
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $clave,
                $descripcion,
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



        //
        return $response->withJson($update_results, 200);
    }












    //
    public function DeleteRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];



        //
        $app_id = Helper::getAppByAccountId($account_id);








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
            "stmt" => "Delete FROM tipos_precios Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
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
