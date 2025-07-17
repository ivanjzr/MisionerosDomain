<?php
namespace Controllers\Promociones;

//

use Controllers\BaseController;

//
use App\App;
//
use Helpers\Helper;

//
use App\Promociones\Promociones;
use Helpers\Query;


//
class PromocionesController extends BaseController
{




    //
    public function ViewIndex($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        //
        return $this->container->php_view->render($response, 'admin/promos/index.phtml', [
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
        $table_name = "v_promos_discounts";
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
        $results = Query::Multiple("Select * from promos_discounts Where app_id = ? And (enabled_pdv = 1 Or enabled_apps = 1)", [$app_id]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }








    //
    public function GetForPOSTotals($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];
        

        $results = Query::Multiple("
            SELECT * 
            FROM promos_discounts 
            WHERE app_id = ? 
            AND enabled_pdv = 1
            AND fecha_hora_inicio <= GETDATE() 
            AND fecha_hora_fin >= GETDATE()
        ", [$app_id]);

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
        $results = Query::Single("Select * From v_promos_discounts Where app_id = ? And id = ?", [$app_id, $record_id]);
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
        //
        $es_porcentaje = Helper::safeVar($request->getParsedBody(), 'es_porcentaje') ? 1 : 0;
        $valor = Helper::safeVar($request->getParsedBody(), 'valor');
        //
        $fecha_hora_inicio = Helper::safeVar($request->getParsedBody(), 'fecha_hora_inicio');
        $fecha_hora_fin = Helper::safeVar($request->getParsedBody(), 'fecha_hora_fin');
        
        //
        $enabled_pdv = Helper::safeVar($request->getParsedBody(), 'enabled_pdv') ? 1 : 0;
        $enabled_apps = Helper::safeVar($request->getParsedBody(), 'enabled_apps') ? 1 : 0;



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
        if ( !( $valor && $valor > 0 ) ){
            $results['error'] = "proporciona el valor";
            return $response->withJson($results, 200);
        }

        //
        if (!$fecha_hora_inicio_obj = Helper::is_valid_date($fecha_hora_inicio, "Y-m-d H:i", false)){
            $results['error'] = "proporciona la fecha de inicio";
            return $response->withJson($results, 200);
        }
        //
        if (!$fecha_hora_fin_obj = Helper::is_valid_date($fecha_hora_fin, "Y-m-d H:i", false)){
            $results['error'] = "proporciona la fecha de finalizacion";
            return $response->withJson($results, 200);
        }
        //dd($fecha_hora_inicio_obj); exit;

        //
        $valor = ( is_numeric($valor) ) ? $valor: 0;


        //
        if ($fecha_hora_fin_obj <= $fecha_hora_inicio_obj){
            $results['error'] = "la fecha y hora de fin de promocion no puede ser menor o igual a inicio";
            return $response->withJson($results, 200);
        }

        // Validar que la fecha fin sea al menos 1 hora mayor que la fecha inicio
        $fecha_hora_inicio_mas_1h = (clone $fecha_hora_inicio_obj)->modify("+1 hour");
        //
        if ($fecha_hora_fin_obj < $fecha_hora_inicio_mas_1h){
            $results['error'] = "la fecha y hora de fin debe ser al menos 1 hora mayor que la fecha de inicio";
            return $response->withJson($results, 200);
        }
        //dd($fecha_hora_inicio_obj_2);


        
        // devuelve las fechas en formato para insercion
        $fecha_hora_inicio = $fecha_hora_inicio_obj->format("Y-m-d H:i");
        $fecha_hora_fin = $fecha_hora_fin_obj->format("Y-m-d H:i");
        //echo "$fecha_hora_inicio $fecha_hora_fin"; exit;

        
        


     
        
        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into promos_discounts
                  ( 
                    clave, descripcion, es_porcentaje, valor, 
                    fecha_hora_inicio, fecha_hora_fin, 
                    app_id, enabled_pdv, enabled_apps, datetime_created
                  )
                  Values
                  ( 
                    ?, ?, ?, ?,
                    ?, ?, 
                    ?, ?, ?, GETDATE()
                  )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $clave,
                $descripcion,
                $es_porcentaje,
                $valor,
                //
                $fecha_hora_inicio,
                $fecha_hora_fin,
                //
                $app_id,
                $enabled_pdv,
                $enabled_apps
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
        $fecha_hora_inicio = Helper::safeVar($request->getParsedBody(), 'fecha_hora_inicio');
        $fecha_hora_fin = Helper::safeVar($request->getParsedBody(), 'fecha_hora_fin');
        //
        $enabled_pdv = Helper::safeVar($request->getParsedBody(), 'enabled_pdv') ? 1 : 0;
        $enabled_apps = Helper::safeVar($request->getParsedBody(), 'enabled_apps') ? 1 : 0;



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
        if (!$fecha_hora_inicio = Helper::is_valid_date($fecha_hora_inicio, "Y-m-d H:i", "Y-m-d H:i")){
            $results['error'] = "proporciona la fecha de inicio";
            return $response->withJson($results, 200);
        }
        //
        if (!$fecha_hora_fin = Helper::is_valid_date($fecha_hora_fin, "Y-m-d H:i", "Y-m-d H:i")){
            $results['error'] = "proporciona la fecha de finalizacion";
            return $response->withJson($results, 200);
        }
        //var_dump($fecha_hora_fin); exit;









        //
        $record_id = $args['id'];



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update promos_discounts
                       Set
                        clave = ?,
                        descripcion = ?,
                        fecha_hora_inicio = ?,
                        fecha_hora_fin = ?,
                        enabled_pdv = ?,
                        enabled_apps = ?
                        
                      Where app_id = ?
                      And id = ?
                      
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $clave,
                $descripcion,
                $fecha_hora_inicio,
                $fecha_hora_fin,
                $enabled_pdv,
                $enabled_apps,
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
            "stmt" => "Delete FROM promos_discounts Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
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
