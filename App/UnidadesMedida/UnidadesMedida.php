<?php
namespace App\UnidadesMedida;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Helper;
use Helpers\Query;


//
class UnidadesMedida
{





    //
    public static function GetUMById($unidad_medida_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "SELECT t.* FROM sys_unidades_medida t Where t.id = ? Order By t.orden Desc";
            },
            "params" => [
                $unidad_medida_id
            ],
            "parse" => function(){

            }
        ]);
    }





}