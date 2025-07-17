<?php
namespace App\Catalogues;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class CatCustomersTypes
{












    //
    public static function GetAll($app_id){

        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*
				
                        FROM sys_cat_customers_types t
                        
                           Where t.active = 1

                        Order By t.id Desc
			";
            },
            "params" => [
            ],
            "parse" => function(){

            }
        ]);
    }




    //
    public static function GetRecordById($app_id, $id){

        //
        return Query::Single("
				SELECT
                    
                    t.*
				
                        FROM sys_cat_customers_types t
                        
                           Where And t.id = ?

			",
            [
                $id
            ]
        );
    }





    //
    public static function GetRecordByKeyName($app_id, $key_name){

        //
        return Query::Single("
				SELECT
                    
                    t.*
				
                        FROM sys_cat_customers_types t
                        
                           Where t.key_name = ?
			",
            [
                $key_name
            ]
        );
    }
















}