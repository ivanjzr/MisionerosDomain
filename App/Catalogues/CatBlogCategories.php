<?php
namespace App\Catalogues;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Helper;
use Helpers\Query;


//
class CatBlogCategories
{







    




    //
    public static function GetAll($app_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                  
                    t.*,
                    ( Select Count(*) From posts K Where K.category_id In (t.id) ) as posts_qty
                        
                        FROM cat_blog_categories t
                        
                           Where t.app_id = ?
                           And t.active = 1
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
    public static function GetRecordByUrl($app_id, $url){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM cat_blog_categories t
                  
                        Where t.app_id = ?
                        And t.url = ?
                        And t.active = 1
                ";
            },
            "params" => [
                $app_id,
                $url
            ],
            "parse" => function(&$row){
                //
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
                    
                      FROM cat_blog_categories t
                  
                        Where t.app_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $app_id,
                $id
            ]
        ]);
    }










}