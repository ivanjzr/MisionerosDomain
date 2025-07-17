<?php
namespace Controllers\Eventos;

//
use App\Eventos\Eventos;
use App\Eventos\EventosFechas;
use App\Eventos\EventosGallery;
use App\Paths;
//
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class EventosController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/eventos/index.phtml', [
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
        return $this->container->php_view->render($response, 'admin/eventos/edit.phtml', $view_data);
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
                        ( t.sku like '%$search_value%' ) Or
                        ( t.description like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "eventos";
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


                    //
                    $row['fechas'] = EventosFechas::GetAll($row['app_id'], $row['id']);


                    // SET PROD IMAGES URL
                    $files_path = Paths::$path_eventos . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_eventos . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_eventos . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
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
        $results = Eventos::GetAll($app_id);
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
        $results = Eventos::GetRecordById( $app_id, $args['id'] );
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
        $title = Helper::safeVar($request->getParsedBody(), 'title');
        $sub_title = Helper::safeVar($request->getParsedBody(), 'sub_title');
        $url = Helper::safeVar($request->getParsedBody(), 'url');
        $description = Helper::safeVar($request->getParsedBody(), 'description');
        $description_2 = Helper::safeVar($request->getParsedBody(), 'description_2');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;


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
        if ( !$title ){
            $results['error'] = "proporciona el title";
            return $response->withJson($results, 200);
        }
        if ( !$sub_title ){
            $results['error'] = "proporciona el title";
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
        $description = ($description) ? $description : null;
        $description_2 = ($description_2) ? $description_2 : null;






        //
        $add_edit_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertEventos(?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($app_id, $title, $sub_title, $url, $description, $description_2, $active, $record_id, &$add_edit_record_id){
                return [
                    //
                    array($title, SQLSRV_PARAM_IN),
                    array($sub_title, SQLSRV_PARAM_IN),
                    array($url, SQLSRV_PARAM_IN),
                    //
                    array($description, SQLSRV_PARAM_IN),
                    array($description_2, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array($app_id, SQLSRV_PARAM_IN),
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
                //
                if ( $record_id ){
                    $results['update_img_results_edit'] = self::updateEventoImage($app_id, $img_section, $file_extension, $add_edit_record_id);
                }
                //
                else {
                    $results['update_img_results_add'] = self::updateEventoImage($app_id, $img_section, $file_extension, $add_edit_record_id, true);
                }
            }
        }


        //
        return $response->withJson($results, 200);
    }






    //
    public static $main_img_width = 886;
    public static $main_img_height = 960;
    //
    public static $thumb_img_width = 200;
    public static $thumb_img_height = 217;


    public static function updateEventoImage($app_id, $img_section, $file_extension, $evento_id, $add_gallery_img = false){
        //var_dump($img_section); exit;
        //echo " $app_id $file_extension, $evento_id "; exit;


        //
        $results = array();


        //
        $files_path = Paths::$path_eventos . DS . $evento_id;
        $main_files_path = $files_path .DS . "main";
        //
        if (!is_dir($files_path)){
            mkdir($files_path);
        }
        //
        if (!is_dir($main_files_path)){
            mkdir($main_files_path);
        }



        /*
         * SI ES AGREGAR A LA GALERIA ENTONCES ESTALBECEMOS REMOVE IMG EN FALSE, EN CASO CONTRARIO ES TRUE
         * */
        $remove_img = ($add_gallery_img) ? false : true;



        //$img_title = $img_section->getClientFilename();
        $new_img_name = "orig." . $file_extension;
        $new_img_thumb_name = "thumb." . $file_extension;
        //---------------------------MAIN
        // original
        if (!ImagesHandler::resizeImage($img_section->file, self::$main_img_width, self::$main_img_height,  $main_files_path . DS . $new_img_name, false)) {
            array_push($update_results['img_resize_img'], "unable to upload thumb file " . $new_img_thumb_name);
        }


        // thumb
        if (!ImagesHandler::resizeImage($img_section->file, self::$thumb_img_width, self::$thumb_img_height,  $main_files_path . DS . $new_img_thumb_name, false, $remove_img)) {
            array_push($update_results['thumb_resize_img'], "unable to upload thumb file " . $new_img_thumb_name);
        }
        //
        $results['main_img'] = Eventos::UpdateImgExt(
            array(
                "img_ext" => $file_extension,
                //
                "app_id" => $app_id,
                "evento_id" => $evento_id
            ));
        //var_dump($results['main_img']); exit;





        //------------------------------ GALLERY IMG
        if ($add_gallery_img){
            //
            $results['gallery_insert'] = EventosGallery::Create(
                array(
                    "app_id" => $app_id,
                    "evento_id" => $evento_id
                ));
            //var_dump($results['gallery_insert']); exit;

            //var_dump($results['gallery_insert']); exit;
            if ( isset($results['gallery_insert']['error']) && $results['gallery_insert']['error'] ){
                $results['error'] = $results['gallery_insert']['error'];
                return $results;
            }
            //
            $evento_gallery_id = $results['gallery_insert']['id'];


            //
            $gallery_files_path = $files_path .DS . "gallery";
            //
            if (!is_dir($gallery_files_path)){
                mkdir($gallery_files_path);
            }

            //$img_title = $img_section->getClientFilename();
            $new_img_name = "orig-" . $evento_gallery_id . "." . $file_extension;
            $new_img_thumb_name = "thumb-" . $evento_gallery_id . "." . $file_extension;

            // file
            if (!ImagesHandler::resizeImage($img_section->file, self::$main_img_width, self::$main_img_height,  $gallery_files_path . DS . $new_img_name, false)) {
                array_push($update_results['img_resize_img'], "unable to upload thumb file " . $new_img_thumb_name);
            }
            // thumb
            if (!ImagesHandler::resizeImage($img_section->file, self::$thumb_img_width, self::$thumb_img_height,  $gallery_files_path . DS . $new_img_thumb_name, false, true)) {
                array_push($update_results['img_resize_img'], "unable to upload thumb file " . $new_img_thumb_name);
            }


            //
            $results['gallery_update'] = EventosGallery::UpdateImgExt(
                array(
                    "img_ext" => $file_extension,
                    "evento_id" => $evento_id,
                    //
                    "app_id" => $app_id,
                    "id" => $evento_gallery_id
                ));
            //var_dump($results['gallery_update']); exit;
        }


        //
        return $results;
    }







    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


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
            "stmt" => "Delete FROM eventos Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $id
            ],
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "FK_products_stands_events" => "Unable to remove event as it has associated booths"
            ],
            "debug" => false,
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