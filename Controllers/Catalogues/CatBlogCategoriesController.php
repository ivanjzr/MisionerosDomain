<?php
namespace Controllers\Catalogues;

//
use App\Paths;

use App\Catalogues\CatBlogCategories;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class CatBlogCategoriesController extends BaseController
{






    public static $table_name = "cat_blog_categories";



    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/catalogues/cat_blog_categories.phtml', [
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
                        ( t.nombre like '%$search_value%' ) Or
                        ( t.description like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = self::$table_name;
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
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
                                      
                                                 
                                            WHERE t.app_id = ?
                                            
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


                    //
                    $files_path = Paths::$path_tipos_productos . DS . $row['id'];
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_tipos_productos . UD . $row['id'] . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_tipos_productos . UD . $row['id'] . UD . "thumb." . $img_ext;
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
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = CatBlogCategories::GetAll($app_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }













    //
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = CatBlogCategories::GetRecordById( $app_id, $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }










    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();


        //
        $category = Helper::safeVar($request->getParsedBody(), 'category');
        $url = Helper::safeVar($request->getParsedBody(), 'url');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;
        //
        if ( !$category ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$url ){
            $results['error'] = "proporciona el URL";
            return $response->withJson($results, 200);
        }
        //
        $description = ($description) ? $description : null;


        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "stmt" => "
                   Insert Into cat_blog_categories
                  ( app_id, category, url, description, active, 
                   datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?,
                   GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id,
                $category,
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
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $category = Helper::safeVar($request->getParsedBody(), 'category');
        $url = Helper::safeVar($request->getParsedBody(), 'url');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;
        //
        if ( !$category ){
            $results['error'] = "proporciona el category";
            return $response->withJson($results, 200);
        }
        if ( !$url ){
            $results['error'] = "proporciona el URL";
            return $response->withJson($results, 200);
        }
        //
        $description = ($description) ? $description : null;






        //
        $record_id = $args['id'];







        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update 
                    cat_blog_categories
                  
                  Set
                    category = ?,
                    url = ?,
                    description = ?,
                    active = ?
                    
                  
                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $category,
                $url,
                $description,
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
            "stmt" => "Delete FROM cat_blog_categories Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
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