<?php
namespace App\Site;


//
use Helpers\Query;


//
class SiteLayouts
{







    //
    public static function GetAll($app_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT 
                    t.*
                        FROM site_layouts t
                           Where t.app_id = ?
			";
            },
            "params" => [
                $app_id
            ],
            "parse" => function(){

            }
        ]);
    }











    //
    public static function GetRecordById($app_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM site_layouts t
                  
                        Where t.app_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $app_id,
                $id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }




}