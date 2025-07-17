<?php
namespace App\Site;


//
use Helpers\Query;


//
class SitePages
{







    //
    public static function GetAll($app_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT 
				       
                    t.*,
				    hf.header_footer_name,
				    hf.header,
				    hf.footer
				
                        FROM site_pages t
				
				            Left Join site_header_footer hf On hf.id = t.header_footer_id
				
                           Where t.app_id = ?
                           And t.active = 1
                            
				        Order By t.id Desc
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
                    
                      FROM site_pages t
                  
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