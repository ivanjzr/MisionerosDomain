<?php
namespace App\Promociones;

//
use Helpers\Query;


//
class Promociones
{




    //
    public static function GetAllAvailable($app_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				    SELECT
                  
                    t.*
                    
                      FROM promociones t
                      
                        Where t.app_id = ?
                        And t.active = 1
                        
                        And t.fecha_hora_fin >= GETDATE()
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
                    
                      FROM promociones t                    
                        
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
