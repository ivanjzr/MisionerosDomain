<?php
namespace Controllers\Loc;

//
use App\Databases\MySqli;
use App\Databases\MySqliHelper;
use App\Paths;


use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class CiudadesController extends BaseController
{







    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/ciudades/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }



    //
    public static function configSearchClause($search_value, $estado_id){
        //echo $estado_id; exit;
        //
        $search_clause = "";

        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.ciudad_nombre like '%$search_value%' )
                    )";
        }

        if (is_numeric($estado_id)){
            //
            $search_clause .= " And t.estado_id = {$estado_id} ";
        }

        //echo $search_clause; exit;
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {

        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);


        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']), $request->getQueryParam("eid") );
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
                "count_stmt" => function() use ($search_clause){
                    //
                    return "Select COUNT(*) total From ViewCiudades t Where 1=1 {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $search_clause){
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
                                        
                                        From ViewCiudades t
                                                 
                                            WHERE 1=1
                                            
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
    public function GetAll($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);




        //
        $str_where = "";
        //
        $state_id = $request->getQueryParam("sid");
        if ($state_id){
            $str_where .= " And t.estado_id = {$state_id}";
        }
        //
        $exclude_ruta_id = $request->getQueryParam("erid");
        if ($exclude_ruta_id){
            $str_where .= " And t.id Not In (Select ciudad_id From rutas_config Where ruta_id = {$exclude_ruta_id}) ";
        }
        //echo $str_where; exit;




        //
        $results = Query::Multiple("select t.* from ViewCiudades t where 1 = 1 {$str_where} order by t.ciudad_nombre Asc", []);
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


        //
        $results = Query::Single("select * from ViewCiudades where id = ?", [$args['id']]);
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


        $app_id = Helper::getAppByAccountId($account_id);
        //echo $app_id; exit;


        //
        $results = array();

        //
        $estado_id = Helper::safeVar($request->getParsedBody(), 'estado_id');
        $ciudad_nombre = Helper::safeVar($request->getParsedBody(), 'ciudad_nombre');
        //
        $loc_identifier = Helper::safeVar($request->getParsedBody(), 'loc_identifier');
        $loc_notes = Helper::safeVar($request->getParsedBody(), 'loc_notes');
        $loc_identifier = ($loc_identifier) ? $loc_identifier : null;
        $loc_notes = ($loc_notes) ? $loc_notes : null;
        //
        $ciudad_address = Helper::safeVar($request->getParsedBody(), 'ciudad_address');
        $ciudad_lat = Helper::safeVar($request->getParsedBody(), 'ciudad_lat');
        $ciudad_lng = Helper::safeVar($request->getParsedBody(), 'ciudad_lng');






        //
        if ( !is_numeric($estado_id) ){
            $results['error'] = "proporciona el estado";
            return $response->withJson($results, 200);
        }
        if ( !$ciudad_nombre ){
            $results['error'] = "proporciona el nombre de la ciudad";
            return $response->withJson($results, 200);
        }
        if ( !$ciudad_address ){
            $results['error'] = "proporciona la direccion de la ciudad";
            return $response->withJson($results, 200);
        }
        if ( !$ciudad_lat ){
            $results['error'] = "proporciona la latitud de la ciudad";
            return $response->withJson($results, 200);
        }
        if ( !$ciudad_lng ){
            $results['error'] = "proporciona la longitud de la ciudad";
            return $response->withJson($results, 200);
        }




        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into cat_ciudades
                  ( estado_id, ciudad_nombre, loc_identifier, loc_notes, ciudad_address, ciudad_lat, ciudad_lng, app_id )
                  Values
                  ( ?, ?, ?, ?, ?, ?, ?, ? )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $estado_id,
                $ciudad_nombre,
                $loc_identifier,
                $loc_notes,
                $ciudad_address,
                $ciudad_lat,
                $ciudad_lng,
                $app_id
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


        $app_id = Helper::getAppByAccountId($account_id);
        //echo $app_id; exit;




        //
        $results = array();



        //
        $ciudad_address = Helper::safeVar($request->getParsedBody(), 'ciudad_address');
        //
        $loc_identifier = Helper::safeVar($request->getParsedBody(), 'loc_identifier');
        $loc_notes = Helper::safeVar($request->getParsedBody(), 'loc_notes');
        $loc_identifier = ($loc_identifier) ? $loc_identifier : null;
        $loc_notes = ($loc_notes) ? $loc_notes : null;
        //
        $ciudad_lat = Helper::safeVar($request->getParsedBody(), 'ciudad_lat');
        $ciudad_lng = Helper::safeVar($request->getParsedBody(), 'ciudad_lng');


        //
        if ( !$ciudad_address ){
            $results['error'] = "proporciona la direccion de la ciudad";
            return $response->withJson($results, 200);
        }
        if ( !$ciudad_lat ){
            $results['error'] = "proporciona la latitud de la ciudad";
            return $response->withJson($results, 200);
        }
        if ( !$ciudad_lng ){
            $results['error'] = "proporciona la longitud de la ciudad";
            return $response->withJson($results, 200);
        }



        //
        $ciudad_id = $args['id'];

        //
        $updated_records = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_CiudadesUpdate(?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "debug" => true,
            "params" => function() use($ciudad_id, $loc_identifier, $loc_notes, $ciudad_address, $ciudad_lat, $ciudad_lng, $app_id, &$updated_records){
                return [
                    //
                    array($ciudad_id, SQLSRV_PARAM_IN),
                    array($loc_identifier, SQLSRV_PARAM_IN),
                    array($loc_notes, SQLSRV_PARAM_IN),
                    array($ciudad_address, SQLSRV_PARAM_IN),
                    array($ciudad_lat, SQLSRV_PARAM_IN),
                    array($ciudad_lng, SQLSRV_PARAM_IN),
                    array($app_id, SQLSRV_PARAM_IN),
                    //
                    array(&$updated_records, SQLSRV_PARAM_OUT),
                ];
            }
        ]);

        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }

        //
        $results['id'] = $ciudad_id;
        $results['updated_records'] = $updated_records;

        //
        return $response->withJson($results, 200);
    }




    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];


        $app_id = Helper::getAppByAccountId($account_id);
        //echo $app_id; exit;


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
            "stmt" => "Delete FROM cat_ciudades Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
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