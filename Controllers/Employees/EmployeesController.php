<?php
namespace Controllers\Employees;

//
use App\Paths;

use Controllers\BaseController;
//
use App\App;
use Helpers\Helper;
use App\Locations\CatPaises;
//
use Helpers\ImagesHandler;
use Helpers\Query;

use Helpers\ValidatorHelper;
use App\Employees\Employees;


//
class EmployeesController extends BaseController
{

    

    //
    public function Index($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        

        //
        return $this->container->php_view->render($response, 'admin/employees/index.phtml', [
            "App" => new App(null, $ses_data)
        ]);
    }


    //
    public function Edit($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        
        //
        return $this->container->php_view->render($response, 'admin/employees/edit.phtml', array(
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
            $search_clause .= searchContact($search_value);
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
        $table_name = "v_employees";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
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
                    $files_path = Paths::$path_employees . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_employees . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_employees . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                        }
                    }

                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }






    //
    public function GetSearch($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        //
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //echo "$account_id, $app_id"; exit;
        
        

        //
        $search_text = $request->getQueryParam("q");
        //echo $search_text; exit;

        //
        $str_where = searchContact($search_text);
        //echo $str_where; exit;
        //
        $stmt = "
            Select Top 6 * From v_employees t
                Where t.account_id = ? 
                And t.app_id = ? 
                And t.active = 1
                {$str_where}
        ";
        //
        $results = Query::Multiple($stmt, [$account_id, $app_id]);

        //
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

        //
        $results = Employees::GetRecordById( $account_id, $record_id);
        //dd($results);


        //
        if (isset($results['id'])){
            //
            $files_path = Paths::$path_employees . DS . $record_id;
            $main_files_path = $files_path .DS . "main";
            //
            $results["orig_img_url"] = "";
            $results["thumb_img_url"] = "";
            //
            if ( isset($results['img_ext']) && ($img_ext = $results['img_ext']) ){
                //
                $img_path = $main_files_path.DS."orig." . $img_ext;
                if ( is_file($img_path) ){
                    $results["orig_img_url"] = Paths::$url_employees . UD . $record_id . UD . "main" . UD . "orig." . $img_ext;
                }
                //
                $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                if ( is_file($thumb_img_path) ){
                    $results["thumb_img_url"] = Paths::$url_employees . UD . $record_id . UD . "main" . UD . "thumb." . $img_ext;
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
        //dd($ses_data);
        $account_id = $ses_data['account_id'];


        //
        $results = Employees::GetAll($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }





    

    //
    public function GetAllAvailable($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        

        //
        $stmt = "Select 

                    t.id,
                    t.account_id,
                    t.departamento,
                    t.job_title,
                    t.name,
                    t.email,
                    t.img_ext,
                    t.phone_cc,
                    t.phone_number,
                    t.datetime_created,
                    t.notes,
                    t.active
                    
        from v_employees t Where t.account_id = ? And t.active = 1";
        $results = Query::Multiple($stmt, [$account_id]);
        //dd($results);

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
        $results = array();

        //
        $v = new ValidatorHelper();
        $body = $request->getParsedBody();

        //
        $name = Helper::safeVar($body, 'name');
        //
        $job_title_id = Helper::safeVar($body, 'job_title_id');
        $departamento_id = Helper::safeVar($body, 'departamento_id');
        $email = Helper::safeVar($body, 'email');
        //
        $phone_country_id = Helper::safeVar($body, 'phone_country_id');
        $phone_cc = Helper::safeVar($body, 'phone_cc');
        $phone_number = Helper::safeVar($body, 'phone_number');
        //
        $notes = Helper::safeVar($body, 'notes');
        //
        $active = Helper::safeVar($body, 'active') ? 1 : 0;




        


        //
        if ( !$name ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }
        if ( !$job_title_id ){
            $results['error'] = "proporciona el titulo laboral";
            return $response->withJson($results, 200);
        }
        if ( !$departamento_id ){
            $results['error'] = "proporciona el departamento";
            return $response->withJson($results, 200);
        }
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "proporciona el email";
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
                    account_id, app_id, name, email, 
                    phone_country_id, phone_cc, phone_number, notes, 
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




        // IX_contacts_email_unique
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into employees
                  ( 
                    account_id, app_id, contact_id, name, job_title_id,
                    departamento_id, email, phone_country_id, phone_cc, phone_number, 
                    active, notes, datetime_created 
                  )
                  Values
                  ( 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?,
                    ?, ?, GETDATE() 
                  )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $account_id,
                $app_id,
                $contact_id,
                $name,
                $job_title_id,
                $departamento_id,
                $email,
                $phone_country_id,
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
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $ses_user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];


        //
        $results = array();


        //
        $v = new ValidatorHelper();
        $body = $request->getParsedBody();

        //
        $name = Helper::safeVar($body, 'name');
        //
        $email = Helper::safeVar($body, 'email');
        $job_title_id = Helper::safeVar($body, 'job_title_id');
        $departamento_id = Helper::safeVar($body, 'departamento_id');
        //
        $phone_country_id = Helper::safeVar($body, 'phone_country_id');
        $phone_cc = Helper::safeVar($body, 'phone_cc');
        $phone_number = Helper::safeVar($body, 'phone_number');
        $notes = Helper::safeVar($body, 'notes');
        //
        $active = Helper::safeVar($body, 'active') ? 1 : 0;



        //
        if ( !$name ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "proporciona el email";
            return $response->withJson($results, 200);
        }
        if ( !$job_title_id ){
            $results['error'] = "proporciona el titulo";
            return $response->withJson($results, 200);
        }
        if ( !$departamento_id ){
            $results['error'] = "proporciona el departamento";
            return $response->withJson($results, 200);
        }
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
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
        $stmt = "
            Update 
                employees
            
            Set 
                --
                name = ?,
                email = ?,
                job_title_id = ?,
                departamento_id = ?,
                --
                phone_country_id = ?,
                phone_cc = ?,
                phone_number = ?,
                notes = ?,
                --
                active = ?
                
                Where account_id = ?
                And id = ?
            ;SELECT @@ROWCOUNT
        ";
        $params = [
            $name,
            $email,
            $job_title_id,
            $departamento_id,
            //
            $phone_country_id,
            $phone_cc,
            $phone_number,
            $notes,
            //
            $active,
            //
            $account_id,
            $record_id
        ];
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
        //var_dump($update_results); exit;




        //
        return $response->withJson($update_user, 200);
    }








    //
    public function PostUpdateCommissions($request, $response, $args) {
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $ses_user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];
        $account_id = $ses_data['account_id'];


        //
        $results = array();


        //
        $body = $request->getParsedBody();

        //
        $commission_rate = Helper::safeVar($body, 'commission_rate');


        $commission_rate = (int)$commission_rate;
        //echo $commission_rate; exit;
        //
        if (!is_numeric($commission_rate) || !is_int($commission_rate) || $commission_rate < 0 || $commission_rate > 100) {
            $results['error'] = "La comisión debe ser un número entero entre 0 y 100"; 
            return $response->withJson($results, 200);
        }
      

        //
        $record_id = $args['id'];



        //
        $params =[
            $commission_rate,
            //
            $account_id,
            $record_id
        ];
        //
        $update_results = Query::DoTaskV2([
            "task" => "update",
            "debug" => true,
            "stmt" => function(){
                return "
                    Update 
                        employees
                    Set
                        commission_rate = ?
                    Where 
                        account_id = ?
                    And 
                        id = ?
                    ;SELECT @@ROWCOUNT
                ";
            },
            "params" => function() use($params){
                return $params;
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
        //dd($ses_data);
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
            $files_path = Paths::$path_employees . DS . $record_id;


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
                $results = Employees::UpdateImgExt(
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
            "stmt" => "Delete FROM employees Where account_id = ? And app_id = ? And id = ?;SELECT @@ROWCOUNT",
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
