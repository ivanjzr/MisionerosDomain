<?php
namespace Controllers\Contacts;

//
use App\Locations\CatPaises;
use App\Paths;

//
use Controllers\BaseController;
use App\App;
use App\Contacts\Contacts;

use Helpers\Geolocation;
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;
use Helpers\ValidatorHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;




//
class ContactsController extends BaseController
{





   


    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/contacts/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "user_session_data" => $request->getAttribute("ses_data"),
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
        return $this->container->php_view->render($response, 'admin/contacts/edit.phtml', $view_data);
    }



    





    




    //
    public static function configSearchClause($search_value, $filter_type){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= searchContact($search_value);
        }
        //
        if ($filter_type == "c"){            
            $search_clause .= "And t.is_customer = 1";
        }
        if ($filter_type == "p"){            
            $search_clause .= "And t.is_supplier = 1";
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
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']), trim($request->getQueryParam("ft")));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];
        //echo $order_direction; exit;


        //
        $table_name = "v_contacts";
        //echo $table_name; exit;



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
                    //echo $where_row_clause; exit;
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
                "parseRows" => function(&$row){
                    //
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }








    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];

        //
        $contact_id = $args['id'];

        //
        $results = Contacts::GetRecordById($account_id, $contact_id);


        //
        return $response->withJson($results, 200);
    }





    





    public static function getImage($file_type_ext, $imagePath){
        if ($file_type_ext === 'jpeg' || $file_type_ext === 'jpg') {
            return imagecreatefromjpeg($imagePath);
        } elseif ($file_type_ext === 'png') {
            return imagecreatefrompng($imagePath);
        } elseif ($file_type_ext === 'gif') {
            return imagecreatefromgif($imagePath);
        }
    }


    public static function image_fix_orientation(&$image, $filename){
        //
        $exif = exif_read_data($filename);;
        //
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;

                case 6:
                    $image = imagerotate($image, 90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, -90, 0);
                    break;
            }
        }
    }













    

    

    //
    public function PostSendEmail($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];

        //
        $contact_id = $args['id'];
        //echo " $contact_id $email_type "; exit;



        //
        $v = new ValidatorHelper();

        $body = $request->getParsedBody();

        //
        $email_type = Helper::safeVar($body, 'email_type');
        //
        if ( !$email_type ){
            $results['error'] = "Provide email type"; 
            return $response->withJson($results, 200);
        }

        //
        $contact_info = $contact_info = Query::Single("select * from v_contacts where id = ?", [$contact_id]);
        $contact_email = $contact_info['email'];
        $contact_name = $contact_info['name'];

        //
        $maqueta_id = null;
        if ($email_type==="register"){
            $maqueta_id = MAQUETA_ID_CUST_REGISTRO;
        }
        else if ($email_type==="recup_acct"){
            $maqueta_id = MAQUETA_ID_CUST_RECUP_CTA;
        }
        else {
            $results['error'] = "proporciona un tipo de correo valido para cliente";
            return $response->withJson($results, 200);
        }
        //echo $maqueta_id; exit;


        //
        $send_to_copies = true;
        //
        $send_email_results = Helper::SendEmail($account_id, $app_id, $maqueta_id, $contact_name, $contact_email, $send_to_copies, function($maqueta_email_msg) use($contact_info){
            return Contacts::ParseCustomerMessages($contact_info, $maqueta_email_msg);
        });




        return $response->withJson($send_email_results, 200);
    }





    




    
    




    //
    public function AddRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();

        

        //
        $v = new ValidatorHelper();

        $body = $request->getParsedBody();

        //
        $name = Helper::safeVar($body, 'name');
        $email = Helper::safeVar($body, 'email');
        $phone_country_id = Helper::safeVar($body, 'phone_country_id');
        $phone_number = Helper::safeVar($body, 'phone_number');
        $notes = Helper::safeVar($body, 'notes');
        
        


        //
        if ( !$name ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }
        //
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "Provide valid email"; return $response->withJson($results, 200);
        }
        

        


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
        $contact_info = Query::Single("select * from v_contacts where app_id = ? and 
        (
            (phone_cc = ? And phone_number = ?) or (email = ?)
        )
            ", [$app_id, $phone_cc, $phone_number, $email]);
        //dd($contact_info); exit;
        if ( $contact_info && isset($contact_info['id']) ){
            $results['error'] = "ya existe cliente con mismo correo o telefono"; return $response->withJson($results, 200);
        }
        //echo "test"; exit;
         



        // IX_contacts_email_unique
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into contacts
                  ( name, email, 
                    phone_country_id, phone_cc, phone_number,
                    notes, account_id, app_id, datetime_created )
                  Values
                  ( ?, ?,
                    ?, ?, ?, 
                    ?, ?, ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()   
                ",
            "params" => [
                $name,
                $email,
                $phone_country_id,
                $phone_cc,
                $phone_number,
                $notes,
                $account_id,
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
        $app_id = $ses_data['app_id'];
        



        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;

        //
        $contact_id = $args['id'];



        //
        $name = $v->safeVar($body_data, 'name');
        $email  = $v->safeVar($body_data, 'email');
        //
        $phone_country_id = $v->safeVar($body_data, 'phone_country_id');
        $phone_number = $v->safeVar($body_data, 'phone_number');
        //
        $notes = $v->safeVar($body_data, 'notes');
        $is_archived = $v->safeVar($body_data, 'is_archived') ? 1 : 0;


        //
        if ( !$v->validateString([2, 256], $name) ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }


        //
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
        $email = (filter_var($email, FILTER_VALIDATE_EMAIL) ) ? $email : null;
        $notes = ($notes) ? $notes : null;



        //
        $contact_info = Query::Single("select * from v_contacts where app_id = ? And id = ?", [$app_id, $contact_id]);
        //dd($contact_info);
        if ( !($contact_info && isset($contact_info['id'])) ){
            $results['error'] = "No se encontro el cliente que intentas editar"; return $response->withJson($results, 200);
        }
        //
        $contact_info = Query::Single("select * from v_contacts where app_id = ? and id != ? And
        (
            (phone_cc = ? And phone_number = ?) or (email = ?)
        )
            ", [$app_id, $contact_id, $phone_cc, $phone_number, $email]);
        //dd($contact_info); exit;
        if ( $contact_info && isset($contact_info['id']) ){
            $results['error'] = "ya existe cliente con mismo correo o telefono"; return $response->withJson($results, 200);
        }
        //echo "test"; exit;

        
         


        
        $params = [
            $name,
            $email,
            $phone_country_id,
            $phone_cc,
            $phone_number,
            $notes,
            $is_archived,
            $account_id,
            $contact_id
        ];
        //dd($params);


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    contacts
                  
                  Set
                    name = ?,
                    email = ?,
                    phone_country_id = ?,
                    phone_cc = ?,
                    phone_number = ?,
                    notes = ?,
                    is_archived = ?
                    
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => $params,
            "parse" => function($updated_rows, &$query_results) use($contact_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$contact_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }










    //
    public function UploadXlsFile($request, $response, $args)
    {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $user_id = $ses_data['id'];
        $sucursal_id = $ses_data['sucursal_id'];
        //echo $sucursal_id; exit;

        //
        $results = array();
        //
        $uploadedFiles = $request->getUploadedFiles();
        //dd($uploadedFiles); exit;



        //
        $file_xls = false;
        $file_extension = null;
        // get imagen large
        if ( isset($uploadedFiles['xls_file']) &&
            ( $uploadedFile = $uploadedFiles['xls_file'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $file_xls = $uploadedFile;
            //
            $file_extension = pathinfo($file_xls->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
        }
        //echo $file_extension; exit;
        //dd($file_xls); exit;
        //echo $file_xls->getClientFilename(); exit;


        //
        $inputFileType = IOFactory::identify($file_xls->file);
        //var_dump($inputFileType); exit;


        $reader = IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($file_xls->file);
        $rows = $spreadsheet->getActiveSheet()->toArray();




        //
        $sucursal_id = 7;
        $product_sucursal_active = 7;
        $user_id = 14;


        //
        $the_res = array();
        //
        $the_res['inserts'] = array();

        //
        foreach( $rows as $row_idx => $row ){
            //dd($row); exit;
            //
            if ( $row_idx > 0 ){
                //dd($row); exit;

                //
                $email = null;
                $name = null;
                $phone = null;
                $address1 = null;
                $address2 = null;
                $city = null;
                $state = null;
                $country = null;
                $postal_code = null;
                $created_at = null;
                $last_updated_at = null;
                $last_activity_type = null;
                $last_activity_at = null;
                $worker = null;
                $member = null;
                $private_page_access = null;
                $subscriber = null;
                $blog_subscriber = null;
                $supressed = null;
                $supression_reazon = null;
                $supressed_at = null;
                $tracking_disabled = null;



                //
                foreach( $row as $col_idx => $col ){
                    //dd($col);
                    //
                    if ($col_idx === 0){$email = $col;}
                    if ($col_idx === 1){$name = $col;}
                    if ($col_idx === 3){$phone = $col;}
                    if ($col_idx === 4){$address1 = $col;}
                    if ($col_idx === 5){$address2 = $col;}
                    if ($col_idx === 6){$city = $col;}
                    if ($col_idx === 7){$state = $col;}
                    if ($col_idx === 8){$country = $col;}
                    if ($col_idx === 9){$postal_code = $col;}
                    if ($col_idx === 10){$created_at = $col;}
                    if ($col_idx === 11){$last_updated_at = $col;}
                    if ($col_idx === 12){$last_activity_type = $col;}
                    if ($col_idx === 13){$last_activity_at = $col;}
                    if ($col_idx === 14){$worker = $col;}
                    if ($col_idx === 15){$member = $col;}
                    if ($col_idx === 16){$private_page_access = $col;}
                    if ($col_idx === 17){$subscriber = $col;}
                    if ($col_idx === 18){$blog_subscriber = $col;}
                    if ($col_idx === 19){$supressed = $col;}
                    if ($col_idx === 20){$supression_reazon = $col;}
                    if ($col_idx === 21){$supressed_at = $col;}
                    if ($col_idx === 22){$tracking_disabled = $col;}
                }
                //dd($arr_prod); exit;
                //echo "$email, $name, $phone, $address1, $address2, $city, $state, $country, $postal_code, $created_at, $last_updated_at, $last_activity_type, $last_activity_at, $worker, $member, $private_page_access, $subscriber, $blog_subscriber, $supressed, $supression_reazon, $supressed_at, $tracking_disabled"; exit;


                //
                $worker_name = null;
                $phone_country_id = 1;
                $phone_cc = "+1";
                $active = 1;
                //
                $blog_subscriber = ( $blog_subscriber === "TRUE" ? 1 : 0 );

                //
                $username = substr($email, 0, strrpos($email, '@'));
                $phone = preg_replace('/\D+/', '', $phone);


                // SI NO ES UN PHONE VALIDO
                if ( !is_numeric($phone) ){
                    $phone = null;
                    $phone_country_id = null;
                    $phone_cc = null;
                }
                //echo $phone . " - " . $username; exit;



                //
                $insert_results = Query::DoTask([
                    "task" => "add",
                    "debug" => true,
                    "stmt" => "
                   Insert Into workers
                  ( worker_name, name,
                    username, email, phone_country_id, phone_cc, 
                    phone_number, address, address2, city_code, 
                    state_code, country_code, postal_code, blog_subscribed, 
                    active, datetime_created )
                  Values
                  ( ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()   
                ",
                    "params" => [
                        $worker_name,
                        $name,
                        $username,
                        $email,
                        $phone_country_id,
                        $phone_cc,
                        $phone,
                        $address1,
                        $address2,
                        $city,
                        $state,
                        $country,
                        $postal_code,
                        $blog_subscriber,
                        $active
                    ],
                    "parse" => function($insert_id, &$query_results){
                        $query_results['id'] = (int)$insert_id;
                    }
                ]);

                //
                if ( isset($insert_results['error']) && $insert_results['error'] ){
                    return $response->withJson($insert_results, 200);
                }

                //
                array_push($the_res['inserts'], $insert_results);
            }
        }


        //
        $the_res['success'] = true;


        //
        return $response->withJson($the_res, 200);
    }





    


    



    //
    public function PostDeleteRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);

        $account_id = $ses_data['account_id'];




        //
        $results = array();


        //
        $contact_id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$contact_id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }


        //
        $results = Query::DoTask([
            "task" => "delete",
            "debug" => false,
            "stmt" => "delete from contacts where account_id = ? And id = ?; SELECT @@ROWCOUNT",
            "params" => [
                $account_id,
                $contact_id
            ],
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "FK_users_contacts" => "No se puede eliminar por que tiene un usuario asociado"
            ],
            "parse" => function($updated_rows, &$query_results) use($contact_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$contact_id;
            }
        ]);
        //dd($results);
        

        //
        return $response->withJson($results, 200);
    }



}
