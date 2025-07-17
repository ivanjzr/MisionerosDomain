<?php
namespace App\Customers;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;


use App\Paths;
use App\Stores\Stores;
use Helpers\PHPMicroParser;
use Helpers\Query;


//
class Customers
{













    //
    public static function GetAll(){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    t.*
                      FROM v_customers t
			";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);
    }










    //
    public static function Search($account_id, $search_text){

        //
        $filter_clause = "";

        //
        if ( $search_text ){
            //
            $filter_clause .= " And ( 
                        ( t.id like '%$search_text%' ) Or
                        ( t.name like '%$search_text%' ) Or
                        ( t.phone_number like '%$search_text%' ) Or
                         ( t.email like '%$search_text%' )
                    )";
        }
        //echo $filter_clause; exit;

        //
        return Query::All([
            "stmt" => function() use($filter_clause){
                return "
                   SELECT
                  
                    Top 10 
                    
                    t.id,
                    t.name,
                    t.name,
                    t.email,
                    t.address,
                    t.phone_country_id,
                    t.phone_cc,
                    t.phone_number
                    
                      FROM v_customers t
                        
                        Where t.account_id = ?
                        $filter_clause 
                        
                ";
            },
            "params" => [
                $account_id
            ]
        ]);
    }






    
    //
    public static function SearchCustomer($search_text){

        //
        $filter_clause = "";

        //
        if ( $search_text ){
            //
            $filter_clause .= " And ( 
                        ( t.id like '%$search_text%' ) Or
                        ( t.company_name like '%$search_text%' ) Or
                        ( t.name like '%$search_text%' ) Or
                        ( t.phone_number like '%$search_text%' ) Or
                         ( t.email like '%$search_text%' )
                    )";
        } else {
            $filter_clause .= " And 1=2 "; 
        }
        //echo $filter_clause; exit;

        //
        return Query::Multiple("
                   SELECT
                  
                    Top 10 
                    
                    t.id,
                    t.name,
                    t.username,
                    t.tipo,
                    t.email,
                    t.address,
                    t.phone_country_id,
                    t.phone_cc,
                    t.phone_number
                    
                      FROM v_customers t
                        
                        Where 1=1
                        And t.customer_type_id = 2
                        {$filter_clause}
                        
                ", [], function(&$row){
                    
                });
    }



    


    //
    public static function getAuthData($id){

        //
        return Query::Get([
            "stmt" => function(){
                return "
                   Select

                    t.id,
                    t.name,
                    t.address,
                    t.lat,
                    t.lng,
                    t.email,
                    t.phone_country_id,
                    t.phone_cc,
                    t.phone_number,
                    t.img_ext,
                    t.app_id
                
                    -- select * from v_customers
                    From v_customers t

					--
                    Where t.id = ?
                ";
            },
            "params" => [
                $id
            ],
            "parse" => function(&$row){
                if ( $profile_img = Customers::getCustomerProfilePic($row['id'], $row['img_ext'])){
                    $row['profile_img'] = $profile_img;
                    unset($row['img_ext']);
                }
            }
        ]);
    }



    //
    public static function GetRecordById($account_id, $id, $hide_password = true){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM v_customers t
                      
                        Where t.account_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $account_id,
                $id
            ],
            "parse" => function(&$row) use ($hide_password){
                //
                if ( $hide_password && isset($row['password']) ){
                    unset($row['password']);
                }
            }
        ]);
    }






    //
    public static function GetRecordByIdAndUrl($id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM v_customers t
                      
                        Where t.id = ?
                ";
            },
            "params" => [
                $id
            ],
            "parse" => function(&$row){
                //
                unset($row['password']);
            }
        ]);
    }









    //
    public static function GetRecordByIdAndPassword($account_id, $id, $password){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                  
                    t.*
                      
                      FROM v_customers t                    
                        
                        Where t.id = ?
                        And t.password = ?
                        
                        And t.active = 1
                ";
            },
            "params" => [
                $account_id,
                $id,
                $password
            ]
        ]);
    }
















    //
    public static function GetRecordByPhone($phone_cc, $phone_number){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.id,
                    t.name,
                    t.email,
                    t.phone_country_id,
                    t.phone_cc,
                    t.phone_number
                    
                      FROM v_customers t                    
                        
                        Where t.phone_cc = ?
                        And t.phone_number = ?
                ";
            },
            "params" => [
                $phone_cc,
                $phone_number
            ],
            "parse" => function(&$row){
            //
            }
        ]);
    }





    //
    public static function GetRecordByEmail($app_id, $email){
        return Query::Single("SELECT
                    
                    t.id,
                    t.name,
                    t.email,
                    t.phone_country_id,
                    t.phone_cc,
                    t.phone_number
                    
                      FROM v_customers t                    
                        
                        Where t.email = ?
                        And t.app_id = ?

                        ", [$email, $app_id]);
    }













    //
    public static function UpdateImgExt($img_ext, $account_id, $customer_id){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);



            //
            $stmt = "Update contacts
                       Set
                        
                        img_ext = ?
                        
                      Where account_id = ?
                      And is_customer = 1
                      And id = ?
                     ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;


            //
            $params = array(
                $img_ext,
                $account_id,
                $customer_id
            );
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query_update = $sqlsrv_inst->query_next($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_update) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }


            //
            $results['affected_rows'] = $query_update;
            $results['id'] = $customer_id;



            //
            return $results;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }







    //
    public static function ParseCustomerMessages($data, $template_content){
        //
        $php_microparser = new PHPMicroParser();
        //
        $php_microparser->setVariable("name", $data['name']);
        $php_microparser->setVariable("phone_number", $data['phone_cc'] . " " . $data['phone_number']);
        //
        if (isset($data['activation_code'])){
            $php_microparser->setVariable("activation_code", $data['activation_code']);
        }
        //
        if (isset($data['activation_link'])){
            $php_microparser->setVariable("activation_link", $data['activation_link']);
        }
        //
        return $php_microparser->parseVariables($template_content);
    }






    //
    public static function getCustomerProfilePic($customer_id, $img_ext){
        //
        $customer_profile_img = Customers::getCustomerSectionPath($customer_id, "profile").DS."me.".$img_ext;
        $customer_profile_img_url = FULL_DOMAIN."/files/customers/".$customer_id."/profile/me.".$img_ext;
        //echo " $customer_profile_path $customer_profile_url "; exit;
        //
        if ( is_file($customer_profile_img) ){
            return $customer_profile_img_url;
        }
        return null;
    }




    public static function getCustomerSectionPath($customer_id, $path){
        //
        $customer_path = Paths::$path_customers.DS.$customer_id;
        //echo $customer_path; exit;
        //
        if (!is_dir($customer_path)){
            mkdir($customer_path);
        }
        //
        $section_path = $customer_path.DS.$path;
        //
        if (!is_dir($section_path)){
            mkdir($section_path);
        }
        //
        return $section_path;
    }



}
