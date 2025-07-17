<?php
namespace App\Maquetas;


//
use Helpers\Query;


//
class MaquetasDocumentos
{







    //
    public static function GetAll($account_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT 
                    t.*
                        FROM maquetas_documentos t
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
    public static function GetAllByType($account_id, $tipo_documento_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT 
                    t.*,
				    t2.nombre maqueta_nombre
                        FROM maquetas_documentos t
				            Left Join sys_documentos t2 On t2.id = t.tipo_documento_id
                           Where t.account_id = ?
                           And t.tipo_documento_id = ?
			";
            },
            "params" => [
                $account_id,
                $tipo_documento_id
            ],
            "parse" => function(){

            }
        ]);
    }









     //
     public static function GetMaquetaInfo($account_id, $tipo_documento_id){
        //echo " $account_id, $maqueta_id "; exit;
        //
        return Query::Single("
                   select 
                        t.*
                            from maquetas_documentos t 
                                
                                Where t.account_id = ?
                                And t.tipo_documento_id = ? 
                                And t.in_use = 1 
                ", [
            $account_id,
            $tipo_documento_id
        ], function(&$row){
            //var_dump($row); exit;
        });
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
                    
                      FROM maquetas_documentos t
                  
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
    public static function GetMaquetaCount($tipo_documento_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   select 
                        Count(*) maqueta_has_docs
                            from maquetas_documentos t 
                                
                                Where t.tipo_documento_id = ? 
                                And t.in_use = 1 
                ";
            },
            "params" => [
                $tipo_documento_id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }




}