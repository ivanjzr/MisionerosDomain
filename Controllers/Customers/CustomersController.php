<?php
namespace Controllers\Customers;

//
use App\Locations\CatPaises;
use App\Paths;

//
use Controllers\BaseController;
use App\App;
use App\Customers\Customers;
use App\Contacts\Contacts;

use Helpers\Geolocation;
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;
use Helpers\ValidatorHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;




//
class CustomersController extends BaseController
{





   


    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/customers/index.phtml', [
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
        return $this->container->php_view->render($response, 'admin/customers/edit.phtml', $view_data);
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
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];
        //echo $order_direction; exit;


        //
        $table_name = "v_customers";
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
    public function GetSearch($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        //echo "$account_id, $app_id"; exit;
        
        

        //
        $search_text = $request->getQueryParam("q");
        //echo $search_text; exit;

        //
        $results = Customers::Search($account_id, $search_text);
        //dd($results); exit;


        //
        return $response->withJson($results, 200);
    }







    //
    public function GetSearchCustomerCitas($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $account_id = $ses_data['account_id'];
        $sucursal_id = $ses_data['sucursal_id'];
        $app_id = $ses_data['app_id'];
        //echo "$account_id, $app_id, $sucursal_id"; exit;

        //
        $customer_id = $args['customer_id'];
        //echo $customer_id; exit;

        //
        $search_text = $request->getQueryParam("q");
        //echo $search_text; exit;

        //
        $results = Query::Multiple("
            SELECT 
            
                ('cit-' + CAST(id AS VARCHAR)) as id,                
                app_id,
                sucursal_id,
                location_name,
                ('CITA-' + CAST(id AS VARCHAR)) as prod_code,
                title as nombre,
                1 as is_cita,
                tps,
                precio

            FROM v_appointments
            
                WHERE app_id = ? 
                AND customer_id = ?
                -- And sucursal_id = ?
                
                -- que sea estatus confirmada
                AND status_id = 1

                -- y que no tenga asociada ninguna venta
                And ( sale_id Is Null And sale_item_id Is Null )
                
        ", [$app_id, $customer_id]);
        //dd($results);
        //
        if ($results && isset($results['error'])){
            return $response->withJson($results, 200);
        }



        //
        return $response->withJson($results, 200);
    }





   



    //
    public function postUpdateBasicInfo($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $account_id = $ses_data['account_id'];
        $customer_id = $ses_data['id'];



        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;



        //
        $uploadedFiles = $request->getUploadedFiles();
        //dd($uploadedFiles); exit;

        //
        $name = Helper::getFirstTextOnly($v->safeVar($body_data, 'name'));
        $password = $v->safeVar($body_data, 'password');
        //echo " $name "; exit;


        //
        if ( !$v->validateString([2, 128], $name) ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }
        //
        if (!$password){
            $results['error'] = "Provide password"; return $response->withJson($results, 200);
        }



        //
        $info_results = Customers::GetRecordByIdAndPassword($account_id, $customer_id, $password);
        //dd($info_results); exit;
        if ( isset($info_results['error']) && $info_results['error'] ){
            $results['error'] = $info_results['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($info_results['id']) && $info_results['id']) ){
            $results['error'] = "Invalid password";
            return $response->withJson($results, 200);
        }



        //
        $img_section = null;
        $file_type_ext = null;
        //
        if ( isset($uploadedFiles['cust_img']) && $uploadedFiles['cust_img'] && $uploadedFiles['cust_img']->getError() === UPLOAD_ERR_OK ) {
            //
            $img_section = $uploadedFiles['cust_img'];
            //dd($img_section); exit;
            //
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_type_ext = strtolower($file_extension);
            //echo $file_type_ext; exit;
            // Validate type of file
            if( !in_array($file_type_ext, ['jpeg', 'jpg', 'png', 'gif']) ){
                //
                $results['error'] = "Solo se permiten archivos Jpeg, Png o Gif";
                return $response->withJson($results, 200);
            }
            //echo "Ok"; exit;
        } else {
            //$results['error'] = "Se require la imagen del usuario";
            //return $response->withJson($results, 200);
        }
        //dd($img_section); exit;
        //echo $file_type_ext; exit;


      


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update contacts 
                       Set
                           
						name = ?
						   
                      Where account_id = ?
                      And is_customer = ?
                      And id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $name,
                //
                $account_id,
                $customer_id
            ],
            "parse" => function($updated_rows, &$query_results) use($customer_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$customer_id;
            }
        ]);
        //dd($update_results); exit;




        //
        if ($img_section && $file_type_ext){


            //
            $customer_profile_path = Customers::getCustomerSectionPath($customer_id, "profile");
            $customer_profile_url = FULL_DOMAIN."/files/customers/".$customer_id."/profile";
            //echo " $customer_profile_path --- $customer_profile_url "; exit;


            // product && product original image
            $new_img_name = "me.{$file_type_ext}";
            //echo $new_img_name; exit;


            // elimina archivos de imagenes previos
            if ( is_file($customer_profile_path.DS."me.png") ){unlink($customer_profile_path.DS."me.png");}
            if ( is_file($customer_profile_path.DS."me.jpg") ){unlink($customer_profile_path.DS."me.jpg");}
            if ( is_file($customer_profile_path.DS."me.jpeg") ){unlink($customer_profile_path.DS."me.jpeg");}
            if ( is_file($customer_profile_path.DS."me.gif") ){unlink($customer_profile_path.DS."me.gif");}


            //
            if ( ImagesHandler::resizeImage(
                $img_section->file,
                250,
                250,
                $customer_profile_path.DS.$new_img_name
            )){

                //
                $results['store_img_ext'] = Query::DoTask([
                    "task" => "update",
                    "stmt" => "
                   Update contacts 
                       Set
                       
						img_ext = ?
						
                      Where account_id = ?
                      And is_customer = 1
                      And id = ?
                     ;SELECT @@ROWCOUNT
                ",
                    "params" => [
                        //
                        $file_type_ext,
                        //
                        $customer_id
                    ],
                    "parse" => function($updated_rows, &$query_results){
                        $query_results['affected_rows'] = $updated_rows;
                    }
                ]);
                //
                $update_results['customer_img_updated'] = true;

            } else {
                $update_results['err_msg'] = "Error, Unable to upload customer image";
            }
            /*
            //
            if (move_uploaded_file($img_section->file, $products_path.DS.$orig_img_name)){
                $insert_results['prod_orig_img_updated_ok'] = true;
            } else {
                $insert_results['msg'] = "Unable to upload orig product image";
            }
            */
            //
            if (is_file($img_section->file)){
                unlink($img_section->file);
            }
        } else {
            $update_results['err_msg'] = "No image provided, not update done";
        }



        // Datos necesarios para que se actualicen en el cliente
        $update_results['updateData'] = Customers::getAuthData($customer_id);

        //
        return $response->withJson($update_results, 200);
    }











    //
    public function PostEditCurrentPassword($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $account_id = $ses_data['account_id'];
        $customer_id = $ses_data['id'];


        //
        $results = array();

        //
        $v = new ValidatorHelper();

        //
        $new_password = Helper::safeVar($request->getParsedBody(), 'new_password');
        $current_password = Helper::safeVar($request->getParsedBody(), 'password');


        //
        if (!$current_password) {
            $results['error'] = "Proporciona tu password actual"; return $response->withJson($results, 200);
        }
        //
        if (strlen($new_password) < 8 || strlen($new_password) > 16) {
            //$results['error'] = "Password debe de contener de 8 a 16 caracteres"; return $response->withJson($results, 200);
        }
        if (!preg_match("/\d/", $new_password)) {
            $results['error'] = "Password debe de contener al menos 1 digito"; return $response->withJson($results, 200);
        }
        if (!preg_match("/[A-Z]/", $new_password)) {
            //$results['error'] = "Password debe de contener al menos 1 mayuscula"; return $response->withJson($results, 200);
        }
        if (!preg_match("/[a-z]/", $new_password)) {
            //$results['error'] = "Password debe de contener al menos 1 minuscula"; return $response->withJson($results, 200);
        }
        if (!preg_match("/\W/", $new_password)) {
            //$results['error'] = "Password debe de contener al menos un caracter especial"; return $response->withJson($results, 200);
        }
        if (preg_match("/\s/", $new_password)) {
            $results['error'] = "Password no debe de contener espacios en blanco"; return $response->withJson($results, 200);
        }
        //
        //$new_password_hash = EncryptHelper::hash($new_password);




        //
        $company_results = Customers::GetRecordByIdAndPassword($customer_id, $current_password);
        //var_dump($company_results); exit;
        if ( isset($company_results['error']) && $company_results['error'] ){
            $results['error'] = $company_results['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($company_results['id']) && $company_results['id']) ){
            $results['error'] = "Invalid password";
            return $response->withJson($results, 200);
        }



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update customers
                       Set
                        
                        password = ?
                        
                      Where account_id = ?
                      And is_customer = 1
                      And id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $new_password,
                $account_id,
                $customer_id
            ],
            "parse" => function($updated_rows, &$query_results) use($customer_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$customer_id;
            }
        ]);
        //var_dump($update_results); exit;



        //
        return $response->withJson($update_results, 200);
    }












    //
    public function PaginateForCompany($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $customer_id = $ses_data['id'];
        //echo "$customer_id"; exit;




        //
        $company_info = Customers::GetRecordById($customer_id, false);
        //dd($company_info); exit;
        if ( $company_info && isset($company_info['error']) && $company_info['error'] > 0 ){
            $results['error'] = $company_info['error'];
            return $response->withJson($results, 200);
        }
        //
        $lat = $company_info['lat'];
        $lng = $company_info['lng'];
        //echo "$lat $lng"; exit;



        //
        $data_results = null;



        // 1 km = 1000 m
        // 100 km = 100,000
        $distance_meters = 200000;

        //
        $nearby_items = Query::Multiple("
                    select 
                        count(*) cant,
                        t.city_code, t.state_code, t.country_code 
                        from workers t
                            
                            Where geography::Point(?, ?, 4326).STDistance(geography::Point(t.lat, t.lng, 4326)) <= ?
                    
                            group by t.city_code, t.state_code, t.country_code
                                ", [
            $lat,
            $lng,
            $distance_meters
        ]);


        //
        $loc_info = Geolocation::getGeoCodeLocation($lat, $lng);
        //dd($loc_info); exit;
        //
        if ( isset($loc_info['city']) && isset($loc_info['state']) && isset($loc_info['country_code']) ){

            //
            $city = $loc_info['city'];
            $state = $loc_info['state'];
            $country_code = $loc_info['country_code'];

            // DEBUG CITY, STATE & COUNTRY
            //echo "{$city} {$state} {$country_code}"; exit;

            //
            $data_results = Query::Multiple("
                    SELECT 
                        t.* 
                            FROM v_customers t
                                Where ( city_code = ? And state_code = ? And country_code = ? )
                                ", [
                $city,
                $state,
                $country_code
            ]);
        }




        //
        return $response->withJson(array(
            "nearby_locations" => $nearby_items,
            "loc" => $loc_info,
            "data" => $data_results
        ), 200);
    }










    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];

        //
        $customer_id = $args['id'];

        //
        $results = Customers::GetRecordById($account_id, $customer_id);


        //
        return $response->withJson($results, 200);
    }









    //
    public function GetCurrentUser($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        /*
         * GET CURRENT RECORD BY SESSION USER ID
         * */
        $account_id = $ses_data['account_id'];
        $customer_id = $ses_data['id'];


        //
        $results = Customers::GetRecordById($account_id, $customer_id);
        //var_dump($results); exit;

        //
        if ($results && isset($results['img_ext'])){
            //
            if ( $profile_img = Customers::getCustomerProfilePic($results['id'], $results['img_ext'])){
                $results['profile_img'] = $profile_img;
                unset($results['img_ext']);
            }
        }


        //
        return $response->withJson($results, 200);
    }














    //
    public function PostUpdateCustomerImage($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $customer_id = $ses_data['id'];


        //
        $results = array();


        //
        $title = Helper::safeVar($request->getParsedBody(), 'title');
        $file = Helper::safeVar($request->getParsedBody(), 'file');
        //echo "$title $file"; exit;
        //
        list($type, $data) = explode(';', $file);
        list(, $data) = explode(',', $data);
        $file_data = base64_decode($data);
        //echo $file_data; exit;



        // Get file mime type
        $finfo = finfo_open();
        $file_mime_type = finfo_buffer($finfo, $file_data, FILEINFO_MIME_TYPE);





        //
        $file_type_ext = null;
        //
        if($file_mime_type == 'image/jpeg' || $file_mime_type == 'image/jpg'){
            $file_type_ext = 'jpeg';
        }
        else if($file_mime_type == 'image/jpg'){
            $file_type_ext = 'jpg';
        }
        else if($file_mime_type == 'image/png'){
            $file_type_ext = 'png';
        }
        else if($file_mime_type == 'image/gif') {
            $file_type_ext = 'gif';
        }

        //
        $file_name = uniqid() . '.' . $file_type_ext;
        $tmp_file_path = PATH_PUBLIC.DS."tmp";

        //
        $main_file = $tmp_file_path.DS.$file_name;

        // Validate type of file
        if( !in_array($file_type_ext, ['jpeg', 'jpg', 'png', 'gif']) ){
            //
            $results['error'] = "Solo se permiten archivos Jpeg, Png o Gif";
            return $response->withJson($results, 200);
        }




        //
        $customer_profile_path = Customers::getCustomerSectionPath($customer_id, "profile");
        $customer_profile_url = FULL_DOMAIN."/files/customers/".$customer_id."/profile";
        //echo " $customer_profile_path --- $customer_profile_url "; exit;



        //
        if ( @file_put_contents($main_file, $file_data) ){


            //
            $update_results = Customers::UpdateImgExt($file_type_ext, $account_id, $customer_id);
            //var_dump($update_results); exit;
            if ( isset($update_results['error']) && $update_results['error'] ){
                $results['error'] = $update_results['error'];
                return $response->withJson($results, 200);
            }


            //
            $new_img_name = "me.".$file_type_ext;
            $orig_img_name = "me-orig.".$file_type_ext;


            // debug image output
            //echo $customer_profile_path.DS.$new_img_name; exit;


            //
            if ( ImagesHandler::resizeImage(
                $main_file,
                250,
                250,
                $customer_profile_path.DS.$new_img_name
            )){
                //
                unlink($main_file);
                //
                $results['id'] = $customer_id;
                //
                return $response->withJson($results, 200);
            } else {
                //
                unlink($main_file);
                //
                $results['error'] = "No se pudo crear el archivo";
                return $response->withJson($results, 200);
            }
        }




        $results['error'] = "No se pudo crear el archivo";
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
        $customer_id = $args['id'];
        //echo " $customer_id $email_type "; exit;



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
        $customer_info = $customer_info = Query::Single("select * from v_customers where id = ?", [$customer_id]);
        $customer_email = $customer_info['email'];
        $customer_name = $customer_info['name'];

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
        $send_email_results = Helper::SendEmail($account_id, $app_id, $maqueta_id, $customer_name, $customer_email, $send_to_copies, function($maqueta_email_msg) use($customer_info){
            return Customers::ParseCustomerMessages($customer_info, $maqueta_email_msg);
        });




        return $response->withJson($send_email_results, 200);
    }






    //
    public function PostUpdateComisionesRecord($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;


        //
        $comision_tipo = $v->safeVar($body_data, 'comision_tipo');
        $comision_valor = $v->safeVar($body_data, 'comision_valor');

        //
        if ( !($comision_tipo === "p" || $comision_tipo === "m") ){
            $results['error'] = "Proprociona el tipo de comision"; return $response->withJson($results, 200);
        }
        if ( !is_numeric($comision_valor) ){
            $results['error'] = "Proprociona el valor de la comision"; return $response->withJson($results, 200);
        }

        //
        $record_id = $args['id'];



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    contacts
                  
                  Set
                    comision_tipo = ?,
                    comision_valor = ?
                    
                  Where account_id = ?
                  And is_customer = 1
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $comision_tipo,
                $comision_valor,
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
    public function PostDeleteCustomerImage($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];



        //
        $results = array();

        //
        $v = new ValidatorHelper();




        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);


        //
        $customer_id = $ses_data['id'];



        //
        $worker_info = Customers::GetRecordById($account_id, $customer_id);
        //var_dump($worker_info); exit;
        if ( isset($worker_info['error']) && $worker_info['error'] ){
            $results['error'] = $worker_info['error'];
            return $response->withJson($results, 200);
        }





        //
        $img_ext = $worker_info['img_ext'];
        //
        if (!$img_ext){
            $results['error'] = "No existe extension para eliminacion";
            return $response->withJson($results, 200);
        }



        //
        $img_cust_path = PATH_PUBLIC.DS."workers".DS.$customer_id.DS."profile.".$img_ext;
        //echo $img_cust_path; exit;
        //
        if ( !is_file($img_cust_path) ){
            $results['error'] = "No existe imagen para eliminacion";
            return $response->withJson($results, 200);
        }


        //
        unlink($img_cust_path);


        //
        $update_results = Customers::UpdateImgExt(null, $account_id, $customer_id);
        //var_dump($update_results); exit;
        if ( isset($update_results['error']) && $update_results['error'] ){
            $results['error'] = $update_results['error'];
            return $response->withJson($results, 200);
        }


        //
        return $response->withJson($update_results, 200);
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
        $password = Helper::safeVar($body, 'password');
        $birth_date = Helper::safeVar($body, 'birth_date');
        $active = Helper::safeVar($body, 'active') ? 1 : 0;
        
        


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
        if (!$birth_date) {
            $results['error'] = "La fecha de nacimiento es obligatoria"; 
            return $response->withJson($results, 200);
        }

        // Validar formato de fecha
        //echo $birth_date; exit;
        $birth_date_obj = \DateTime::createFromFormat('!Y-m-d', $birth_date);
        if (!$birth_date_obj) {
            $results['error'] = "Formato de fecha de nacimiento inválido"; 
            return $response->withJson($results, 200);
        }
        //dd($birth_date_obj);

        //
        $today = new \DateTime();
        //
        $diff = $today->diff($birth_date_obj);
        $edad_years = $diff->y;
        $age_months = $diff->m;
        $age_days = $diff->d;
        //
        //echo "Edad: " . $edad_years . " años"; exit;






        //
        $customer_info = Query::Single("select * from v_customers where app_id = ? and 
        (
            (phone_cc = ? And phone_number = ?) or (email = ?)
        )
            ", [$app_id, $phone_cc, $phone_number, $email]);
        //dd($customer_info); exit;
        if ( $customer_info && isset($customer_info['id']) ){
            $results['error'] = "ya existe cliente con mismo correo o telefono"; return $response->withJson($results, 200);
        }
        //echo "test"; exit;




        //
        $customer_info = Query::Single("select * from contacts where account_id = ? and email = ?", [$account_id, $email]);
        //dd($customer_info);
        if ($customer_info && isset($customer_info['id']) && $customer_info['id']){
            //            
            $update_contact = Contacts::updateContactType("customer", $account_id, $customer_info['id'], $active);
            //dd($update_contact);
            return $response->withJson($update_contact, 200);
        }



         


        // IX_contacts_email_unique
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => true,
            "stmt" => "
                   Insert Into contacts
                  ( name, email, 
                    phone_country_id, phone_cc, phone_number, 
                    birth_date, edad_years,
                    notes, is_customer_active, account_id, app_id, is_customer, datetime_created )
                  Values
                  ( ?, ?,
                    ?, ?, ?, 
                    ?, ?,
                    ?, ?, ?, ?, 1, GETDATE() )
                  ;SELECT SCOPE_IDENTITY()   
                ",
            "params" => [
                $name,
                $email,
                $phone_country_id,
                $phone_cc,
                $phone_number,
                $birth_date,
                $edad_years,
                $notes,
                $active,
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
        $customer_id = $args['id'];



        //
        $name = $v->safeVar($body_data, 'name');
        $email  = $v->safeVar($body_data, 'email');
        $birth_date  = $v->safeVar($body_data, 'birth_date');

        //
        $phone_country_id = $v->safeVar($body_data, 'phone_country_id');
        $phone_number = $v->safeVar($body_data, 'phone_number');
        //
        $notes = $v->safeVar($body_data, 'notes');
        $active = $v->safeVar($body_data, 'active') ? 1 : 0;


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
        $customer_info = Query::Single("select * from v_customers where app_id = ? And id = ?", [$app_id, $customer_id]);
        //dd($customer_info);
        if ( !($customer_info && isset($customer_info['id'])) ){
            $results['error'] = "No se encontro el cliente que intentas editar"; return $response->withJson($results, 200);
        }
        //
        $customer_info = Query::Single("select * from v_customers where app_id = ? and id != ? And
        (
            (phone_cc = ? And phone_number = ?) or (email = ?)
        )
            ", [$app_id, $customer_id, $phone_cc, $phone_number, $email]);
        //dd($customer_info); exit;
        if ( $customer_info && isset($customer_info['id']) ){
            $results['error'] = "ya existe cliente con mismo correo o telefono"; return $response->withJson($results, 200);
        }
        //echo "test"; exit;




        //
        if (!$birth_date) {
            $results['error'] = "La fecha de nacimiento es obligatoria"; 
            return $response->withJson($results, 200);
        }

        // Validar formato de fecha
        //echo $birth_date; exit;
        $birth_date_obj = \DateTime::createFromFormat('!Y-m-d', $birth_date);
        if (!$birth_date_obj) {
            $results['error'] = "Formato de fecha de nacimiento inválido"; 
            return $response->withJson($results, 200);
        }
        //dd($birth_date_obj);

        //
        $today = new \DateTime();
        //
        $diff = $today->diff($birth_date_obj);
        $edad_years = $diff->y;
        $age_months = $diff->m;
        $age_days = $diff->d;
        //
        //echo "Edad: " . $edad_years . " años"; exit;


         


        
        $params = [
            $name,
            $email,
            $phone_country_id,
            $phone_cc,
            $phone_number,
            $birth_date,
            $edad_years,
            $notes,
            $active,
            $account_id,
            $customer_id
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
                    birth_date = ?,
                    edad_years = ?,
                    notes = ?,
                    is_customer_active = ?
                    
                  Where account_id = ?
                  And is_customer = 1
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => $params,
            "parse" => function($updated_rows, &$query_results) use($customer_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$customer_id;
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
        $customer_id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$customer_id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }


        $results = Contacts::removeContactType("customer", $account_id, $customer_id);

        /*
        $results = Query::DoTask([
            "task" => "delete",
            "debug" => true,
            "stmt" => "delete from contacts where account_id = ? And is_customer = 1 And id = ?; SELECT @@ROWCOUNT",
            "params" => [
                $account_id,
                $customer_id
            ],
            "parse" => function($updated_rows, &$query_results) use($customer_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$customer_id;
            }
        ]);
        //dd($results);
        */
        


        //
        return $response->withJson($results, 200);
    }



}
