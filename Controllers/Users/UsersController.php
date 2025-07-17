<?php
namespace Controllers\Users;

//
use App\Paths;
use App\Locations\CatPaises;
use Controllers\BaseController;
//
use App\App;
use Helpers\Helper;
//
use App\Sucursales\Sucursales;
use App\Users\Users;
use App\Users\UsersSucursales;
use Helpers\ImagesHandler;
use Helpers\ValidatorHelper;
use Helpers\Query;


//
class UsersController extends BaseController
{

    

    //
    public function Index($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        
        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;

        //
        return $this->container->php_view->render($response, 'admin/users/index.phtml', [
            "App" => new App(null, $ses_data)
        ]);
    }


    //
    public function Edit($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        
        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;

        //
        return $this->container->php_view->render($response, 'admin/users/edit.phtml', array(
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
                        ( t.name like '%$search_value%' ) Or 
                        ( t.email like '%$search_value%' ) Or
                        ( t.phone_number like '%$search_value%' ) 
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        //
        $table_name = "v_users";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        
        



        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;

        

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
                                      
                                            Where t.account_id = ?
                                            
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
                "parseRows" => function(&$row) use($account_id){
                    
                    //
                    $row_user_id = $row['id'];

                    //
                    if ( isset($row["is_admin"]) && $row["is_admin"] ){
                        $arr_sucursales = Sucursales::GetAll($account_id);
                    } else {
                        $arr_sucursales = UsersSucursales::GetAll($account_id, $row_user_id);
                    }
                    //dd($arr_sucursales);
                    $row['sucursales'] = $arr_sucursales;


                    //
                    $files_path = Paths::$path_users . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_users . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_users . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
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
        //dd($ses_data);
        $account_id = $ses_data['account_id'];


        //
        $record_id = $args['id'];

        //echo "$account_id, $record_id"; exit;
        $results = Users::GetRecordById( $account_id, $record_id);
        //dd($results); exit;


        //
        if (isset($results['id'])){
            //
            $files_path = Paths::$path_users . DS . $record_id;
            $main_files_path = $files_path .DS . "main";
            //
            $results["orig_img_url"] = "";
            $results["thumb_img_url"] = "";
            //
            if ( isset($results['img_ext']) && ($img_ext = $results['img_ext']) ){
                //
                $img_path = $main_files_path.DS."orig." . $img_ext;
                if ( is_file($img_path) ){
                    $results["orig_img_url"] = Paths::$url_users . UD . $record_id . UD . "main" . UD . "orig." . $img_ext;
                }
                //
                $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                if ( is_file($thumb_img_path) ){
                    $results["thumb_img_url"] = Paths::$url_users . UD . $record_id . UD . "main" . UD . "thumb." . $img_ext;
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
        $account_id = $ses_data['account_id'];


        //
        $results = Users::GetAll($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }





    

    //
    public function GetAllAvailable($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        

        //
        $results = Users::GetAllAvailable($account_id);
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
        //dd($ses_data);
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];



        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;



        //
        $results = array();

        //
        $v = new ValidatorHelper();
        $body = $request->getParsedBody();

        //
        $name = Helper::safeVar($body, 'name');
        //
        $sys_title_id = Helper::safeVar($body, 'sys_title_id');
        $email = Helper::safeVar($body, 'email');
        //
        $phone_country_id = Helper::safeVar($body, 'phone_country_id');
        $phone_cc = Helper::safeVar($body, 'phone_cc');
        $phone_number = Helper::safeVar($body, 'phone_number');
        //
        $password = Helper::safeVar($body, 'password');
        $notes = Helper::safeVar($body, 'notes');
        //
        $is_admin = Helper::safeVar($body, 'is_admin') ? 1 : 0;




        //$password = "abcd1234";$password_hash = EncryptHelper::hash($password);echo $password_hash; exit;


        //
        if ( !$name ){
            $results['error'] = "proporciona el nombre"; return $response->withJson($results, 200);
        }
        if ( !$sys_title_id ){
            $results['error'] = "proporciona el titulo";
            return $response->withJson($results, 200);
        }
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "proporciona el email";
            return $response->withJson($results, 200);
        }
        if ( !$password ){
            $results['error'] = "proporciona la clave";
            return $response->withJson($results, 200);
        }
        //
        $notes = ($notes) ? $notes : null;



        /*
         * GET PH#1
         * */
        if ( !is_numeric($phone_country_id) ){
            $results['error'] = "Provide Country Id"; return $response->withJson($results, 200);
        }

         //
         $countries_results = CatPaises::GetById($phone_country_id);
         //dd($countries_results); exit;
         if ( isset($countries_results['error']) && $countries_results['error'] ){
             $results['error'] = $countries_results["error"];
             return $response->withJson($results, 200);
         }
         //
         if ( !(isset($countries_results['id']) && $countries_results['id']) ){
             $results['error'] = "Country does not exists";
             return $response->withJson($results, 200);
         }
 
         //
         $phone_cc = $countries_results['phone_cc'];
         //
         if ( !$v->validateString([10, 10], $phone_number) ){
             $results['error'] = "Provide a valid phone number";
             return $response->withJson($results, 200);
         }
         $phone_number_1 = "+".$phone_cc . $phone_number;
         // DEBUG PHONES
         //echo $phone_number_1; exit;






        //
        $insert_contact = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into contacts
                  ( 
                    account_id, app_id, name, 
                    email, phone_country_id, phone_cc, phone_number, 
                    notes, datetime_created 
                  )
                  Values
                  ( 
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, GETDATE() 
                  )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $account_id,
                $app_id,
                $name,
                $email,
                $phone_country_id,
                $phone_cc,
                $phone_number,
                $notes
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);
        //dd($insert_contact);
        //
        if ( isset($insert_contact["error"]) && $insert_contact["error"] ){
            return $response->withJson($insert_contact, 200);
        }
        $contact_id = $insert_contact["id"];
        //echo $contact_id; exit;

        //
        $insert_user = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into users
                  ( 
                    account_id, app_id, contact_id, sys_title_id,
                    username, password, is_admin, notes, 
                    datetime_created 
                  )
                  Values
                  ( 
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    GETDATE() 
                  )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $account_id,
                $app_id,
                $contact_id,
                $sys_title_id,
                $email,
                $password,
                $is_admin,
                $notes
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);
        //dd($insert_user);

        //
        $insert_user['insert_contact'] = $insert_contact;


        //
        return $response->withJson($insert_user, 200);
    }


    //
    public function UpdateRecord($request, $response, $args) {
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $ses_user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];


        $v = new ValidatorHelper();

        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;




        //dd($ses_data); exit;
        //
        if (!$ses_data['is_admin']){
            $results['error'] = "only admins can update users";
            return $response->withJson($results, 200);
        }



        //
        $results = array();


        //
        $body = $request->getParsedBody();

        //
        $name = Helper::safeVar($body, 'name');
        //
        $email = Helper::safeVar($body, 'email');
        $sys_title_id = Helper::safeVar($body, 'sys_title_id');
        $password = Helper::safeVar($body, 'password');
        //
        $phone_country_id = Helper::safeVar($body, 'phone_country_id');
        $phone_cc = Helper::safeVar($body, 'phone_cc');
        $phone_number = Helper::safeVar($body, 'phone_number');
        $notes = Helper::safeVar($body, 'notes');
        //
        $active = Helper::safeVar($body, 'active') ? 1 : 0;
        $is_admin = Helper::safeVar($body, 'is_admin') ? 1 : 0;




        //
        if ( !$name ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "proporciona el email";
            return $response->withJson($results, 200);
        }
        if ( !$sys_title_id ){
            $results['error'] = "proporciona el titulo";
            return $response->withJson($results, 200);
        }
        //
        $notes = ($notes) ? $notes : null;




        /*
         * GET PH#1
         * */
        if ( !is_numeric($phone_country_id) ){
            $results['error'] = "Provide Country Id"; return $response->withJson($results, 200);
        }

         //
         $countries_results = CatPaises::GetById($phone_country_id);
         //dd($countries_results); exit;
         if ( isset($countries_results['error']) && $countries_results['error'] ){
             $results['error'] = $countries_results["error"];
             return $response->withJson($results, 200);
         }
         //
         if ( !(isset($countries_results['id']) && $countries_results['id']) ){
             $results['error'] = "Country does not exists";
             return $response->withJson($results, 200);
         }
 
         //
         $phone_cc = $countries_results['phone_cc'];
         //
         if ( !$v->validateString([10, 10], $phone_number) ){
             $results['error'] = "Provide a valid phone number";
             return $response->withJson($results, 200);
         }
         $phone_number_1 = "+".$phone_cc . $phone_number;
         // DEBUG PHONES
         //echo $phone_number_1; exit;
         



        //
        $record_id = $args['id'];



        //
        $contact_id = Users::getContactId($account_id, $record_id);
        //dd($contact_id);
        if (!$contact_id){
            $results['error'] = "Contact user not found";
            return $response->withJson($results, 200);
        }




        // 
        $stmt_1 = [$name, $email, $phone_country_id, $phone_cc, $phone_number, $account_id, $contact_id];
        //
        $update_contact = Query::DoTaskV2([
            "task" => "update",
            "debug" => true,
            "stmt" => function(){
                return "
                    Update 
                        contacts
                    Set 

                        name = ?,
                        email = ?,
                        phone_country_id = ?,
                        phone_cc = ?,
                        phone_number = ?

                    Where 
                        account_id = ?
                    And 
                        id = ?
                    ;SELECT @@ROWCOUNT
            ";
            },
            "params" => function() use($stmt_1){
                return $stmt_1;
            },
            "parse" => function($updated_rows, &$query_results) use($contact_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$contact_id;
            }
        ]);
        //dd($update_contact);



        //
        $base_params = [$sys_title_id, $email, $is_admin, $notes, $active];
        $password_params = $password ? [$password] : [];
        $final_params = [$account_id, $record_id];

        // Merge all
        $params = array_merge($base_params, $password_params, $final_params);

        //
        $password_clause = $password ? ", password = ?" : "";

        $stmt = "
            UPDATE users
            SET 
                sys_title_id = ?,
                username = ?,                
                is_admin = ?,
                notes = ?,
                active = ?
                {$password_clause}
            WHERE 
                account_id = ?
            AND 
                id = ?
            ; SELECT @@ROWCOUNT
        ";
        //
        $update_user = Query::DoTaskV2([
            "task" => "update",
            "debug" => true,
            "stmt" => function() use($stmt){
                return $stmt;
            },
            "params" => function() use($params){
                return $params;
            },
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //dd($update_user);


        $update_user['update_contact'] = $update_contact;

        
        //
        return $response->withJson($update_user, 200);
    }








    //
    public function PostUpdatePOSData($request, $response, $args) {



        /*
         * GET SESSION DATA
         * */
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;
        $account_id = $ses_data['account_id'];


        //dd($ses_data); exit;
        //
        if (!$ses_data['is_admin']){
            $results['error'] = "only admins can update users";
            return $response->withJson($results, 200);
        }

        //
        $results = array();

        //
        $pos_pin = Helper::safeVar($request->getParsedBody(), 'pos_pin');
        $is_pos_user = Helper::safeVar($request->getParsedBody(), 'is_pos_user') ? 1 : 0;
        $login_to_pos = Helper::safeVar($request->getParsedBody(), 'login_to_pos') ? 1 : 0;

        //
        $record_id = $args['id'];


        //
        if (!is_numeric($pos_pin)){
            $results['error'] = "proporciona el numero de PIN";
            return $response->withJson($results, 200);
        }


        //
        $update_results = Query::DoTaskV2([
            "task" => "update",
            "stmt" => function(){
                //
                return "
                    Update 
                        users
                    Set 
                        --
                        is_pos_user = ?,
                        pos_pin = ?,
                        login_to_pos = ?
                        
                      Where account_id = ?
                      And id = ?
                      ;SELECT @@ROWCOUNT
                ";
            },
            "params" => function() use($is_pos_user, $pos_pin, $login_to_pos, $account_id, $record_id){
                //
                return [
                    $is_pos_user,
                    $pos_pin,
                    $login_to_pos,
                    //
                    $account_id,
                    $record_id
                ];
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
        $account_id = $ses_data['account_id'];
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
            $files_path = Paths::$path_users . DS . $record_id;


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
                $results = Users::UpdateImgExt(
                    array(
                        "account_id" => $account_id,
                        "img_ext" => $file_extension,
                        "id" => $record_id
                    ));

            }
            //
            return $response->withJson($results, 200);
        }



        $results['error'] = "proporciona una imagen valida";
        return $response->withJson($results, 200);
    }









    //
    public function DeleteRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];




        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;




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
            "stmt" => "Delete FROM users Where account_id = ? And app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $account_id,
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
