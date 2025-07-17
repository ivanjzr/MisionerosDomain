<?php
namespace App\Stores;

//
use App\Catalogues\CatSalesStatus;
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use Helpers\PHPMicroParser;
use Helpers\Query;

//
class Stores
{













    //
    public static function GetAll(){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    t.*
                      FROM stores t
			";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);
    }










    //
    public static function Search($search_text){

        //
        if ( $search_text ){
            //
            $filter_clause = " And ( 
                        ( t.company_name like '%$search_text%' ) Or
                        ( t.store_title like '%$search_text%' ) Or
                        ( t.name like '%$search_text%' ) Or
                        ( t.username like '%$search_text%' ) Or
                        ( t.phone_number like '%$search_text%' ) Or
                         ( t.email like '%$search_text%' )
                    )";
        }


        //
        return Query::All([
            "stmt" => function() use($filter_clause){
                return "
                   SELECT
                  
                    Top 10 
                    
                    t.id,
                    t.company_name,
                    t.store_title,
                    t.name,
                    t.username,
                    t.email,
                    t.address,
                    t.phone_country_id,
                    t.phone_cc,
                    t.phone_number
                    
                      FROM viewStores t
                        
                        Where 1=1
                        $filter_clause 
                        
                ";
            },
            "params" => [

            ]
        ]);
    }





    //
    public static function getAuthData($id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   Select

				t.id,
				t.company_id,
				t.company_name,
				t.address,
                t.lat,
                t.lng,                          
				t.cat_company_type_id,
				t.tipo,
				t.store_title,
				t.name,
				t.email,
				t.phone_country_id,
				t.phone_cc,
				t.phone_number,
				t.img_ext,
				t2.app_id
				
				-- select * from ViewStores
				From ViewStores t

					--
                    Where t.id = ?
                ";
            },
            "params" => [
                $id
            ],
            "parse" => function(&$row){
                if ( $biz_logo = Stores::getStoreLogo($row['id'], $row['img_ext'])){
                    $row['biz_logo'] = $biz_logo;
                    unset($row['img_ext']);
                }
                $row['page_url'] = Stores::getStoreUrl($row['id'], $row['company_name'], $row['store_title']);
            }
        ]);
    }






    //
    public static function GetRecordById($id, $hide_password = true){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    -- ISNULL( (select AVG(k.biz_rating) from pedidos k Where k.store_id = t.id And k.biz_rating > 0 GROUP BY k.store_id), 0)  as average_rating
                    
                      FROM viewStores t   
                      
                        Where t.id = ?
                ";
            },
            "params" => [
                $id
            ],
            "parse" => function(&$row) use ($hide_password){
                //
                if ( $hide_password && isset($row['password']) ){
                    unset($row['password']);
                }
                //
                if ( $biz_logo = Stores::getStoreLogo($row['id'], $row['img_ext'])){
                    $row['biz_logo'] = $biz_logo;
                    unset($row['img_ext']);
                }
                //
                $row['page_url'] = Stores::getStoreUrl($row['id'], $row['company_name'], $row['store_title']);
            }
        ]);
    }






    //
    public static function GetRecordByIdOnly($id, $hide_password = true){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM viewStores t
                   
                        Where t.id = ?
                ";
            },
            "params" => [
                $id
            ],
            "parse" => function(&$row) use ($hide_password){
                //
                if ( $hide_password && isset($row['password']) ){
                    unset($row['password']);
                }
                //
                if ( $biz_logo = Stores::getStoreLogo($row['id'], $row['img_ext'])){
                    $row['biz_logo'] = $biz_logo;
                    unset($row['img_ext']);
                }
                //
                $row['page_url'] = Stores::getStoreUrl($row['id'], $row['company_name'], $row['store_title']);
            }
        ]);
    }







    //
    public static function FindStoreByPlaceId($place_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM viewStores t
                   
                        Where t.place_id = ?
                ";
            },
            "params" => [
                $place_id
            ],
            "parse" => function(&$row) {
                //
                if ( $biz_logo = Stores::getStoreLogo($row['id'], $row['img_ext'])){
                    $row['biz_logo'] = $biz_logo;
                }
                //
                $row['page_url'] = Stores::getStoreUrl($row['id'], $row['company_name'], $row['store_title']);



                //
                $row['arr_products'] = Query::Multiple("Select * from stores_products t Where t.store_id = ?", [
                    $row['id']
                ], function(&$row_product){
                    //
                    $prod_name = "p-".$row_product['id'].".".$row_product['img_ext'];
                    //
                    $product_path = Stores::getStoreSectionPath($row_product['store_id'], "products").DS.$prod_name;
                    $product_url = FULL_DOMAIN."/files/stores/".$row_product['store_id']."/products/".$prod_name;

                    //
                    if (is_file($product_path)){
                        $row_product['prod_img'] = $product_url;
                    }
                });


                //
                unset($row['img_ext']);
                unset($row['password']);
            }
        ]);
    }









    //
    public static function GetRecordByIdAndPassword($id, $password){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                  
                    t.*
                      
                      FROM stores t                    
                        
                        Where t.id = ?
                        And t.password = ?
                        
                        And t.active = 1
                ";
            },
            "params" => [
                $id,
                $password
            ]
        ]);
    }














    //
    public static function getStoreImage(&$row, $store_id, $img_ext, $img_name){

        //$store_path = PATH_PUBLIC.DS."stores".DS.$store_id;

        //
        if ($img_ext){
            //
            $img_path = PATH_PUBLIC.DS.'stores'.DS.$store_id.DS.$img_name.".".$img_ext;
            //echo $logo_path; exit;
            if ( is_file($img_path) ){
                $row['img_url'] = "/stores/{$store_id}/{$img_name}.{$img_ext}";
            }
        }
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
                    
                      FROM stores t                    
                        
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
    public static function UpdateImgExt($img_ext, $store_id){
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
            $stmt = "Update stores
                       Set
                        
                        img_ext = ?
                        
                      Where id = ?
                     ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;


            //
            $params = array(
                $img_ext,
                $store_id
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
            $results['id'] = $store_id;



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
    public static function ParseStoresMessages($data, $template_content){
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
    public static function getStoreUrl($store_id, $company_name, $store_title){
        return "/store/" . $store_id . "/" . toValidUrl($company_name . "-" . $store_title);
    }


    //
    public static function getStoreLogo($store_id, $img_ext){
        //
        $file_name = "me.".$img_ext;
        //
        $store_profile_path = Stores::getStoreSectionPath($store_id, "profile").DS.$file_name;
        $store_profile_url = FULL_DOMAIN."/files/stores/".$store_id."/profile/".$file_name;
        //echo $store_profile_path; exit;
        //
        if ( is_file($store_profile_path) ){
            return $store_profile_url;
        }
        return null;
    }



    public static function getStoreSectionPath($store_id, $path){
        //
        $store_path = Paths::$path_stores.DS.$store_id;
        //
        if (!is_dir($store_path)){
            mkdir($store_path);
        }
        //
        $section_path = $store_path.DS.$path;
        //
        if (!is_dir($section_path)){
            mkdir($section_path);
        }
        //
        return $section_path;
    }


}
