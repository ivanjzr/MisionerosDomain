<?php
namespace App\Locations;




//
use Helpers\Query;


//
class CatPaises
{







    //
    public static function GetAll(){
        //
        return Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_cat_paises t";
            },
            "params" => [
            ]
        ]);
    }






    //
    public static function GetById($pais_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "SELECT t.* FROM sys_cat_paises t Where t.id = ?";
            },
            "params" => [
                $pais_id
            ]
        ]);
    }









}