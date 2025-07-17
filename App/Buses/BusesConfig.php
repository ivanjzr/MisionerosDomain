<?php
namespace App\Buses;


//
use Helpers\Query;


//
class BusesConfig
{













    //
    public static function GetRecordById($account_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM products_config t
                  
                        Where t.account_id = ?
                ";
            },
            "params" => [
                $account_id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }




}