<?php
namespace App\Companies;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\PHPMicroParser;
use Helpers\Query;

//
class Companies
{













    //
    public static function GetAll(){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    t.*
                      FROM companies t
                    Where t.active = 1
			";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);
    }










    //
    public static function Search($search_text){

        //
        if ( $search_text ){
            //
            $filter_clause = " And ( 
                        ( t.company_name like '%$search_text%' ) Or
                         ( t.company_name like '%$search_text%' )
                    )";
        }


        //
        return Query::All([
            "stmt" => function() use($filter_clause){
                return "
                   SELECT
                  
                    Top 10 
                    
                    t.*
                    
                      FROM companies t
                        
                        Where 1=1
                        $filter_clause 
                        
                        And t.active = 1
                        And t.username is Not Null
                ";
            },
            "params" => [

            ]
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
                    -- ISNULL( (select AVG(k.biz_rating) from pedidos k Where k.store_id = t.id And k.biz_rating > 0 GROUP BY k.store_id), 0)  as average_rating
                    
                      FROM companies t   
                      
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
