<?php
namespace Controllers\Api;
//
use App\Config\ConfigStripe\ConfigStripe;
use App\Config\ConfigSquare\ConfigSquare;
use App\Config\ConfigAuthorizeNet\ConfigAuthorizeNet;
use App\Auth\Auth;
use App\Utils\Utils;
use App\Auth\AuthTokens;
use App\Companies\Companies;
use App\Customers\Customers;
use App\Auth\ActivationCodes;
use App\Locations\CatPaises;
use App\Notifications\Notifications;
use App\Paths;
use App\Ventas\Ventas;
use Controllers\BaseController;
//
use App\Stores\Stores;

use Helpers\Helper;
use Helpers\Query;
use Helpers\ValidatorHelper;

use ipinfo\ipinfo\IPinfo;
use Google;
use Square\Models\Customer;


//
class AuthController extends BaseController
{









    //
    public function ViewLogin($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/login.phtml');
    }




    
    //
    public function PostRegisterCustomer($request, $response, $args) {
        //
        $app_data = $request->getAttribute("app");
        //dd($app_data); exit;
        //
        $app_id = (int)$app_data['id'];
        $account_id = (int)$app_data['account_id'];
        //echo " $app_id $account_id "; exit;


        //
        $v = new ValidatorHelper();



        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;
        //
        $company_name = $v->safeVar($body_data, 'company_name');
        //
        $name = Helper::getFirstTextOnly($v->safeVar($body_data, 'name'));
        //
        $email = $v->safeVar($body_data, 'email');
        //
        $phone_country_id = $v->safeVar($body_data, 'phone_country_id');
        $phone_number = $v->safeVar($body_data, 'phone_number');
        $password = $v->safeVar($body_data, 'password');
        
        //
        $is_biz = $v->safeVar($body_data, 'is_biz');
        $is_biz = ( $is_biz === "true" ) ? 1 : 0;
        //echo $is_biz; exit;


        $request_identifier = $v->safeVar($body_data, 'request_identifier');
       
        


        //
        if ( !$name ){
            $results['error'] = "Provide first name"; return $response->withJson($results, 200);
        }
        //
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "Provide valid email"; return $response->withJson($results, 200);
        }

        //
        if ( !$request_identifier ){
            $results['error'] = "Provide Request Identifier"; return $response->withJson($results, 200);
        }
        //echo "$request_identifier"; exit;


        /*
         * GET PH#1
         * */
        if ( !is_numeric($phone_country_id) ){
            $results['error'] = "Provide Country Id"; return $response->withJson($results, 200);
        }



        



        //
        $countries_results = CatPaises::GetById($phone_country_id);
        //dd($countries_results);
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
        $password = trim($password);
        //
        if (strlen($password) < 6 || strlen($password) > 16) {
            $results['error'] = "password length must be more than 6 digits and less than 16"; return $response->withJson($results, 200);
        }
        if (!preg_match("/\d/", $password)) {
            $results['error'] = "Password must contain numbers"; return $response->withJson($results, 200);
        }
        if (!preg_match("/[a-z]/", $password)) {
            $results['error'] = "Password must contain letters"; return $response->withJson($results, 200);
        }
        if (!preg_match("/[A-Z]/", $password)) {
            //$results['error'] = "Password must contain at least 1 upper case"; return $response->withJson($results, 200);
        }
        if (!preg_match("/\W/", $password)) {
            //$results['error'] = "Password must contain at least 1 special character"; return $response->withJson($results, 200);
        }
        //
        //$password_hash = EncryptHelper::hash($password);
        //echo $password; exit;


        //
        $new_record_id = 0;


        //echo "$name, $email, $phone_country_id, $phone_number, $password, $request_identifier, $app_id"; exit;

        // 
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_Auth_CreateAccountCustomers(?,?,?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "ERR_CUSTOMER_ALREADY_EXISTS" => "Ya existe el cliente, intenta recuerar tu cuenta",
                "IX_contacts_email_unique" => "Ya existe el cliente, intenta recuerar tu cuenta",
            ],
            "debug" => true,
            "params" => function() use(
                $company_name,
                $name,
                $email,                
                //
                $phone_country_id,
                $phone_number,
                $password,
                $request_identifier,
                $account_id,
                $app_id,
                //
                &$new_record_id
            ){
                return [
                    //
                    array($company_name, SQLSRV_PARAM_IN),
                    array($name, SQLSRV_PARAM_IN),
                    array($email, SQLSRV_PARAM_IN),
                    //
                    array($phone_country_id, SQLSRV_PARAM_IN),
                    array($phone_number, SQLSRV_PARAM_IN),
                    array($password, SQLSRV_PARAM_IN),
                    array($request_identifier, SQLSRV_PARAM_IN),
                    array($account_id, SQLSRV_PARAM_IN),
                    array($app_id, SQLSRV_PARAM_IN),
                    //
                    array(&$new_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //echo $new_record_id; exit;
        //dd($sp_res); exit;
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            $results['error'] = $sp_res['error'];
            return $response->withJson($results, 200);
        }

        //
        $results['id'] = $new_record_id;







        /*
         *
         * Creamos el Path del Customer
         *
         * */
        Customers::getCustomerSectionPath($new_record_id, "profile");
        //echo "done Ok!"; exit;



        /*
         *
         * Creamos la imagen de placeholder
         *
         * */
        /*
        //
        $savePath = $customer_profile_path.DS.'me.png';
        //
        $randomR = mt_rand(150, 230);
        $randomG = mt_rand(200, 250);
        $randomB = mt_rand(200, 250);
        //
        Helper::cratePlaceholderImage(100, 100, $savePath, $randomR, $randomG, $randomB);
        */




        /*
         *
         * GET CREATED ACTIVATION TOKEN
         *
         * */
        $get_code_results = ActivationCodes::GetLastActivationCode($new_record_id);
        //dd($get_code_results);
        if ( isset($get_code_results['error']) && $get_code_results['error'] ){
            $results['error'] = $get_code_results["error"];
            return $response->withJson($results, 200);
        }
        //
        $activation_code = $get_code_results['activation_code'];









        /*
         *
         * GET CUSTOMER INFO
         *
         * */
        //
        $customer_info = Customers::GetRecordById($account_id, $new_record_id);
        //dd($customer_info);
        if ( isset($customer_info['error']) && $customer_info['error'] ){
            $results['error'] = $customer_info["error"];
            return $response->withJson($results, 200);
        }
        //
        $customer_info['activation_code'] = $activation_code;




        //
        //$send_sms_results = null;
        /*
        $send_sms_results = Helper::SendSMS($account_id, $app_id, MAQUETA_ID_CUST_REGISTRO, $phone_number_1, function($maqueta_sms_msg) use($customer_info){
            return Customers::ParseCustomerMessages($customer_info, $maqueta_sms_msg);
        });
        */

        //
        $send_email_results = Helper::SendEmail($account_id, $app_id, MAQUETA_ID_CUST_REGISTRO, $name, $email, true, function($maqueta_email_msg) use($customer_info){
            return Customers::ParseCustomerMessages($customer_info, $maqueta_email_msg);
        });


        // 
        return $response->withJson(array(
            "id" => $customer_info['id'],
            "company_name" => $customer_info['company_name'],
            "email" => $customer_info['email'],
            "name" => $customer_info['name'],
            "phone_country_id" => $customer_info['phone_country_id'],
            "phone_cc" => $customer_info['phone_cc'],
            "phone_number" => $customer_info['phone_number'],
            //"send_sms_results" => $send_sms_results,
            "send_email_results" => $send_email_results
        ), 200);
    }







    
    






    //  
    public function ActivateAccount($request, $response, $args) {
         //
         $app_data = $request->getAttribute("app");
         //dd($app_data); exit;
         //
         $app_id = (int)$app_data['id'];
         //echo $app_id; exit;


        //
        $results = array();
        //
        $v = new ValidatorHelper();


        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;



        //
        $record_id = $v->safeVar($body_data, 'id');
        $activation_code = (int)$v->safeVar($body_data, 'activation_code');
        //echo " $record_id $activation_code"; exit;



        //
        if ( !is_numeric($record_id) ){
            $results['error'] = "Provide id";
            return $response->withJson($results, 200);
        }
        //
        if ( !$activation_code){
            $results['error'] = "Provide activation code";
            return $response->withJson($results, 200);
        }





        /*
         *
         * ACTIVATE ACCOUNT
         *
         * */
        //
        $activation_results = Auth::ActivateAccount($record_id, $activation_code);
        //dd($activation_results); exit;
        //
        if ( isset($activation_results['error']) && $activation_results['error'] ){
            $results['error'] = $activation_results['error'];
            return $response->withJson($results, 200);
        }






        /*
         * HACEMOS LOGIN
         * */
        $login_results = Auth::PostDirectLogin($app_id, $record_id);
        //dd($login_results); exit;
        if ( isset($login_results['error']) && $login_results['error'] ){
            $results['error'] = $login_results['error'];
            return $response->withJson($results, 200);
        }
        //
        if ( !(isset($login_results['id']) && $login_results['id']) ){
            $results['error'] = "Invalid phone or user not found";
            return $response->withJson($results, 200);
        }

       
        

        // LOGO PARA STORES
       //
       $profile_pic = Customers::getCustomerProfilePic($login_results['id'], $login_results['img_ext']);
       if ( $profile_pic ){
           $login_results['profile_img'] = $profile_pic;
       }
       //
       $login_results['ut'] = "ST";


        //
        $login_results['create_acct_updated_rows'] = $activation_results['updated_rows'];
        $login_results['token'] = base64_encode($login_results['token']);


        //
        return $response->withJson($login_results, 200);
    }












    //
    public function PostLogout($request, $response, $args){
        //
        //$app_data = $request->getAttribute("app");
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        
        //
        $app_id = (int)$ses_data['app_id'];
        $user_id = (int)$ses_data['id'];
        $token_id = (int)$ses_data['token_id'];
        //echo "$app_id $user_id $token_id"; exit;

        //
        $results = AuthTokens::DisableAuthToken($app_id, $user_id, $token_id);
        //
        return $response->withJson($results, 200);
    }




    //
    public function PostLogin($request, $response, $args) {
       //
        $app_data = $request->getAttribute("app");
        //dd($app_data); exit;
        //
        $app_id = (int)$app_data['id'];
        $account_id = Helper::getAccountByAppId($app_id);
        //echo " $app_id $account_id "; exit;

        //
        $results = array();
        //
        $v = new ValidatorHelper();


        //
        $body_data = $request->getParsedBody();
        //var_dump($body_data); exit;



        //
        $email = $v->safeVar($body_data, 'email');
        $password = $v->safeVar($body_data, 'password');
        //$sale_type_id = (int)$v->safeVar($body_data, 'sale_type_id');
        //echo "$sale_type_id $phone_country_id $phone_number $password "; exit;




        //
        if ( !($password && strlen($password) >= 3) ){
            $results['error'] = "proporciona la clave";
            return $response->withJson($results, 200);
        }
        //
        if ( !($email && filter_var($email, FILTER_VALIDATE_EMAIL)) ){
            $results['error'] = "proporciona el email";
            return $response->withJson($results, 200);
        }

        


        /*
         * HACEMOS LOGIN
         * */
        $login_results = Auth::PostLogin($app_id, $email, $password);
        //dd($login_results); exit;
        if ( isset($login_results['error']) && $login_results['error'] ){
            $results['error'] = $login_results['error'];
            return $response->withJson($results, 200);
        }
        //
        if ( !(isset($login_results['id']) && $login_results['id']) ){
            $results['error'] = "Invalid phone or user not found";
            return $response->withJson($results, 200);
        }

        // LOGO PARA STORES
        //echo $sale_type_id . " " . PROD_TYPE_CUSTOMER_ID . " " . PROD_TYPE_STORE_ID; exit;

        //
         //
         $profile_pic = Customers::getCustomerProfilePic($login_results['id'], $login_results['img_ext']);
         //dd($profile_pic);
         //
         if ( $profile_pic ){
             $login_results['profile_img'] = $profile_pic;
         }


        //
        unset($login_results['img_ext']);


        //
        $login_results['token'] = base64_encode($login_results['token']);
        $login_results['t'] = "f";



        //dd($login_results); exit;
        


        //
        return $response->withJson($login_results, 200);
    }




    //
    public static $google_api_key = "AIzaSyAnefoaTTKkeSdSfD-s8vP1rkniL3ePBi8";



    //
    public static function getGoogleGeocodingLocation($lat, $lng){
        //
        if ( Helper::valid_lat($lat) && Helper::valid_lng($lng) ){

            //
            $api_maps = 'https://maps.googleapis.com/maps/';

            //
            $url = $api_maps . 'api/geocode/json?key=' .self::$google_api_key . "&latlng=" . $lat . ',' . $lng;
            //echo $url; exit;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //
            $curl_response = curl_exec($curl);
            curl_close($curl);

            //
            $response = json_decode($curl_response, true);
            //dd($response['results'][0]); exit;

            //
            if ( $response && isset($response['results']) && isset($response['results'][0]) ){
                //
                $first_place = $response['results'][0];
                //dd($first_place);
                return self::getPlaceInfo($first_place);
            }

        }
        //$results['error'] = "Invalid lat lng"; return $response->withJson($results, 200);
        return null;
    }


    public static function getIpInfoIoLocation($ip_address){
        //
        $access_token = '5f49811b222f45';
        $client = new IPinfo($access_token);
        //
        $details = $client->getDetails($ip_address);
        //dd($details); exit;
        //
        $geo_provider_id_ipinfoio = 2;
        //
        return [
            "postal_code" => $details->postal,
            "state" => $details->region,
            "country" => $details->country_name,
            "formatted_address" => null,
            "geo_pid" => $geo_provider_id_ipinfoio,
            "city" => $details->city,
            "short_address" => null,
            "lat" => (float)$details->latitude,
            "lng" => (float)$details->longitude
        ];
    }




    //
    public function PostGetUserLocationInfo($request, $response, $args) {


        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;


        $v = new ValidatorHelper();


        //
        $lat = $v->safeVar($body_data, 'lat');
        $lng = $v->safeVar($body_data, 'lng');
        $visitorId = $v->safeVar($body_data, 'vid');
        $ip_address = get_ip_address();

        // override for localhost
        if ( $ip_address === "::1" || $ip_address === "127.0.0.1" ){
            $ip_address = "189.202.61.106";
        }

        // User Info
        //echo "$lat $lng $visitorId $ip_address"; exit;



        //
        $place_results = self::getGoogleGeocodingLocation($lat, $lng);
        //dd($place_results); exit;
        //
        if ( $place_results && isset($place_results['lat']) && isset($place_results['lng']) ){

            /*  */

        } else {
            //
            $place_results = self::getIpInfoIoLocation($ip_address);
        }





        //
        if ($place_results && isset($place_results['lat']) && isset($place_results['lng'])){

            //
            $lat = $place_results['lat'];
            $lng = $place_results['lng'];
            $city = $place_results['city'];
            $state = $place_results['state'];
            $country = $place_results['country'];
            $address = $place_results['short_address'];
            $postal_code = $place_results['postal_code'];
            $geo_provider_id = $place_results['geo_pid'];

            //
            $insert_update_id = 0;
            $affected_rows = 0;
            //
            $sp_res = Query::StoredProcedure([
                "ret" => "single",
                "stmt" => function(){
                    return "{call usp_upsertVisitorInfo(?,?,?,?,?,?,?,?,?,?,?,?)}";
                },
                "exeptions_msgs" => [
                    "default" => "Server Error, unable to do operation"
                ],
                "debug" => true,
                "params" => function() use($visitorId, $lat, $lng, $city, $state, $country, $address, $postal_code, $geo_provider_id, $ip_address, &$insert_update_id, &$affected_rows){
                    //
                    return [
                        // 10 in params
                        array($visitorId, SQLSRV_PARAM_IN),
                        array($lat, SQLSRV_PARAM_IN),
                        array($lng, SQLSRV_PARAM_IN),
                        array($city, SQLSRV_PARAM_IN),
                        array($state, SQLSRV_PARAM_IN),
                        array($country, SQLSRV_PARAM_IN),
                        array($address, SQLSRV_PARAM_IN),
                        array($postal_code, SQLSRV_PARAM_IN),
                        array($geo_provider_id, SQLSRV_PARAM_IN),
                        array($ip_address, SQLSRV_PARAM_IN),
                        // 2 out params
                        array(&$insert_update_id, SQLSRV_PARAM_OUT),
                        array(&$affected_rows, SQLSRV_PARAM_OUT)
                    ];
                },
            ]);

            //dd($place_results);exit;
            return $response->withJson([
                "lat" => $place_results['lat'],
                "lng" => $place_results['lng'],
                "city" => $place_results['city'],
                "vid" => $insert_update_id,
                "vr" => ($affected_rows > 0) ? 0 : 1
            ], 200);
        }

        //
        return $response->withJson(null, 200);
    }




    //
    public static function getPlaceInfo($place){
        //dd($place); exit;

        //
        $route = null; $street_number = null; $neighborhood = null; $sublocality = null; $postal_code = null; $city = null; $state = null; $country = null;

        //
        foreach($place['address_components'] as $idx => $item){
            foreach($item['types'] as $idx2 => $type){
                //
                if ( $type ===  "route"){
                    $route = $item['short_name'];
                }
                if ( $type ===  "street_number"){
                    $street_number = $item['long_name'];
                }
                if ( $type ===  "postal_code"){
                    $postal_code = $item['long_name'];
                }
                if ( $type ===  "neighborhood"){
                    $neighborhood = $item['long_name'];
                }
                if ( $type ===  "sublocality"){
                    $sublocality = $item['long_name'];
                }
                if ( $type ===  "locality"){
                    $city = $item['long_name'];
                }
                //
                if ( $type ===  "administrative_area_level_1" ){
                    $state = $item['long_name'];
                }
                //
                if ( $type ===  "country" ){
                    $country = $item['long_name'];
                }
            }
        }


        //
        if (!$city){
            foreach($place['address_components'] as $idx => $item){
                foreach($item['types'] as $idx2 => $type){
                    //
                    if ( $type ===  "administrative_area_level_2" ){
                        $city = $item['long_name'];
                    }
                }
            }
        }

        //
        $neighborhood2 = ($neighborhood) ? ", " . $neighborhood : $sublocality;
        $postal_code2 = ($postal_code) ? ", " . $postal_code : "";


        // ignoramos los acentos
        if ( $city === "Centro" && ( strcasecmp($state, "Ciudad de México") === 0 ) ){ $city = str_replace(",", "", $neighborhood2); }
        //echo $city; exit;



        //
        $geo_provider_id_google_maps = 1;
        //
        return [
            "postal_code" => $postal_code,
            "formatted_address" => $place['formatted_address'],
            "state" => $state,
            "country" => $country,
            "geo_pid" => $geo_provider_id_google_maps,
            "city" => $city,
            "short_address" => $route . " " . $street_number . $neighborhood2 . $postal_code2 . ", " . $city,
            "lat" => $place['geometry']['location']['lat'],
            "lng" => $place['geometry']['location']['lng'],
        ];
    }



    //
    public function GetCheckValidToken($request, $response, $args) {
        //
        $user_session_data = $request->getAttribute("ses_data");
        //dd($user_session_data); exit;
        //echo "test"; exit;
        //
        //$user_session_data['notifications'] = Notifications::GetAllByType($user_session_data['ut'], $user_session_data['id']);
        //
        return $response->withJson($user_session_data, 200);
    }



    //
    public function getPlatformConfig($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;


        //
        $results = [];

        $appid = (int)$request->getQueryParam("appid");
        //echo $appid; exit;


        $account_id = Helper::getAccountByAppId($appid);
        //echo $account_id; exit;

        //
        if ( $appid === APP_ID_MISSIONEXPRESS ){
            
             //
             $results['square_config'] = [];
             //
             $square_info = ConfigSquare::GetRecord($account_id);
             //dd($square_info); exit;
             if (isset($square_info['error']) && $square_info['error']){
                 $results['error'] = $square_info['error'];
                 return $response->withJson($results, 200);
             }
             $app_id = null;
             $loc_id = null;
             $is_prod = false;
             if (isset($square_info['id']) && $square_info['id']){
                 if (isset($square_info['is_prod']) && $square_info['is_prod']){
                     $app_id = $square_info['prod_app_id'];
                     $loc_id = $square_info['prod_loc_id'];
                     $is_prod = true;
                 } else {
                     $app_id = $square_info['dev_app_id'];
                     $loc_id = $square_info['dev_loc_id'];
                 }
             }
             //
             $results['square_config'] = [
                'applicationId' => $app_id,
                'locationId' => $loc_id,
                'is_prod' => $is_prod,
             ];

        }
        //
        else if ( $appid === APP_ID_PLABUZ ){
            //
            $results['stripe_config'] = [];
            //
            $stripe_info = ConfigStripe::GetRecord($account_id);
            //dd($stripe_info); exit;
            if (isset($stripe_info['error']) && $stripe_info['error']){
                $results['error'] = $stripe_info['error'];
                return $response->withJson($results, 200);
            }
            $pk = null;
            $is_prod = false;
            if (isset($stripe_info['id']) && $stripe_info['id']){
                if (isset($stripe_info['is_prod']) && $stripe_info['is_prod']){
                    $pk = $stripe_info['public_key'];
                    $is_prod = true;
                } else {
                    $pk = $stripe_info['public_key_test'];
                }
            }
            $results['stripe_config'] = [
                'pk' => $pk,
                'is_prod' => $is_prod,
             ];
        }
        //
        else if ( $appid === APP_ID_T4B ){
            
            //
            $results['authorizenet'] = [];
            //
            $authorizenet_info = ConfigAuthorizeNet::GetRecord($account_id);
            //dd($authorizenet_info); exit;
            if (isset($authorizenet_info['error']) && $authorizenet_info['error']){
                $results['error'] = $authorizenet_info['error'];
                return $response->withJson($results, 200);
            }
            $login_id = null;
            $trans_key = null;
            $is_prod = false;
            if (isset($authorizenet_info['id']) && $authorizenet_info['id']){                
                if (isset($authorizenet_info['is_prod']) && $authorizenet_info['is_prod']){
                    $login_id = $authorizenet_info['login_id'];
                    $trans_key = $authorizenet_info['trans_key'];
                    $is_prod = true;
                } else {
                    $login_id = $authorizenet_info['login_id_test'];
                    $trans_key = $authorizenet_info['trans_key_test'];
                }
            }
            //
            $results['authorizenet'] = [
               //'login_id' => $login_id,
               //'trans_key' => $trans_key,
               'is_prod' => $is_prod,
            ];

        }

        //        
        $results['expiry_mins_limite'] = Utils::getExpiryMinutosLimite();
        
        //
        return $response->withJson($results, 200);
    }

 



    //
    public function PostAuthRequestActivationCode($request, $response, $args) {
        //
        $app_data = $request->getAttribute("app");
        //dd($app_data);
        //
        $app_id = (int)$app_data['id'];
        $account_id = (int)$app_data['account_id'];
        //echo "$app_id $account_id"; exit;



        //
        $results = array();
        //
        $v = new ValidatorHelper();


        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;



        //
        $email = $v->safeVar($body_data, 'email');
        $request_identifier = $v->safeVar($body_data, 'request_identifier');
        


        //
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "Provide valid email"; return $response->withJson($results, 200);
        }
        if ( !$request_identifier ){
            $results['error'] = "Provide request identifier"; return $response->withJson($results, 200);
        }
        //echo $email; exit;
        


        //
        $customer_info = Customers::GetRecordByEmail($app_id, $email);
        if ( isset($customer_info['error']) ){
            $results['error'] = $customer_info['error'];
            return $response->withJson($results, 200);
        }
        //
        if ( !(isset($customer_info['id'])) ){
            $results['error'] = "No se encontro la cuenta"; return $response->withJson($results, 200);
        }
        //
        $record_id = $customer_info['id'];
        $customer_name = $customer_info['name'];
        //
        $limit_requests_section_name = "request_recover_customer";
        //dd($customer_info); exit;




        /*
         *
         * LIMITAMOS SOLICITUDES A ESTA SECCION
         *
         * */
        //
        $max_requests = 3;
        $wait_minutes = 1;
        //
        $limit_section_results = Query::LimitSectionRequests($limit_requests_section_name, $request_identifier, $max_requests, $wait_minutes);
        //dd($limit_section_results); exit;
        if ( isset($limit_section_results['error']) && $limit_section_results['error'] ){
            $results['error'] = $limit_section_results['error']; return $response->withJson($results, 200);
        }






        /*
         *
         *
         * CREAMOS EL CODIGO DE ACTIVACION PERO SOLAMENTE HASTA "N" CANTIDAD DE VECES
         * PARA EVITAR QUE SOLICITEN DE MAS DESDE CIERTO DISPOSITIVO
         *
         *
         * */
        $digits = 4;
        $activation_code = str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
        //
        $create_restuls = ActivationCodes::Create($account_id, $record_id, $activation_code, $request_identifier);
        //dd($create_restuls);
        if ( isset($create_restuls['error']) ){
            $results['error'] = $create_restuls['error'];
            return $response->withJson($results, 200);
        }

        // ACID = ACTIVATION-CODE-ID 
        $customer_info['acid'] = $activation_code;


        $parse_info = $customer_info;
        $parse_info['activation_code'] = $activation_code;
        //dd($parse_info); exit;



        //
        $customer_info['send_email_results'] = Helper::SendEmail($account_id, $app_id, MAQUETA_ID_CUST_RECUP_CTA, $customer_name, $email, true, function($maqueta_email_msg) use($parse_info){
            return Customers::ParseCustomerMessages($parse_info, $maqueta_email_msg);
        });


        //
        return $response->withJson($customer_info, 200);
    }








    //
    public function PostRecoverActivate($request, $response, $args) {
        //
        $app_data = $request->getAttribute("app");
        //dd($app_data); exit;
        //
        $app_id = (int)$app_data['id'];
        $account_id = Helper::getAccountByAppId($app_id);
        //echo "$app_id $account_id"; exit;


        //
        $results = array();
        //
        $v = new ValidatorHelper();


        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;



        //
        $record_id = $v->safeVar($body_data, 'id');
        $activation_code = $v->safeVar($body_data, 'activation_code');
        //echo "$record_id $activation_code"; exit;




        //
        if ( !is_numeric($record_id) ){
            $results['error'] = "Provide customer id"; return $response->withJson($results, 200);
        }
        //
        if ( !$activation_code){
            $results['error'] = "Provide activation code"; return $response->withJson($results, 200);
        }
       
        

        /*
         *
         * ACTIVATE ACCOUNT/SUCURSAL/USER
         *
         * */
        $get_code_results = ActivationCodes::GetActivationCode($record_id, $activation_code);
        //dd($get_code_results); exit;
        //
        if ( isset($get_code_results['error']) && $get_code_results['error'] ){
            $results['error'] = $get_code_results["error"];
            return $response->withJson($results, 200);
        }
        //
        if ( !(isset($get_code_results['id']) && $get_code_results['id']) ){
            $results['error'] = "Codigo de activacion no valido";
            return $response->withJson($results, 200);
        }




        //
        return $response->withJson($get_code_results, 200);

    }







    //
    public function PostAuthUpdatePassword($request, $response, $args) {
         //
         $app_data = $request->getAttribute("app");
         //dd($app_data); exit;
         //
         $app_id = (int)$app_data['id'];
         $account_id = Helper::getAccountByAppId($app_id);
         //echo "$app_id $account_id"; exit;


        //
        $results = array();
        //
        $v = new ValidatorHelper();


        //
        $body_data = $request->getParsedBody();
        //$results['_debug'] = $body_data; return $response->withJson($results, 200);
        //var_dump($body_data); exit;



        //
        $record_id = $v->safeVar($body_data, 'id');
        $activation_code = $v->safeVar($body_data, 'activation_code');
        $password = $v->safeVar($body_data, 'password');
        //echo " $record_id $activation_code $password "; exit;



        //
        if ( !is_numeric($record_id) ){
            $results['error'] = "Provide worker id"; return $response->withJson($results, 200);
        }
        if ( !$activation_code ){
            $results['error'] = "Provide activation code"; return $response->withJson($results, 200);
        }
        //
        if (strlen($password) < 8 || strlen($password) > 16) {
            //$results['error'] = "Password debe de contener de 8 a 16 caracteres"; return $response->withJson($results, 200);
        }
        if (!preg_match("/\d/", $password)) {
            $results['error'] = "Password debe de contener al menos 1 digito"; return $response->withJson($results, 200);
        }
        if (!preg_match("/[A-Z]/", $password)) {
            //$results['error'] = "Password debe de contener al menos 1 mayuscula"; return $response->withJson($results, 200);
        }
        if (!preg_match("/[a-z]/", $password)) {
            //$results['error'] = "Password debe de contener al menos 1 minuscula"; return $response->withJson($results, 200);
        }
        if (!preg_match("/\W/", $password)) {
            //$results['error'] = "Password debe de contener al menos un caracter especial"; return $response->withJson($results, 200);
        }
        if (preg_match("/\s/", $password)) {
            $results['error'] = "Password no debe de contener espacios en blanco"; return $response->withJson($results, 200);
        }
        //
        //$password_hash = EncryptHelper::hash($password);


        /*
         *
         * EJECUTAMOS EL USP PARA ACTUALIZAR EL PASSWORD
         *
         * */
        $update_results = Auth::RecoverUpdatePassword($app_id, $record_id, $activation_code, $password);
        //dd($update_results); exit;
        if ( isset($update_results['error']) && $update_results['error'] ){
            $results['error'] = $update_results['error'];
            return $response->withJson($results, 200);
        }





        /*
         * HACEMOS DIRECT LOGIN
         * */
        $login_results = Auth::PostDirectLogin($app_id, $record_id);
        //dd($login_results); exit;
        if ( isset($login_results['error']) && $login_results['error'] ){
            $results['error'] = $login_results['error'];
            return $response->withJson($results, 200);
        }
        //
        if ( !(isset($login_results['id']) && $login_results['id']) ){
            $results['error'] = "Invalid phone or user not found";
            return $response->withJson($results, 200);
        }
        //
        $login_results['create_acct_updated_rows'] = $update_results['updated_rows'];

        //
        $login_results['ut'] = "CT";
        $login_results['token'] = base64_encode($login_results['token']);



        //dd($login_results); exit;
        return $response->withJson($login_results, 200);
    }










}