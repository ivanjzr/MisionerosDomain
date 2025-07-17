<?php
namespace App\Ventas;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class VentasPayments
{





    //
    public static function GetAll($sale_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*
				
                        FROM sales_payments t
                  
                           Where t.sale_id = ?
			";
            },
            "params" => [
                $sale_id
            ],
            "parse" => function(){

            }
        ]);
    }





}