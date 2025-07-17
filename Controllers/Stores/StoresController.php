<?php
namespace Controllers\Stores;

//
use App\Auth\ActivationCodes;
use App\Locations\CatPaises;
use App\Paths;

//
use App\Customers\Customers;
use Controllers\BaseController;
use App\App;
use App\Stores\Stores;;


use Helpers\Geolocation;
use Helpers\Helper;
use Helpers\EncryptHelper;
use Helpers\ImagesHandler;
use Helpers\PHPMicroParser;
use Helpers\Query;
use Helpers\ValidatorHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;


//
class StoresController extends BaseController
{





    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/stores/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "user_session_data" => $request->getAttribute("ses_data")
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
                        ( t.name like '%$search_value%' ) Or 
                        ( t.last_name like '%$search_value%' ) Or
                        ( t.username like '%$search_value%' ) Or
                        ( t.email like '%$search_value%' ) Or
                        ( t.phone_number like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "viewStores";
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
                    return "Select COUNT(*) total From {$table_name} t Where 1=1 {$search_clause}";
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
                    if ( isset($row['img_ext']) && $row['img_ext']  && ( $biz_logo = Stores::getStoreLogo($row['id'], $row['img_ext']) ) ){
                        $row['biz_logo'] = $biz_logo;
                        unset($row['img_ext']);
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
        //dd($ses_data); exit;

        //
        $search_text = $request->getQueryParam("q");

        //
        $results = Stores::Search($search_text);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }







    //
    public function PaginateForCustomer($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $company_id = $ses_data['id'];
        //echo "$company_id"; exit;




        //
        $customer_info = Customers::GetRecordById($company_id, false);
        //dd($customer_info); exit;
        if ( $customer_info && isset($customer_info['error']) && $customer_info['error'] > 0 ){
            $results['error'] = $customer_info['error'];
            return $response->withJson($results, 200);
        }
        //
        $lat = $customer_info['lat'];
        $lng = $customer_info['lng'];
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
                        from companies t
                            
                            Where 1 = 1
                            And geography::Point(?, ?, 4326).STDistance(geography::Point(t.lat, t.lng, 4326)) <= ?
                    
                            group by t.city_code, t.state_code, t.country_code
                                ", [
            $lat,
            $lng,
            $distance_meters
        ]);
        //dd($nearby_items); exit;


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
                            FROM viewStores t
                                Where 1=1
                                And ( city_code = ? And state_code = ? And country_code = ? )
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
    public function GetRecordById($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;


        //
        $results = Stores::GetRecordById($args['id']);
        //dd($results); exit;


        //
        return $response->withJson($results, 200);
    }












    //
    public function GetCompanyState($request, $response, $args) {
        //
        $headers = apache_request_headers();
        //
        if ( isset($headers['Authorization']) && strlen($headers['Authorization']) >= 30) {

            //
            $api_key = base64_decode($headers['Authorization']);
            //echo $api_key; exit;

            //
            $res = Query::Single("
                SELECT
                        cust.id,
                        t.id as token_id
                          --
                          FROM companies_auth_tokens t
                          
                            Left Join companies cust On cust.id = t.company_id
                            
                            --
                            Where t.token = ?
                            
                            -- donde el companies y el token esten activos
                            And t.active = 1
        ", [
                $api_key
            ]);
            //
            //var_dump($res); exit;


            //
            $cust_info = Stores::GetRecordById($res['id']);
            return $response->withJson($cust_info, 200);
        }
        //
        return $response->withJson([], 200);
    }






    //
    public function GetCompanyReviews($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $search_text = $request->getQueryParam("search");

        //
        //$results = Pedidos::GetAllForCustomerReviews($args['company_id'], $args['exclude_pedido_id']);
        //var_dump($results); exit;


        //
        return $response->withJson([], 200);
    }







    //
    public function GetCurrentUser($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $store_id = $ses_data['id'];

        //
        $results = Stores::GetRecordById($store_id, 5);
        //dd($results); exit;

        //
        $results['schedule'] = Query::Single("Select * from stores_schedule where store_id = ?", [$store_id]);


        //
        if ($results && isset($results['id']) ){
            //
            if ( isset($results['img_ext']) && $results['img_ext']  && ( $biz_logo = Stores::getStoreLogo($store_id, $results['img_ext']) ) ){
                $results['biz_logo'] = $biz_logo;
                unset($results['img_ext']);
            }
            //
            $results['page_url'] = Stores::getStoreUrl($results['id'], $results['company_name'], $results['store_title']);
        }


        //
        return $response->withJson($results, 200);
    }






    //
    public function GetPublicStoreById($request, $response, $args) {


        //
        $store_id = $args['store_id'];


        //
        $results = Stores::GetRecordByIdOnly($store_id);
        //dd($results); exit;

        //
        return $response->withJson($results, 200);
    }




    //
    public function GetStoreByPlaceId($request, $response, $args) {


        //
        $place_id = $args['place_id'];
        //echo $place_id; exit;



        //
        $results = Stores::FindStoreByPlaceId($place_id);
        //dd($results); exit;

        //
        return $response->withJson($results, 200);
    }





    //
    public function PostUpdatePassword($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $store_id = $ses_data['id'];


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
        $company_results = Stores::GetRecordByIdAndPassword($store_id, $current_password);
        //var_dump($company_results); exit;
        if ( isset($company_results['error']) && $company_results['error'] ){
            $results['error'] = $company_results['error'];
            return $response->withJson($results, 200);
        }
        if ( !(isset($company_results['id']) && $company_results['id']) ){
            $results['error'] = "invalid password";
            return $response->withJson($results, 200);
        }



        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update stores
                       Set
                        
                        password = ?
                        
                      Where id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $new_password,
                $store_id
            ],
            "parse" => function($updated_rows, &$query_results) use($store_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$store_id;
            }
        ]);
        //var_dump($update_results); exit;



        //
        return $response->withJson($update_results, 200);
    }












    //
    public function postUpdateContactInfo($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $sale_type_id = $ses_data['sale_type_id'];
        $store_id = $ses_data['id'];


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;


        //
        $name = Helper::getFirstTextOnly($v->safeVar($body_data, 'name'));
        $last_name = Helper::getFirstTextOnly($v->safeVar($body_data, 'last_name'));
        $password = $v->safeVar($body_data, 'password');
        $allow_receive_sms = ($v->safeVar($body_data, 'allow_receive_sms') === "true") ? 1 : 0;
        //echo $allow_receive_sms; exit;


        //
        //echo " $name $last_name "; exit;



        //
        if ( !$v->validateString([2, 128], $name) ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }
        if ( !$v->validateString([2, 128], $last_name) ){
            $results['error'] = "Provide last name"; return $response->withJson($results, 200);
        }
        //
        if (!$password){
            $results['error'] = "Provide password"; return $response->withJson($results, 200);
        }



        //
        $info_results = Stores::GetRecordByIdAndPassword($store_id, $password);
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
        $store_dir = Paths::$path_stores.DS.$store_id;
        //
        if (!is_dir($store_dir)){
            mkdir($store_dir);
        }
        //echo $store_dir; exit;




        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update stores 
                       Set
                           
						name = ?,
						last_name = ?,
						allow_receive_sms = ?
						   
                      Where id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $name,
                $last_name,
                $allow_receive_sms,
                //
                $store_id
            ],
            "parse" => function($updated_rows, &$query_results) use($store_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$store_id;
            }
        ]);
        //var_dump($update_results); exit;




        //
        return $response->withJson($update_results, 200);
    }










    //
    public function postUpdateAddress($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $sale_type_id = $ses_data['sale_type_id'];
        $store_id = $ses_data['id'];


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;


        //
        $store_name = $v->safeVar($body_data, 'store_name');
        $place_id = $v->safeVar($body_data, 'place_id');
        $address = $v->safeVar($body_data, 'address');
        $lat = $v->safeVar($body_data, 'lat');
        $lng = $v->safeVar($body_data, 'lng');
        $state_code = $v->safeVar($body_data, 'state_code');
        $city_code = $v->safeVar($body_data, 'city_code');

        //
        $preq = $v->safeVar($body_data, 'preq');
        $password_validation_required = ( $preq && ($preq === "true" || $preq === "1") ) ? true : false;
        $password = $v->safeVar($body_data, 'password');



        //
        if ( !$v->validateString([2, 256], $address) ){
            $results['error'] = "Provide adress"; return $response->withJson($results, 200);
        }
        if ( !Helper::valid_lat($lat) ){
            $results['error'] = "Provide latitude"; return $response->withJson($results, 200);
        }
        if ( !Helper::valid_lng($lng) ){
            $results['error'] = "Provide longitude"; return $response->withJson($results, 200);
        }

        //
        $state_code = ($state_code) ? $state_code : null;
        $city_code = ($city_code) ? $city_code : null;




        //
        if ($password_validation_required){
            //
            if (!$password){
                $results['error'] = "Provide password"; return $response->withJson($results, 200);
            }
            //
            $info_results = Stores::GetRecordByIdAndPassword($store_id, $password);
            //dd($info_results); exit;
            if ( isset($info_results['error']) && $info_results['error'] ){
                $results['error'] = $info_results['error'];
                return $response->withJson($results, 200);
            }
            if ( !(isset($info_results['id']) && $info_results['id']) ){
                $results['error'] = "Invalid password";
                return $response->withJson($results, 200);
            }
        }




        if ($store_name){
            //
            $str_additional_fields = "store_title = ?,";
            //
            $arr_params = [
                $store_name,
                $place_id,
                $address,
                $lat,
                $lng,
                $state_code,
                $city_code,
                //
                $store_id
            ];

        } else {
            //
            $str_additional_fields = "";
            //
            $arr_params = [
                $place_id,
                $address,
                $lat,
                $lng,
                $state_code,
                $city_code,
                //
                $store_id
            ];

        }




        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update stores 
                       Set
                        
                        {$str_additional_fields}
                        place_id = ?,
                        address = ?, 
                        lat = ?,
                        lng = ?,
                        state_code = ?,
                        city_code = ?
                           
                      Where id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => $arr_params,
            "parse" => function($updated_rows, &$query_results) use($store_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$store_id;
            }
        ]);
        //var_dump($update_results); exit;




        // Datos necesarios para que se actualicen en el cliente
        $update_results['updateData'] = Stores::getAuthData($store_id);



        //
        return $response->withJson($update_results, 200);
    }












    //
    public function postUpdateAboutInfo($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;

        //
        $company_id = $ses_data['company_id'];
        $store_id = $ses_data['id'];


        //
        $results = array();

        //
        $v = new ValidatorHelper();




        //
        $uploadedFiles = $request->getUploadedFiles();
        //dd($uploadedFiles); exit;


        //
        $company_type_id  = Helper::safeVar($request->getParsedBody(), 'company_type_id');
        $company_name  = Helper::safeVar($request->getParsedBody(), 'company_name');
        $about_text  = Helper::safeVar($request->getParsedBody(), 'about_text');
        $terms_and_conditions  = Helper::safeVar($request->getParsedBody(), 'terms_and_conditions');
        $store_logo_img   = Helper::safeVar($request->getParsedBody(), 'store_logo_img');


        //
        $preq  = Helper::safeVar($request->getParsedBody(), 'preq');
        $password_validation_required = ( $preq && ($preq === "true" || $preq === "1") ) ? true : false;
        $password  = Helper::safeVar($request->getParsedBody(), 'password');






        //
        if ( !is_numeric($company_type_id) ){
            $results['error'] = "proporciona el giro";
            return $response->withJson($results, 200);
        }
        if ( !$company_name ){
            $results['error'] = "proporciona el nombre del negocio";
            return $response->withJson($results, 200);
        }
        if ( !$about_text ){
            $results['error'] = "proporciona acerca del negocio";
            return $response->withJson($results, 200);
        }


        //
        if ($password_validation_required){
            //
            if (!$password){
                $results['error'] = "Provide password"; return $response->withJson($results, 200);
            }
            //
            $info_results = Stores::GetRecordByIdAndPassword($store_id, $password);
            //dd($info_results); exit;
            if ( isset($info_results['error']) && $info_results['error'] ){
                $results['error'] = $info_results['error'];
                return $response->withJson($results, 200);
            }
            if ( !(isset($info_results['id']) && $info_results['id']) ){
                $results['error'] = "Invalid password";
                return $response->withJson($results, 200);
            }
        }




        //
        $store_profile_path = Stores::getStoreSectionPath($store_id, "profile");
        $store_profile_url = FULL_DOMAIN."/files/stores/".$store_id."/profile";
        //echo " $store_profile_path --- $store_profile_url "; exit;



        //
        $store_info = Query::Single("select img_ext from stores where id = ?", [$store_id]);
        //dd($store_info); exit;




        /*
         * Flag para hacer bypass si ya tiene logo
         * */
        $store_already_has_logo = false;
        if ( $store_info && isset($store_info['img_ext']) && ($biz_logo = Stores::getStoreLogo($store_id, $store_info['img_ext'])) ){
            $store_already_has_logo = true;
        }



        //
        $img_section = null;
        $file_type_ext = null;
        //
        if ( isset($uploadedFiles['store_logo_img']) && $uploadedFiles['store_logo_img'] && $uploadedFiles['store_logo_img']->getError() === UPLOAD_ERR_OK ) {
            //
            $img_section = $uploadedFiles['store_logo_img'];
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

            // Si no tiene logo aun validamos
            if (!$store_already_has_logo){
                $results['error'] = "Se require el logotipo del negocio";
                return $response->withJson($results, 200);
            }

        }
        //dd($img_section); exit;
        //echo $file_type_ext; exit;



        //
        $results['company_update'] = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update companies 
                       Set
                       
						cat_company_type_id = ?,
						company_name = ?
						
                      Where id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $company_type_id,
                $company_name,
                //
                $company_id
            ],
            "parse" => function($updated_rows, &$query_results){
                $query_results['affected_rows'] = $updated_rows;
            }
        ]);
        //var_dump($update_results); exit;
        if ( isset($update_results['error']) && $update_results['error'] ){
            $results['error'] = $update_results["error"];
            return $response->withJson($results, 200);
        }



        //
        $results['store_update'] = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update stores 
                       Set
                       
						about = ?,
						terms_conditions = ?
						
                      Where company_id = ?
                      And id = ?
                     ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $about_text,
                $terms_and_conditions,
                //
                $company_id,
                $store_id
            ],
            "parse" => function($updated_rows, &$query_results){
                $query_results['affected_rows'] = $updated_rows;
            }
        ]);
        //var_dump($update_results); exit;
        if ( isset($update_results['error']) && $update_results['error'] ){
            $results['error'] = $update_results["error"];
            return $response->withJson($results, 200);
        }





        //
        // Si proporciono img_section entonces lo actualizamos
        //
        if ( $img_section && $file_type_ext ){
            // product && product original image
            $new_img_name = "me.{$file_type_ext}";
            //echo $new_img_name; exit;


            // elimina archivos de imagenes previos
            if ( is_file($store_profile_path.DS."me.png") ){unlink($store_profile_path.DS."me.png");}
            if ( is_file($store_profile_path.DS."me.jpg") ){unlink($store_profile_path.DS."me.jpg");}
            if ( is_file($store_profile_path.DS."me.jpeg") ){unlink($store_profile_path.DS."me.jpeg");}
            if ( is_file($store_profile_path.DS."me.gif") ){unlink($store_profile_path.DS."me.gif");}



            //
            if ( ImagesHandler::resizeImage(
                $img_section->file,
                250,
                250,
                $store_profile_path.DS.$new_img_name
            )){

                //
                $results['store_img_ext'] = Query::DoTask([
                    "task" => "update",
                    "stmt" => "
                   Update stores 
                       Set
                       
						img_ext = ?
						
                      Where company_id = ?
                      And id = ?
                     ;SELECT @@ROWCOUNT
                ",
                    "params" => [
                        //
                        $file_type_ext,
                        //
                        $company_id,
                        $store_id
                    ],
                    "parse" => function($updated_rows, &$query_results){
                        $query_results['affected_rows'] = $updated_rows;
                    }
                ]);
                //
                $results['logo_img_updated_ok'] = true;

            } else {
                $results['err_msg'] = "Unable to upload logo image";
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
            $results['msg'] = "no logo image provided";
        }




        //
        $results['id'] = $store_id;
        $results['updateData'] = Stores::GetRecordById($store_id);

        //
        return $response->withJson($results, 200);
    }












    //
    public function PostUpdateStoreImage($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $store_id = $ses_data['id'];


        //
        $results = array();


        //
        $file = Helper::safeVar($request->getParsedBody(), 'file');
        //
        list($type, $data) = explode(';', $file);
        list(, $data) = explode(',', $data);
        $file_data = base64_decode($data);



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
        if ( @file_put_contents($main_file, $file_data) ){



            //
            $update_results = Stores::UpdateImgExt($file_type_ext, $store_id);
            //var_dump($update_results); exit;
            if ( isset($update_results['error']) && $update_results['error'] ){
                $results['error'] = $update_results['error'];
                return $response->withJson($results, 200);
            }


            //
            $profile_path = Stores::getStoreSectionPath($store_id, "profile");

            //
            $new_img_name = "me.".$file_type_ext;
            $orig_img_name = "me-orig.".$file_type_ext;


            //echo $img_new_nombre; exit;
            //
            if ( ImagesHandler::resizeImage(
                $main_file,
                250,
                250,
                $profile_path.DS.$new_img_name
            )){
                //
                unlink($main_file);
                //
                $results['id'] = $store_id;
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







    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;


        //
        $phone_country_id = $v->safeVar($body_data, 'phone_country_id');
        $phone_number = $v->safeVar($body_data, 'phone_number');
        $email = $v->safeVar($body_data, 'email');
        $active = $v->safeVar($body_data, 'active') ? 1 : 0;



        //
        if ( !is_numeric($phone_country_id) ){
            $results['error'] = "Provide Country Id"; return $response->withJson($results, 200);
        }
        //
        $countries_results = CatPaises::GetById($phone_country_id);
        //var_dump($countries_results); exit;
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
        $phone_number_1 = $phone_cc . $phone_number;
        // DEBUG PHONES
        //echo $phone_number_1; exit;
        //
        $email = (filter_var($email, FILTER_VALIDATE_EMAIL) ) ? $email : null;




        //
        $store_id = $args['id'];




        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update 
                    stores
                  
                  Set
                    email = ?,
                    phone_country_id = ?,
                    phone_cc = ?,
                    phone_number = ?,
                    active = ?
                    
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $email,
                $phone_country_id,
                $phone_cc,
                $phone_number,
                $active,
                //
                $store_id
            ],
            "parse" => function($updated_rows, &$query_results) use($store_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$store_id;
            }
        ]);
        //var_dump($update_results); exit;


        //
        return $response->withJson($update_results, 200);
    }





















    //
    public function PostDeleteStoreImage($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $store_id = $ses_data['id'];



        //
        $results = array();

        //
        $v = new ValidatorHelper();




        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;





        //
        $company_info = Stores::GetRecordById($store_id);
        //var_dump($company_info); exit;
        if ( isset($company_info['error']) && $company_info['error'] ){
            $results['error'] = $company_info['error'];
            return $response->withJson($results, 200);
        }





        //
        $img_ext = $company_info['img_ext'];
        //
        if (!$img_ext){
            $results['error'] = "No existe extension GetRecordpara eliminacion";
            return $response->withJson($results, 200);
        }



        //
        $img_cust_path = PATH_PUBLIC.DS."companies".DS.$store_id.DS."profile.".$img_ext;
        //echo $img_cust_path; exit;
        //
        if ( !is_file($img_cust_path) ){
            $results['error'] = "No existe imagen para eliminacion";
            return $response->withJson($results, 200);
        }


        //
        unlink($img_cust_path);


        //
        $update_results = Stores::UpdateImgExt(null, $store_id);
        //var_dump($update_results); exit;
        if ( isset($update_results['error']) && $update_results['error'] ){
            $results['error'] = $update_results['error'];
            return $response->withJson($results, 200);
        }


        //
        return $response->withJson($update_results, 200);
    }










    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        //
        $user_email = $ses_data['email'];


        //
        $results = array();


        //
        $id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }





        if (  !($user_email === "ivanjzr@gmail.com") ){
            $results['error'] = "Solo un admin puedee eliminar los registros de negocios";
            return $response->withJson($results, 200);
        }



        //
        $results = Query::DoTask([
            "task" => "delete",
            "stmt" => "Delete FROM stores Where id = ?;SELECT @@ROWCOUNT",
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
