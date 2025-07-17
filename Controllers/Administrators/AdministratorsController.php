<?php
namespace Controllers\Administrators;

//
use App\Paths;

use Controllers\BaseController;
//
use App\App;
use Helpers\Helper;
//
use App\Administrators\Administrators;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class AdministratorsController extends BaseController
{


    //
    public function Index($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'supadmin/administrators/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }


    //
    public function Edit($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'supadmin/administrators/edit.phtml', array(
            "App" => new App(null, $request->getAttribute("ses_data")),
            'record_id' => $args['id']
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
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.email like '%$search_value%' ) Or
                        ( t.phone_number like '%$search_value%' ) 
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
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "administrators",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where 1=1 {$search_clause}";
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
                                        
                                            Where 1=1
                                            
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
                    $files_path = Paths::$path_administrators . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_administrators . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_administrators . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                        }
                    }

                }

            ]
        );
        //var_dump($results); exit;
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
        $record_id = $args['id'];

        //
        $results = Administrators::GetRecordById( $record_id);
        //var_dump($results); exit;



        //
        if (isset($results['id'])){
            //
            $files_path = Paths::$path_administrators . DS . $record_id;
            $main_files_path = $files_path .DS . "main";
            //
            $results["orig_img_url"] = "";
            $results["thumb_img_url"] = "";
            //
            if ( isset($results['img_ext']) && ($img_ext = $results['img_ext']) ){
                //
                $img_path = $main_files_path.DS."orig." . $img_ext;
                if ( is_file($img_path) ){
                    $results["orig_img_url"] = Paths::$url_administrators . UD . $record_id . UD . "main" . UD . "orig." . $img_ext;
                }
                //
                $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                if ( is_file($thumb_img_path) ){
                    $results["thumb_img_url"] = Paths::$url_administrators . UD . $record_id . UD . "main" . UD . "thumb." . $img_ext;
                }
            }
        }




        //
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
        $results = Administrators::GetAll();
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
        $email = Helper::safeVar($request->getParsedBody(), 'email');
        //
        $phone_cc = Helper::safeVar($request->getParsedBody(), 'phone_cc');
        $phone_number = Helper::safeVar($request->getParsedBody(), 'phone_number');
        //
        $password = Helper::safeVar($request->getParsedBody(), 'password');
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');
        //
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;





        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre completo";
            return $response->withJson($results, 200);
        }
        if ( !$phone_cc ){
            $results['error'] = "proporciona la clave del telefono";
            return $response->withJson($results, 200);
        }
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
            return $response->withJson($results, 200);
        }
        if ( !$email ){
            $results['error'] = "proporciona el email";
            return $response->withJson($results, 200);
        }
        if ( !$password ){
            $results['error'] = "proporciona la clave";
            return $response->withJson($results, 200);
        }
        //
        $notes = ($notes) ? $notes : null;






        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into administrators
                  ( 
                    nombre, email, password, phone_cc, 
                    phone_number, active, notes, datetime_created 
                  )
                  Values
                  ( 
                    ?, ?, ?, ?, 
                    ?, ?, ?, GETDATE() 
                  )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $nombre,
                $email,
                $password,
                $phone_cc,
                $phone_number,
                $active,
                $notes
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



        /*
         * GET SESSION DATA
         * */
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;



        //
        $results = array();



        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $password = Helper::safeVar($request->getParsedBody(), 'password');
        //
        $phone_cc = Helper::safeVar($request->getParsedBody(), 'phone_cc');
        $phone_number = Helper::safeVar($request->getParsedBody(), 'phone_number');
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');
        //
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;





        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre completo";
            return $response->withJson($results, 200);
        }
        if ( !$phone_cc ){
            $results['error'] = "proporciona la clave del telefono";
            return $response->withJson($results, 200);
        }
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
            return $response->withJson($results, 200);
        }
        //
        $notes = ($notes) ? $notes : null;


        //
        $record_id = $args['id'];


        //
        $update_results = Query::DoTaskV2([
            "task" => "update",
            "stmt" => function() use($password){
                //
                if ($password){
                    return "
                   Update 
                    administrators
                  
                  Set 
                    --
                    nombre = ?,
                    password = ?,
                    phone_cc = ?,
                    --
                    phone_number = ?,
                    notes = ?,
                    active = ?
                    
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                ";
                }
                //
                else {
                    return "
                   Update 
                    administrators
                  
                  Set 
                    --
                    nombre = ?,
                    phone_cc = ?,
                    phone_number = ?,
                    --
                    notes = ?,
                    active = ?
                  
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                ";
                }
            },
            "params" => function() use($nombre, $phone_cc, $phone_number, $password, $active, $notes, $record_id){
                //
                if ($password){
                    return [
                        $nombre,
                        $password,
                        $phone_cc,
                        $phone_number,
                        $notes,
                        $active,
                        $record_id
                    ];
                }
                //
                else {
                    return [
                        $nombre,
                        $phone_cc,
                        $phone_number,
                        $notes,
                        $active,
                        $record_id
                    ];
                }
            },
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
    public function PostUploadImage($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $record_id = $args['id'];
        $uploadedFiles = $request->getUploadedFiles();



        //
        if ( isset($uploadedFiles['img_section']) &&
            ( $uploadedFile = $uploadedFiles['img_section'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $img_section = $uploadedFile;
            //
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);



            //
            $files_path = Paths::$path_administrators . DS . $record_id;


            //
            $main_files_path = $files_path .DS . "main";
            //
            if (!is_dir($files_path)){
                mkdir($files_path);
            }
            //
            if (!is_dir($main_files_path)){
                mkdir($main_files_path);
            }

            //$img_nombre = $img_section->getClientFilename();
            $new_img_name = "orig." . $file_extension;
            $new_img_thumb_name = "thumb." . $file_extension;


            //---------------------------MAIN
            if (!ImagesHandler::resizeImage($img_section->file, 128, 128,  $main_files_path . DS . $new_img_name, false, true)) {
                array_push($results['img_resize_img'], "unable to upload image " . $new_img_thumb_name);
            }
            //
            else {

                //
                $results = Administrators::UpdateImgExt(
                    array(
                        "img_ext" => $file_extension,
                        "id" => $record_id
                    ));

                if ( isset($_SESSION[APP_TYPE_SUPADMIN]) && isset($_SESSION[APP_TYPE_SUPADMIN]['id']) ){
                    //
                    $image_url = Paths::$url_administrators.UD.$record_id.UD."main".UD."orig.".$file_extension;;;
                    $_SESSION[APP_TYPE_SUPADMIN]['image_url'] = $image_url;
                }

            }
            //
            return $response->withJson($results, 200);
        }



        $results['error'] = "proporciona una imagen valida";
        return $response->withJson($results, 200);
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
        $results = Query::DoTask([
            "task" => "delete",
            "stmt" => "Delete FROM administrators Where id = ?;SELECT @@ROWCOUNT",
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





}
