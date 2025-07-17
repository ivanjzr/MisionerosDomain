<?php
namespace App\Contacts;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;


use App\Paths;
use App\Stores\Stores;
use Helpers\PHPMicroParser;
use Helpers\Query;


//
class Contacts
{




  //
    public static function GetRecordById($account_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM v_contacts t
                      
                        Where t.account_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $account_id,
                $id
            ],
            "parse" => function(&$row){
            }
        ]);
    }




    //
    public static function updateContactType($field_type, $account_id, $contact_id, $active){
        //
        return Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    contacts
                  
                  Set
                    is_{$field_type} = 1,
                    is_{$field_type}_active = ?
                    
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [$active, $account_id, $contact_id],
            "parse" => function($updated_rows, &$query_results) use($contact_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$contact_id;
            }
        ]);
    }



    public static function removeContactType($field_type, $account_id, $contact_id){
        //
        return Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    contacts
                  
                  Set
                    is_{$field_type} = Null,
                    is_{$field_type}_active = Null
                    
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [$account_id, $contact_id],
            "parse" => function($updated_rows, &$query_results) use($contact_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$contact_id;
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




    
}
