<?php
namespace App\Coupons;

//
use App\Stores\Stores;
use Helpers\Helper;
use Helpers\Query;


//
class Coupons
{



    public static function FindCoupons($num_records, $lat, $lng, $distance_km, $str_prev_ids, $customer_id, $category_id, $query_search){



        /*
         * Ya no incluimos cupones de esa empresa
         * */
        $and_limit_to_prev_ids = "";
        if ( $str_prev_ids ){
            $and_limit_to_prev_ids = " 
                And t.store_id Not In ($str_prev_ids)
            ";
        }
        //echo $and_limit_to_prev_ids; exit;


        /*
        *
        * Aqui solo mostramos los campos del cliente para modo Debug
        * pero debemos de quitar la clausula "$and_limit_to_customer_id" para pode ver las fechas de cut y reedem
        * */
        $str_customer_field = "";
        $and_limit_to_customer_id = "";
        if ( is_numeric($customer_id) ){
            /*
            $str_customer_field = "
                ,(select n.datetime_created from customers_coupons n where n.customer_id = {$customer_id} And n.coupon_id = t.id ) as datetime_cut
                ,(select p.datetime_reedemed from customers_coupons p where p.customer_id = {$customer_id} And p.coupon_id = t.id ) as datetime_reedemed
            ";
            */
            //
            $and_limit_to_customer_id = " And t.id Not In ( select c.coupon_id from customers_coupons c where c.customer_id = {$customer_id} ) ";
        }
        //echo $and_limit_to_customer_id; exit;




        /*
         * Limitamos a la busqueda
         * */
        //
        $and_limit_to_search = "";
        //
        if ($query_search){
            $and_limit_to_search .= " And (
                    ( t.company_name like '%$query_search%') Or
                    ( t.store_title like '%$query_search%') Or
                    ( t.description like '%$query_search%')   
                )";
        }
        //$and_limit_to_search = " And 1=2 ";
        //echo $and_limit_to_search; exit;




        /*
       * Limitamos a categoria
       * */
        $and_limit_to_category_id = "";
        if ( is_numeric($category_id) && !$query_search ){
            $and_limit_to_category_id = " And t.cat_company_type_id = {$category_id} ";
        }
        //echo $and_limit_to_category_id; exit;





        /*
         // Pruebas
        $distance_meters = ( $distance_km * 10000 );
        // 15279 es menor a
        if ( 15279.074153238711 <= $distance_meters ){
            //echo "15279 es menor esta dentro del rango Ok $distance_meters";
        } else {
            //echo "15279 es mayor NO esta dentro del rango $distance_meters";
        }
        */
        $distance_meters = ( $distance_km * 10000 );



        //
        $stmt = "
                Select
                    Top {$num_records}
                     k.*
                     From (
                        SELECT         
                                             
                            t.*,
                            ROW_NUMBER() OVER (PARTITION BY t.store_id ORDER BY NEWID()) AS rn
                            {$str_customer_field}
                        
                                FROM ViewCoupons t 
                                    
                                    --
                                    Where geography::Point(?, ?, 4326).STDistance(geography::Point(t.lat, t.lng, 4326)) <= ?
                                    
                                    -- mostramos cupones activos, no finalizados y con tiendas que tengan suscripcion activa          
                                    And t.active = 1
                                    --And t.is_finalized = 0
                                    --And t.has_valid_subs = 1
                                    
                                    -- limitamos a otros 
                                    {$and_limit_to_prev_ids}
                                    {$and_limit_to_customer_id}
                                    {$and_limit_to_category_id}
                                    {$and_limit_to_search}
                        ) k
                
                -- LIMITA A 1 REGISTRO POR NEGOCIO
                -- Where k.rn = 1
                
                -- volvemos a sortear para que no salgan siempre en el mismo orden
                Order By NEWID()
            ";
        //echo $stmt; exit;
        //
        return Query::Multiple($stmt,[
            $lat,
            $lng,
            $distance_meters
        ], function(&$row){
            //Helper::printFull($row); exit;
            //
            if ( isset($row['img_ext']) && $row['img_ext'] ){
                $row['biz_logo'] = Stores::getStoreLogo($row['store_id'], $row['img_ext']);
            }
        });
    }





    //
    public static function GetCouponById($coupon_id, $customer_id = null){

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
        return Query::Single("
                   SELECT
                    
                    t.*
                    {$str_customer_field}
                    
                      FROM ViewCoupons t
                   
                        Where t.id = ?
                        {$str_where_id_used}

                ", [
            $coupon_id
        ], function(&$row){

            //
            if ( $biz_logo = Stores::getStoreLogo($row['store_id'], $row['img_ext'])){
                $row['biz_logo'] = $biz_logo;
                unset($row['img_ext']);
            }

        });
    }




    //
    public static function GetAllAvailable(){
        //
        return Query::All([
            "stmt" => function(){
                return "
				    SELECT
                  
                    t.*
                    
                      FROM coupons t
                      
                        Where t.active = 1
                        And t.fecha_hora_fin >= GETDATE()
			";
            },
            "params" => [
            ],
            "parse" => function(){

            }
        ]);
    }







    //
    public static function GetAll($qty = 10){
        //
        return Query::All([
            "stmt" => function(){
                return "
				    SELECT
                  
				    Top {$qty}
                    t.*
                    
                      FROM coupons t
                      
                        Where t.active = 1
			";
            },
            "params" => [
                //
            ],
            "parse" => function(){

            }
        ]);
    }










    //
    public static function GetRecordById($id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                    
                      FROM coupons t                    
                        
                        Where t.id = ?
                ";
            },
            "params" => [
                $id
            ],
            "parse" => function(&$row){
                //
            }
        ]);
    }







}
