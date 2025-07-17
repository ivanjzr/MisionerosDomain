<?php
namespace App\Catalogues;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Helper;
use Helpers\Query;


//
class CatModels
{







    




    //
    public static function GetAll(){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                  
                    t.*
                        
                        FROM v_cat_models t
                        
                           Where t.active = 1

                    Order By t.id Asc
			";
            },
            "params" => [
            ],
            "parse" => function(){

            }
        ]);
    }












    //
    public static function GetAllForSite(){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                  
                    t.*
                        
                        FROM v_cat_models t
                        
                           Where t.active = 1
				           And t.allow_meal_plan = 1
				
                    Order By t.id Asc
			";
            },
            "params" => [

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
                    
                      FROM v_cat_models t
                  
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







    //
    public static function GetRecordByUrl($url){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM v_cat_models t
                  
                        Where t.url = ?
                        And t.active = 1
                ";
            },
            "params" => [
                $url
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }







}