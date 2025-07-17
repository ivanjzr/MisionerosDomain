<?php
namespace App\Notifications;




//
use Helpers\Query;


//
class Notifications
{







    




    //
    public static function GetAllByType($user_type, $record_id){

        //
        $str_field = null;
        $send_type = null;

        // if store
        if ($user_type === "ST"){
            $str_field = "k.store_id";
            $send_type = "s";
        }
        // if customer
        else if ($user_type === "CT"){
            $str_field = "k.customer_id";
            $send_type = "c";
        }
        //
        return Query::All([
            "stmt" => function() use($str_field){
                return "
				SELECT
                    t.* 
                    FROM notifications t
				
                        Where t.id Not In ( select k.notification_id from notifications_dismissed k where {$str_field} = ? ) 
                        And t.send_type = ?
                        And t.active = 1
				
                    Order By t.id Asc
			";
            },
            "params" => [
                $record_id,
                $send_type
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
                    
                      FROM notifications t
                  
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