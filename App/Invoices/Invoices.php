<?php
namespace App\Invoices;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;


use App\Paths;
use App\Stores\Stores;
use Helpers\PHPMicroParser;
use Helpers\Query;


//
class Invoices
{













    //
    public static function GetAll(){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    t.*
                      from v_customerst
			";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);
    }




    //
    public static function GetCustomerLastInvoice($customer_id){
        //
        return Query::Single("select top 1 * from ViewInvoices where customer_id = ? order by id desc", [$customer_id]);
    }









    //
    public static function Search($search_text, $customer_type_id){

        //
        $filter_clause = "";

        //
        if (is_numeric($customer_type_id)){
            //
            $filter_clause .= " And t.customer_type_id = {$customer_type_id}";
        }

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
        }
        //echo $filter_clause; exit;

        //
        return Query::All([
            "stmt" => function() use($filter_clause){
                return "
                   SELECT
                  
                    Top 10 
                    
                    t.id,
                    t.company_name,
                    t.name,
                    t.allow_credit,
                    t.comision_tipo,
                    t.comision_valor,
                    t.username,
                    t.customer_type_id,
                    t.tipo,
                    t.email,
                    t.address,
                    t.phone_country_id,
                    t.phone_cc,
                    t.phone_number
                    
                      FROM v_customers t
                        
                        Where 1=1
                        $filter_clause 
                        
                ";
            },
            "params" => [

            ]
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
                    
                      FROM invoices t
                      
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
            }
        ]);
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






    



    public static function getCustomerSectionPath($customer_id, $path){
        //
        $customer_path = Paths::$path_customers.DS.$customer_id;
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
