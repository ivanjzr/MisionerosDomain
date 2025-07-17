<?php
namespace Controllers\Buses;

//
use App\Buses\BusesFeatures;

use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;


//
class BusesFeaturesController extends BaseController
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
        $bus_id = $args['id'];

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "buses_features",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.bus_id = ? {$search_clause}";
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
                                      
                                        t.*,
                                        feats.nombre
                                        
                                      
                                        From {$table_name} t
                                        
                                            Left Join cat_features feats On feats.id = t.feature_id
                                      
                                            Where t.app_id = ? 
                                            And t.bus_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $bus_id
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
        $feature_id = Helper::safeVar($request->getParsedBody(), 'feature_id');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;


        //
        if ( !($feature_id > 0) ){
            $results['error'] = "proporciona la etiqueta";
            return $response->withJson($results, 200);
        }

        //
        $bus_id = $args['id'];


        $active = 1;
        //
        $update_results = BusesFeatures::AddRecord($app_id, $bus_id, $feature_id, $user_id, $active);


        /*
        //
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_EDIT,
                "valor_nuevo" => "cliente folio: $cliente_id, nombre: $nombre, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($update_results, 200);
    }







    //
    public function GetAll($request, $response, $args) {


        //
        $results = BusesFeatures::GetAll($args['id']);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }










    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;


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
        $results = BusesFeatures::Remove($args['id'], $id);
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