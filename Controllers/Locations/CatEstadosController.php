<?php
namespace Controllers\Locations;

//
use App\Locations\CatCiudades;
use App\Locations\JobsApplications;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class CatEstadosController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'supadmin/locations/index.phtml', [
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
        $filter_pais_id = $request->getQueryParam("filter_pais_id", 0);

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "sys_cat_estados",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.pais_id = ? {$search_clause}";
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
                                      
                                            Where t.pais_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $filter_pais_id
                ),

                //
                "parseRows" => function(&$row){
                    //
                    $row['ciudades'] = CatCiudades::GetAll($row['id']);
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
        $filter_pais_id = $request->getQueryParam("filter_pais_id", 0);
        $filter_estado_id = $request->getQueryParam("filter_estado_id", 0);


        //
        $results = Query::Multiple("Select * From cat_estados Where pais_id = 1");

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
        $results = JobsApplications::GetRecordById($args['id'] );
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
        $pais_id = Helper::safeVar($request->getParsedBody(), 'pais_id');
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $abreviado = Helper::safeVar($request->getParsedBody(), 'abreviado');
        //
        if ( !is_numeric($pais_id) ){
            $results['error'] = "proporciona el pais";
            return $response->withJson($results, 200);
        }
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$abreviado ){
            $results['error'] = "proporciona la abreviacion";
            return $response->withJson($results, 200);
        }





        //
        $insert_results = JobsApplications::Create(
            array(
                "pais_id" => $pais_id,
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
        $record_id = $args['id'];







        //
        $update_results = JobsApplications::Edit(
            array(
                "nombre" => $nombre,
                "abreviado" => $abreviado,
                //
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
        $results = array();


        //
        $id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }


        //
        $results = JobsApplications::Remove( $id );
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