<?php
namespace Controllers\Buses;

//
use App\Paths;

use App\Catalogues\CatModels;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class CatModelsController extends BaseController
{






    public static $table_name = "v_cat_models";



    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/buses/cat_models.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }






    //
    public static function configSearchClause($search_value, $make_id){
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
        //
        if ( is_numeric($make_id) ){
            //
            $search_clause .= " And t.make_id = $make_id ";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = self::$table_name;
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']), trim($request->getQueryParam("mkid")) );
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
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
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.account_id = ? {$search_clause}";
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
                                      
                                                 
                                            WHERE t.account_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $account_id
                ),

                //
                "parseRows" => function(&$row){


                    //
                    $files_path = Paths::$path_categories . DS . $row['id'];
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_categories . UD . $row['id'] . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_categories . UD . $row['id'] . UD . "thumb." . $img_ext;
                        }
                    }

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
        $app_ses = $request->getAttribute("app");
        //dd($ses_data);

        //
        $account_id = null;
        if (isset($ses_data['account_id'])){
            $account_id = $ses_data['account_id'];
        }
        //echo $account_id; exit;

        $results = [];

        //
        $make_id = $request->getQueryParam("mid");

        if ($make_id){
            //
            $stmt = "SELECT
                        t.*
                            
                            FROM v_cat_models t
                            
                            Where t.account_id = ?
                            And t.make_id = ?
                            And t.active = 1

                        Order By t.id Asc";
            //
            $results = Query::Multiple($stmt, [$account_id, $make_id]);
            //var_dump($results); exit;
        }

        //
        return $response->withJson($results, 200);
    }












    //
    public function GetAllForSite($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        $app_ses = $request->getAttribute("app");
        //var_dump($ses_data); exit;

        //
        if ($ses_data){
            $account_id = $ses_data['account_id'];
            $user_id = $ses_data['id'];
        }
        else if ($app_ses){
            $account_id = $app_ses['account_id'];
        }
        //echo $account_id; exit;

        //
        $results = CatModels::GetAllForSite($account_id);
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
        $results = CatModels::GetRecordById( $account_id, $args['id'] );
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
        $results = array();


        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $url = Helper::safeVar($request->getParsedBody(), 'url');
        $fa_icon = Helper::safeVar($request->getParsedBody(), 'fa_icon');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $make_id = Helper::safeVar($request->getParsedBody(), 'make_id');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$url ){
            $results['error'] = "proporciona el URL";
            return $response->withJson($results, 200);
        }
        //
        $fa_icon = ($fa_icon) ? $fa_icon : null;
        $description = ($description) ? $description : null;



        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into cat_models
                  ( account_id, nombre, make_id, fa_icon, url, 
                    description, active, datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?,
                    ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $account_id,
                $nombre,
                $make_id,
                $fa_icon,
                $url,
                $description,
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
        $results = array();



        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $url = Helper::safeVar($request->getParsedBody(), 'url');
        $fa_icon = Helper::safeVar($request->getParsedBody(), 'fa_icon');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$url ){
            $results['error'] = "proporciona el URL";
            return $response->withJson($results, 200);
        }
        //
        $fa_icon = ($fa_icon) ? $fa_icon : null;
        $description = ($description) ? $description : null;






        //
        $record_id = $args['id'];







        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update 
                    cat_models
                  
                  Set
                    nombre = ?,
                    url = ?,
                    fa_icon = ?,
                    description = ?,
                    active = ?
                   
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $nombre,
                $url,
                $fa_icon,
                $description,
                $active,
                //
                $account_id,
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
            "stmt" => "Delete FROM cat_models Where account_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $account_id,
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