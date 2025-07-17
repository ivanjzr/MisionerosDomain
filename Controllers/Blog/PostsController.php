<?php
namespace Controllers\Blog;

//
use App\Paths;
use App\Blog\Posts;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class PostsController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/blog/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }
    //
    public function ViewEdit($request, $response, $args) {
        //
        $view_data = [
            "App" => new App(null, $request->getAttribute("ses_data")),
            'record_id' => $args['id']
        ];
        //
        return $this->container->php_view->render($response, 'admin/blog/edit.phtml', $view_data);
    }









    //
    public static function configSearchClause($search_value, $filter_category_id){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' )
                    )";
        }
        //
        if ( $filter_category_id ){
            //
            $search_clause .= " And t.category_id = $filter_category_id";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "posts";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']), $request->getQueryParam("filter_category_id"));
        //echo $search_clause; exit;
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
                "records_stmt" => function($where_row_clause) use ($table_name, $order_field, $order_direction, $search_clause){
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
                                        cat.category
                                      
                                        From {$table_name} t
                                      
                                            Left Join cat_blog_categories cat On cat.id = t.category_id
                                                 
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

                    //
                    Posts::setPostFields($row);
                    //
                    unset($row['contenido']);


                    // SET PROD IMAGES URL
                    $files_path = Paths::$path_posts . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_posts . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
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
        $results = Posts::GetAll($app_id);
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
        $results = Posts::GetRecordById( $app_id, $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }















    //
    public function UpsertRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];



        //
        $record_id = ( isset($args['id']) && $args['id'] ) ? $args['id'] : null;




        //
        $results = array();

        //
        $uploadedFiles = $request->getUploadedFiles();
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $category_id = Helper::safeVar($request->getParsedBody(), 'category_id');
        $url = Helper::safeVar($request->getParsedBody(), 'url');
        $contenido = Helper::safeVar($request->getParsedBody(), 'contenido');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;



        //
        if ( !filter_var($url, FILTER_VALIDATE_URL) ){
            //$results['error'] = "proporciona un url valido";
            //return $response->withJson($results, 200);
        }

        //$url =  urlencode($url);
        //var_dump($url); exit;


        //
        $img_section = false;
        $file_extension = null;
        // get imagen large
        if ( isset($uploadedFiles['img_section']) &&
            ( $uploadedFile = $uploadedFiles['img_section'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $img_section = $uploadedFile;
            //
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
        }
        //var_dump($img_section); exit;



        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !is_numeric($category_id) ){
            $results['error'] = "proporciona la categoria";
            return $response->withJson($results, 200);
        }
        if ( !$url ){
            $results['error'] = "proporciona el URL";
            return $response->withJson($results, 200);
        }

        // Validaciones Add mode
        if ( !$record_id ){

            //
            if ( !($img_section && in_array($file_extension, Helper::$valid_img_types)) ){
                $results['error'] = "se requiere un formato de imagen valido";
                return $response->withJson($results, 200);
            }
        }



        //
        //echo $contenido; exit;
        $contenido = ($contenido) ? $contenido : null;






        //
        $add_edit_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertBlogPost(?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($app_id, $nombre, $category_id, $url, $contenido, $user_id, $active, $record_id, &$add_edit_record_id){
                return [
                    //
                    array($nombre, SQLSRV_PARAM_IN),
                    array($category_id, SQLSRV_PARAM_IN),
                    array($url, SQLSRV_PARAM_IN),
                    array($contenido, SQLSRV_PARAM_IN),
                    //
                    array($user_id, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    array($app_id, SQLSRV_PARAM_IN),
                    //
                    array($record_id, SQLSRV_PARAM_IN),
                    array(&$add_edit_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);

        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }

        //
        if ($add_edit_record_id){
            //
            $results['id'] = $add_edit_record_id;
            //
            if ( $img_section && $file_extension ){
                $results['img_results'] = Posts::updateImage($app_id, $img_section, $file_extension, $add_edit_record_id);
            }
        }


        //
        return $response->withJson($results, 200);
    }







}