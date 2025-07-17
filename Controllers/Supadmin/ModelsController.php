<?php
namespace Controllers\Supadmin;

//
use App\Accounts\Accounts;
use App\Paths;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class ModelsController extends BaseController
{






    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'supadmin/models/index.phtml', array(
            "App" => new App(null, $request->getAttribute("ses_data"))
        ));
    }












    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.company_name like '%$search_value%' ) Or
                        ( t.contact_name like '%$search_value%' ) 
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
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "sys_admin_secciones",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.parent_id Is Null {$search_clause}";
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
                                      ,ROW_NUMBER() OVER (ORDER BY orden asc) as row
                                    From
                                      (Select
                                      
                                        t.*,
                                        --
                                        lag(t.orden) over (partition by parent_id order by t.orden) as prev_orden,
                                        lead(t.orden) over (partition by parent_id order by t.orden) as next_orden
                                      
                                        From {$table_name} t
                                      
                                            
                                            Where t.parent_id Is Null
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },
                //
                "params" => array(
                ),
                //
                "parseRows" => function(&$row){
                    //
                    $row['children'] = self::GetAll($row['id']);
                }
            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public static function GetAll($parent_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                  
                    t.*,
				    --
                    lag(t.orden) over (partition by parent_id order by t.orden) as prev_orden,
                    lead(t.orden) over (partition by parent_id order by t.orden) as next_orden
                        
                        FROM sys_admin_secciones t
                        
                           Where t.parent_id = ?

                            Order by t.orden Asc
			";
            },
            "params" => [
                $parent_id
            ],
            "parse" => function(){

            }
        ]);
    }







    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;

        //
        $id = $args['menu_id'];


        //
        $results = Query::Get([
            "stmt" => function(){
                return "SELECT t.* FROM sys_admin_secciones t Where t.id = ?
                ";
            },
            "params" => [
                $id
            ]
        ]);

        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }







    //
    public function PostUpsertRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $results = array();


        //
        $parent_id = Helper::safeVar($request->getParsedBody(), 'parent_id');
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $clave = Helper::safeVar($request->getParsedBody(), 'clave');
        $model_name = Helper::safeVar($request->getParsedBody(), 'model_name');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $fa_icon = Helper::safeVar($request->getParsedBody(), 'fa_icon');
        $orden = Helper::safeVar($request->getParsedBody(), 'orden');
        //
        $is_only_admin = Helper::safeVar($request->getParsedBody(), 'is_only_admin') ? 1 : 0;
        $is_menu_expandible = Helper::safeVar($request->getParsedBody(), 'is_menu_expandible') ? 1 : 0;


        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$clave ){
            $results['error'] = "proporciona la clave";
            return $response->withJson($results, 200);
        }
        if ( !$fa_icon ){
            $results['error'] = "proporciona el fa_icon";
            return $response->withJson($results, 200);
        }
        if ( !is_numeric($orden) ){
            $results['error'] = "proporciona el orden";
            return $response->withJson($results, 200);
        }
        //
        $parent_id = (is_numeric($parent_id)) ? $parent_id : null;
        $model_name = ($model_name) ? $model_name : null;
        $description = ($description) ? $description : null;



        //
        $menu_id = (isset($args['menu_id']) && $args['menu_id']) ? $args['menu_id'] : null;



        //
        $param_record_id = 0;
        $param_oper_type = 'operation_add_edit';
        //
        $sp_res = Query::StoredProcedure([
            "ret" => "single",
            "debug" => true,
            "stmt" => function(){
                return "{call usp_SysMenusUpsert(?,?,?,?,?,?,?,?,?,?,?,?)}";
            },
            "params" => function() use($menu_id, $parent_id, $nombre, $clave, $model_name, $description, $fa_icon, $orden, $is_menu_expandible, $is_only_admin, &$param_record_id, &$param_oper_type){
                //
                return [
                    //
                    array($parent_id, SQLSRV_PARAM_IN),
                    array($nombre, SQLSRV_PARAM_IN),
                    array($clave, SQLSRV_PARAM_IN),
                    array($model_name, SQLSRV_PARAM_IN),
                    array($description, SQLSRV_PARAM_IN),
                    array($fa_icon, SQLSRV_PARAM_IN),
                    array($orden, SQLSRV_PARAM_IN),
                    array($is_menu_expandible, SQLSRV_PARAM_IN),
                    array($is_only_admin, SQLSRV_PARAM_IN),
                    //
                    array($menu_id, SQLSRV_PARAM_IN),
                    //
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                    array(&$param_oper_type, SQLSRV_PARAM_OUT)
                ];
            },
        ]);
        //
        $results = array();
        $results['id'] = $param_record_id;
        $results['oper_type'] = $param_oper_type;
        //
        //var_dump($sp_res); var_dump($results);  exit;


        //
        return $response->withJson($results, 200);
    }













    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;


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
            "debug" => true,
            "stmt" => "Delete FROM sys_admin_secciones Where id = ?;SELECT @@ROWCOUNT",
            "params" => [
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












    //
    public function PostUpdateOrder($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $results = array();


        //
        $id = Helper::safeVar($request->getParsedBody(), 'id');
        $new_orden = Helper::safeVar($request->getParsedBody(), 'new_orden');
        //
        if ( !$id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }
        //
        if ( !( $new_orden === "up" || $new_orden === "down" ) ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }



        //
        $param_oper_type = 'abcdefhijklmnopqrstuvwxyzabcdefhijklmnopqrstuvwxyz';
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_SysMenusUpdateOrder(?,?,?)}";
            },
            "params" => function() use($id, $new_orden, &$param_oper_type){
                return [
                    array($id, SQLSRV_PARAM_IN),
                    array($new_orden, SQLSRV_PARAM_IN),
                    //
                    array(&$param_oper_type, SQLSRV_PARAM_OUT)
                ];
            },
        ]);
        //
        $results = array();
        $results['id'] = $id;
        $results['oper_type'] = $param_oper_type;
        //var_dump($sp_res); var_dump($results);  exit;


        //
        return $response->withJson($results, 200);
    }





}