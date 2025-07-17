<?php
namespace App\Site;


//
use Helpers\Query;


//
class SiteConfig
{













    //
    public static function GetRecordById($app_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM sites_config t
                  
                        Where t.app_id = ?
                ";
            },
            "params" => [
                $app_id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }




}