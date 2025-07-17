<?php
namespace App\Catalogues;


//
use Helpers\Query;


//
class CatFeatures
{




    //
    public static function GetAll($account_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT 
                         
                    t.*
				
                        FROM cat_features t
                  
                           Where t.account_id = ?
                           And t.active = 1
			";
            },
            "params" => [
                $account_id
            ],
            "parse" => function(){

            }
        ]);
    }






    //
    public static function GetRecordById($account_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM cat_features t
                  
                        Where t.account_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $account_id,
                $id
            ]
        ]);
    }




}