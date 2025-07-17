<?php
namespace App\Maquetas\MaquetasMensajes;

//;
use Helpers\Query;


//
class MaquetasMensajesCopiasPhones
{









    //
    public static function GetAllByMensajeId($account_id, $mensaje_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    t.*
                        FROM maquetas_mensajes_copias_phones t
                            Where t.account_id = ?
                            And t.mensaje_id = ?
			";
            },
            "params" => [
                $account_id,
                $mensaje_id
            ],
            "parse" => function(){

            }
        ]);
    }



    //
    public static function GetAll($app_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    t.*
                        FROM maquetas_mensajes_copias_phones t
                            Where t.account_id = ( select top 1 account_id from accounts_apps Where id = ? )
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
    public static function GetRecordById($app_id, $mensaje_id, $value_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM maquetas_mensajes_copias_phones t
                  
                        Where t.app_id = ?
                        And t.mensaje_id = ?
                        And t.id = ?
                ";
            },
            "params" => [
                $app_id,
                $mensaje_id,
                $value_id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }





}