<?php
namespace App\Maquetas\MaquetasMensajes;


//
use Helpers\Query;


//
class MaquetasMensajes
{







    //
    public static function GetAll($account_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT 
                    t.*
                        FROM maquetas_mensajes t
				           Where t.account_id = ?
                           And t.active = 1
			";
            },
            "params" => [
                $account_id
            ],
            "parse" => function(){

            }
        ]);
    }



    //
    public static function GetAllByType($account_id, $maqueta_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT 
                    t.*,
				    t2.nombre maqueta_nombre
                        FROM maquetas_mensajes t
				            Left Join sys_maquetas t2 On t2.id = t.maqueta_id
                           Where t.account_id = ?
                           And t.maqueta_id = ?
                           And ( t.sms_active = 1 Or email_active = 1 )
			";
            },
            "params" => [
                $account_id,
                $maqueta_id
            ],
            "parse" => function(){

            }
        ]);
    }











    //
    public static function GetRecordById($account_id, $id){
        //echo " $account_id, $id "; exit;
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM maquetas_mensajes t
                  
                        Where t.account_id = ? 
                        And t.id = ?
                ";
            },
            "params" => [
                $account_id,
                $id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }






    //
    public static function GetMaquetaInfo($account_id, $maqueta_id, $get_copias_phones = false, $get_copias_emails = false){
        //echo " $account_id, $maqueta_id "; exit;
        //
        return Query::Single("
                   select 
                        t.*
                            from maquetas_mensajes t 
                                
                                Where t.account_id = ?
                                And t.maqueta_id = ? 
                                And t.in_use = 1 
                                And (t.sms_active = 1 Or email_active = 1)
                ", [
            $account_id,
            $maqueta_id
        ], function(&$row) use ($get_copias_phones, $get_copias_emails){
            //var_dump($row); exit;
            //
            if ($get_copias_phones){
                $row['copias_phones'] = MaquetasMensajesCopiasPhones::GetAllByMensajeId($row['account_id'], $row['id']);
            }
            //
            if ($get_copias_emails){
                $row['copias_emails'] = MaquetasMensajesCopiasEmails::GetAllByMensajeId($row['account_id'], $row['id']);
            }
        });
    }








    //
    public static function GetMaquetaCount($maqueta_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   select 
                        Count(*) maqueta_has_mensajes
                            from maquetas_mensajes t 
                                
                                Where t.maqueta_id = ? 
                                And t.in_use = 1 
                                And (t.sms_active = 1 Or email_active = 1)
                ";
            },
            "params" => [
                $maqueta_id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }




}