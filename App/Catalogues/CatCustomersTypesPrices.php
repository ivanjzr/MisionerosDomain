<?php
namespace App\Catalogues;




//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Query;


//
class CatCustomersTypesPrices
{












    //
    public static function GetAll($app_id, $customer_type_id){

        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*
				
                        FROM cat_customers_types_prices t
                        
                           Where t.app_id = ?
                           And t.customer_type_id = ?

                        Order By t.id Desc
			";
            },
            "params" => [
                $app_id,
                $customer_type_id
            ],
            "parse" => function(){

            }
        ]);
    }


















}