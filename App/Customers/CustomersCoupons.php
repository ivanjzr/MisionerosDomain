<?php
namespace App\Customers;


//
use Helpers\Query;

class CustomersCoupons
{






    //
    public static function GetCustomerCouponByCouponId($customer_id, $coupon_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM customers_coupons t
                   
                        Where t.customer_id = ?
                        And t.coupon_id = ?
                ";
            },
            "params" => [
                $customer_id,
                $coupon_id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }





    //
    public static function GetCustomerCouponById($customer_id, $id){
        // 
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*,
                    ts.description,
                    ts.coupon_code
                    
                      FROM customers_coupons t
                   
                        Left Join coupons ts On ts.store_id = t.store_id And ts.id = t.coupon_id
                   
                   
                            Where t.customer_id = ?
                            And t.id = ?
                ";
            },
            "params" => [
                $customer_id,
                $id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }






}
