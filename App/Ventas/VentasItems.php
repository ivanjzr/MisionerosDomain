<?php
namespace App\Ventas;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Helper;
use Helpers\Query;


//
class VentasItems
{





    //
    public static function GetAll($sale_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*,
                    prod.nombre,
				    prod.description
                  
                        FROM sales_items t
                  
                            Left Join products prod On prod.id = t.product_id
                  
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












    //
    public static function GetCustomerSaleItems($app_id, $customer_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                    
                    t.*
				
                        FROM sales_items t
                  
                            Left Join sales ts On ts.app_id = t.app_id And ts.id = t.sale_id
                            
                  
                           Where ts.app_id = ?
                           And ts.customer_id = ?
			";
            },
            "params" => [
                $app_id,
                $customer_id
            ],
            "parse" => function(){

            }
        ]);
    }







}