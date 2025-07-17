<?php
namespace App\Coupons;

//
use Helpers\Query;


//
class CouponsCoupons
{






    //
    public static function FindCuponByCode($app_id, $coupon_code){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*,
                    t2.purchase_min_amount,
                    t2.description,
                    t2.is_percentage,
                    t2.valor,
                    t2.fecha_hora_inicio,
                    t2.fecha_hora_fin,
                    t2.qty,
                    t2.active,
                    t2.datetime_created
                    
                      FROM coupons_coupons t
                        
                        Left Join coupons t2 On t2.id = t.coupon_id
                   
                        Where t.app_id = ?
                        And t.coupon_code = ?
                          
                        And t.active = 1
                        And t2.active = 1
                ";
            },
            "params" => [
                $app_id,
                $coupon_code
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }





    //
    public static function GetCouponCouponById($app_id, $coupon_coupon_id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*,
                    t2.description,
                    t2.is_percentage,
                    t2.valor,
                    t2.fecha_hora_inicio,
                    t2.fecha_hora_fin,
                    t2.qty,
                    t2.active,
                    t2.datetime_created
                    
                      FROM coupons_coupons t                    
                        
                        Left Join coupons t2 On t2.id = t.coupon_id
                   
                        Where t.app_id = ?
                        And t.id = ?
                          
                        And t.active = 1
                        And t2.active = 1
                ";
            },
            "params" => [
                $app_id,
                $coupon_coupon_id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }






    //
    public static function GetAllValid($app_id, $customer_id = null){

        //
        $str_customer_field = "";
        $str_where_id_used = "";
        if ($customer_id){
            $str_customer_field = "
                , (select k.datetime_created from customers_coupons k where k.customer_id = {$customer_id} And k.coupon_id = t.id ) as datetime_cut
                , (select k.datetime_reedemed from customers_coupons k where k.customer_id = {$customer_id} And k.coupon_id = t.id ) as datetime_reedemed
                ";
            //$str_where_id_used = " And t.id Not In ( select k.coupon_id from customers_coupons k where k.customer_id = {$customer_id} And k.datetime_reedemed Is Not Null )";
        }

        //
        return Query::Multiple("
                   SELECT
                    
                    t.* 
                    {$str_customer_field}
                    
                      FROM coupons t
                   
                        Where t.app_id = ?
                        And t.active = 1
                        {$str_where_id_used}

                ", [$app_id]);
    }











    //
    public static function SearchByCategoryAndCompanyStore($app_id, $category_id, $query_search, $customer_id = null){

        //
        $str_customer_field = "";
        $str_where_id_used = "";
        if ($customer_id){
            $str_customer_field = "
                , (select k.datetime_created from customers_coupons k where k.customer_id = {$customer_id} And k.coupon_id = t.id ) as datetime_cut
                , (select k.datetime_reedemed from customers_coupons k where k.customer_id = {$customer_id} And k.coupon_id = t.id ) as datetime_reedemed
                ";
            //$str_where_id_used = " And t.id Not In ( select k.coupon_id from customers_coupons k where k.customer_id = {$customer_id} And k.datetime_reedemed Is Not Null )";
        }

        //
        $str_search = "";
        if ($query_search){
            $str_search .= " And (
                    ( ts.company_name like '%$query_search%') Or
                    ( ts.store_title like '%$query_search%')  
                )";
        }

        //
        return Query::Multiple("
                    select 
                        t.*,
                        ts.company_name,
                        ts.store_title
                        {$str_customer_field}
                        from coupons t
                            left Join ViewStores ts On ts.id = t.store_id
                                Where t.app_id = ?
                                And t.active = 1
                                And ts.cat_company_type_id = ?
                                {$str_search}
                                {$str_where_id_used}
                ", [
                    $app_id,
                $category_id
        ]);
    }









    //
    public static function usp_ValidateGetCoupon($app_id, $coupon_coupon_id, $purchase_min_amount, $sub_total){
        //echo " app_id: $app_id, coupon_id: $coupon_id"; exit;


        //
        $results = array();



        $coupon_id = 0;
        $allow_reedem = 0;
        $discount_percent = 0.00;
        $discount_amount = 0.00;
        $error = "-------------------------------------------";



        //echo "test"; exit;


        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_GetCouponValues2(?,?,?,?,?,?,?,?)}";
            },
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "debug" => false,
            "params" => function() use(
                $app_id,
                $coupon_coupon_id,
                $sub_total,
                //
                &$coupon_id,
                &$allow_reedem,
                &$discount_percent,
                &$discount_amount,
                &$error
            ){
                return [
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array($coupon_coupon_id, SQLSRV_PARAM_IN),
                    array($sub_total, SQLSRV_PARAM_IN),
                    //
                    array(&$coupon_id, SQLSRV_PARAM_OUT),
                    array(&$allow_reedem, SQLSRV_PARAM_OUT),
                    array(&$discount_percent, SQLSRV_PARAM_OUT),
                    array(&$discount_amount, SQLSRV_PARAM_OUT),
                    array(&$error, SQLSRV_PARAM_OUT)
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $sp_res;
        }

        //
        $results['coupon_id'] = $coupon_id;
        $results['allow_reedem'] = $allow_reedem;
        $results['discount_percent'] = $discount_percent;
        $results['discount_amount'] = $discount_amount;

        // MANUALLY ADD ERRORS
        //
        if ($error === "ERR_COUPON_NOT_VALID"){
            $results['error'] = "Coupon not valid";
        }
        else if ($error === "ERR_COUPON_ALREADY_REEDEMED"){
            $results['error'] = "Coupon already reedemed, try removing it";
        }
        else if ($error === "ERR_COUPON_EXPIRED"){
            $results['error'] = "Coupon expired";
        }
        else if ($error === "ERR_PURCHASE_MIN_AMOUNT"){
            $results['error'] = "Coupon minimum purchase of \${$purchase_min_amount} usd";
        }


        //
        return $results;
    }




}
