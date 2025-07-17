<?php
namespace Controllers\Buses;

//
use App\Paths;
use App\Buses\BusesGallery;

use Controllers\BaseController;
//
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class BusesGalleryController extends BaseController
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
                "table_name" => "buses_images",

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
                                      
                                        t.*
                                        
                                        From {$table_name} t
                                                    
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

                    //
                    $bus_id = $row['bus_id'];
                    $img_gallery_id = $row['id'];
                    $img_ext = $row['img_ext'];
                    //
                    $bus_path = Paths::$path_buses . DS . $bus_id;
                    $gallery_files_path = $bus_path .DS . "gallery";
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( $img_ext ){
                        //
                        $img_path = $gallery_files_path.DS."orig-" . $img_gallery_id . "." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_buses . UD . $bus_id . UD . "gallery" . UD . "orig-" . $img_gallery_id . "." . $img_ext;
                        }
                        //
                        $thumb_img_path = $gallery_files_path.DS."thumb-" . $img_gallery_id . "." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_buses . UD . $bus_id . UD . "gallery" . UD . "thumb-" . $img_gallery_id . "." . $img_ext;
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
        $results = BusesGallery::GetAll($args['id']);
        //var_dump($results); exit;

        //
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
        $bus_id = $args['id'];


        //
        $uploadedFiles = $request->getUploadedFiles();

        //
        $img_section = false;
        $file_extension = null;
        // get imagen large
        if ( isset($uploadedFiles['gallery_img_section']) &&
            ( $uploadedFile = $uploadedFiles['gallery_img_section'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $img_section = $uploadedFile;
            //
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
        }
        //var_dump($img_section); exit;

        //
        if ( !($img_section && in_array($file_extension, Helper::$valid_img_types)) ){
            $results['error'] = "se requiere un formato de imagen valido";
            return $response->withJson($results, 200);
        }





        //
        if ( isset($img_section) && ($img_section) ) {



            //
            $insert_results = BusesGallery::Create(
                array(
                    "bus_id" => $bus_id,
                    "app_id" => $app_id
                ));
            //var_dump($insert_results); exit;

            if ( isset($insert_results['error']) && $insert_results['error'] ){
                $results['error'] = $insert_results['error'];
                return $response->withJson($results, 200);
            }
            //
            $bus_gallery_id = $insert_results['id'];




            //$img_nombre = $img_section->getClientFilename();
            $new_img_name = "orig-" . $bus_gallery_id . "." . $file_extension;
            $new_img_thumb_name = "thumb-" . $bus_gallery_id . "." . $file_extension;
            //
            $bus_path = Paths::$path_buses . DS . $bus_id;
            $gallery_files_path = $bus_path .DS . "gallery";
            //
            if (!is_dir($bus_path)){
                mkdir($bus_path);
            }
            //
            if (!is_dir($gallery_files_path)){
                mkdir($gallery_files_path);
            }



            // file
            if (!ImagesHandler::resizeImage($img_section->file, 886, 960,  $gallery_files_path . DS . $new_img_name)) {
                $insert_results['img_resize'] = "unable to add gallery file {$new_img_name}";
            } else {
                $insert_results['img_resize'] = "gallery file {$new_img_name} added success";
            }
            // thumb
            if (!ImagesHandler::resizeImage($img_section->file, 200, 217,  $gallery_files_path . DS . $new_img_thumb_name, true, true)) {
                $insert_results['img_resize'] = "unable to add gallery thumb file {$new_img_thumb_name}";
            } else {
                $insert_results['img_thumb_resize'] = "gallery thumb file {$new_img_thumb_name} added success";
            }


            //
            $insert_results['update_img_ext'] = BusesGallery::UpdateImgExt($file_extension, $app_id, $bus_id, $bus_gallery_id);
        }



        //
        return $response->withJson($insert_results, 200);
    }


















    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd); exit;
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
        $results = BusesGallery::Remove($app_id, $args['id'], $id);
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