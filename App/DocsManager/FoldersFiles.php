<?php
namespace App\DocsManager;


//
use Helpers\Query;


//
class FoldersFiles
{







    //
    public static function GetAll($app_id, $folder_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT 
				       
                    t.*
				
                        FROM docs_manager_folders_files t
				
                           Where t.app_id = ?
                           And t.folder_id = ?
                            
				        Order By t.id Desc
			";
            },
            "params" => [
                $app_id,
                $folder_id
            ],
            "parse" => function(){

            }
        ]);
    }











    //
    public static function GetRecordById($app_id, $folder_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM docs_manager_folders_files t
                  
                        Where t.app_id = ?
                        And t.folder_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $app_id,
                $folder_id,
                $id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }




}