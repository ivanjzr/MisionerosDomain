<?php
namespace Controllers\Locations;

//
use App\Locations\CatCiudades;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class CatCiudadesController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/locations/index.phtml', [
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
                        ( t.nombre like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {

        /*
         * GET SESSION DATA
         * */
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;



        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);

        //
        $estado_id = $args['estado_id'];

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "sys_cat_ciudades",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.estado_id = ? {$search_clause}";
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
                                      
                                            Where t.estado_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $estado_id
                ),

                //
                "parseRows" => function(&$row){
                    //
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }











    //
    public function GetAll($request, $response, $args) {

        /*
         * GET SESSION DATA
         * */
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;

        //
        $estado_id = $args['estado_id'];


        //
        $results = Query::Multiple("Select * From cat_ciudades Where estado_id = ?", [$estado_id]);

        //
        return $response->withJson($results, 200);
    }










    //
    public function GetRecord($request, $response, $args) {


        /*
         * GET SESSION DATA
         * */
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;



        //
        $estado_id = $args['estado_id'];


        //
        $results = CatCiudades::GetRecordById($estado_id, $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function AddRecord($request, $response, $args) {


        /*
          * GET SESSION DATA
          * */
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;



        //
        $results = array();


        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $abreviado = Helper::safeVar($request->getParsedBody(), 'abreviado');
        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$abreviado ){
            $results['error'] = "proporciona la abreviacion";
            return $response->withJson($results, 200);
        }



        //
        $estado_id = $args['estado_id'];



        //
        $insert_results = CatCiudades::Create(
            array(
                "estado_id" => $estado_id,
                "nombre" => $nombre,
                "abreviado" => $abreviado
            ));

        //var_dump($insert_results); exit;
        if ( isset($insert_results['error']) && $insert_results['error'] ){
            $results['error'] = $insert_results['error'];
            return $response->withJson($results, 200);
        }




        //
        return $response->withJson($insert_results, 200);
    }

















    //
    public function UpdateRecord($request, $response, $args) {


        /*
         * GET SESSION DATA
         * */
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;




        //
        $results = array();



        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $abreviado = Helper::safeVar($request->getParsedBody(), 'abreviado');
        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$abreviado ){
            $results['error'] = "proporciona la abreviacion";
            return $response->withJson($results, 200);
        }





        //
        $estado_id = $args['estado_id'];
        $record_id = $args['id'];







        //
        $update_results = CatCiudades::Edit(
            array(
                "nombre" => $nombre,
                "abreviado" => $abreviado,
                //
                "estado_id" => $estado_id,
                "id" => $record_id
            )
        );

        //var_dump($update_results); exit;
        if ( isset($update_results['error']) && $update_results['error'] ){
            $results['error'] = $update_results['error'];
            return $results;
        }

        /*
        //
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_EDIT,
                "valor_nuevo" => "cliente folio: $cliente_id, feature: $feature, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($update_results, 200);
    }















    //
    public function DeleteRecord($request, $response, $args) {


        /*
         * GET SESSION DATA
         * */
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;





        //
        $estado_id = $args['estado_id'];


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
        $results = CatCiudades::Remove( $estado_id, $id );
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