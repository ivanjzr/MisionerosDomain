<?php
namespace App\Sucursales;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\EncryptHelper;
use Helpers\Helper;
use Helpers\Query;


//
class Sucursales
{






    //
    public static function GetAll($account_id, $city_id = null){
        //
        $str_where_city_id = "";
        if ( $city_id > 0 ){
            $str_where_city_id = " And t.city_id = {$city_id} ";
        }
        //
        return Query::All([
            "stmt" => function() use($str_where_city_id){
                return "
				SELECT
                  
                    t.*,
                    cit.nombre ciudad,
                    cit.estado_id,
                    est.nombre estado
                        
                        FROM sucursales t
                  
                            Left Join sys_cat_ciudades cit On cit.id = t.city_id
                            Left Join sys_cat_estados est On est.id = cit.estado_id
                        
                           Where t.account_id = ?
                           {$str_where_city_id}
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
    public static function GetCityStores($account_id, $lat, $lng, $city_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
                    Select        
                          k.*
                        From
                          (Select
                          
                            t.id,
                            t.name,
                            t.address,
                            t.lat,
                            t.lng,
                            cit.allow_pickup,
                            cit.allow_delivery,
                            cit.miles_from_store,
                            geography::Point(?, ?, 4326).STDistance(geography::Point(t.lat, t.lng, 4326)) as distance_meters
                            -- 
                            From sucursales t   
                            
                                Left Join sys_cat_ciudades cit On cit.id = t.city_id
                            
                            
                                Where t.account_id = ?
                                And t.city_id = ?
                          ) k
                        Order By k.distance_meters Asc
			";
            },
            "params" => [
                $lat,
                $lng,
                $account_id,
                $city_id
            ],
            "parse" => function(){
            }
        ]);
    }


    //
    public static function GetAllKitchens($account_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                  
                    t.*,
                    cit.nombre ciudad,
                    cit.estado_id,
                    est.nombre estado
                        
                        FROM sucursales t
                  
                            Left Join sys_cat_ciudades cit On cit.id = t.city_id
                            Left Join sys_cat_estados est On est.id = cit.estado_id
                  
                        
                           Where t.account_id = ?
                           And t.is_kitchen = 1
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
    public static function GetForSiteLocations($account_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
                  
                    t.*,
                    cit.nombre ciudad,
                    cit.estado_id,
                    est.nombre estado
                        
                        FROM sucursales t
                  
                            Left Join sys_cat_ciudades cit On cit.id = t.city_id
                            Left Join sys_cat_estados est On est.id = cit.estado_id
                  
                        
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
    public static function GetRecordById($account_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*,
                    cit.nombre as ciudad_nombre,
                    cit.estado_id,
                    est.nombre as estado_nombre
                  
                      FROM sucursales t
                      
                        Left Join sys_cat_ciudades cit On cit.id = t.city_id
                        Left Join sys_cat_estados est On est.id = cit.estado_id
                  
                  
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









}
