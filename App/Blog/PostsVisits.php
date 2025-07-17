<?php
namespace App\Blog;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class PostsVisits
{












    //
    public static function GetAll($app_id, $post_id){

        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*
                  
                        FROM posts_visits t
                        
                           Where t.app_id = ?
                           And t.post_id = ?

                        Order By t.id Desc
			";
            },
            "params" => [
                $app_id,
                $post_id
            ],
            "parse" => function(){

            }
        ]);
    }












    //
    public static function registerVisit($app_id, $post_id, $visit_identifier){
        //
        $results = array();
        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_PostsVisitsAdd(?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation",
                "VISIT_ALREADY_REGISTERED" => "Visit already registered"
            ],
            "params" => function() use($app_id, $post_id, $visit_identifier, &$param_record_id){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($post_id, SQLSRV_PARAM_IN),
                    array($visit_identifier, SQLSRV_PARAM_IN),
                    //
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }

        //
        $results['id'] = $param_record_id;

        //
        return $results;
    }








}