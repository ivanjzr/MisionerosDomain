<?php
namespace Controllers\Sucursales;

//
use App\Paths;

use Controllers\BaseController;
//
use App\App;
use Helpers\Helper;
//
use App\Sucursales\Sucursales;
use Helpers\Query;


//
class SucursalesController extends BaseController
{


    //
    public function Index($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        
        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;


        //
        return $this->container->php_view->render($response, 'admin/sucursales/index.phtml', [
            "App" => new App(null, $ses_data)
        ]);
    }


    //
    public function Edit($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        
        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;


        //
        return $this->container->php_view->render($response, 'admin/sucursales/edit.phtml', array(
            "App" => new App(null, $ses_data),
            'record_id' => $args['id']
        ));
    }




    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.name like '%$search_value%' ) Or 
                        ( t.email like '%$search_value%' ) Or
                        ( t.phone_number like '%$search_value%' ) 
                    )";
        }
        return $search_clause;
    }

    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "sucursales";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];



        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;




        //
        $results = Query::PaginateRecordsV2(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            $request->getQueryParam("draw"),
            [

                /*
                 * Count Statement
                 * */
                "count_stmt" => function() use ($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.account_id = ? {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($where_row_clause) use ($order_field, $order_direction, $table_name, $search_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.*,
                                        cit.nombre ciudad,
                                        est.nombre estado
                                      
                                        From {$table_name} t
                                
                                            Left Join sys_cat_ciudades cit On cit.id = t.city_id
                                            Left Join sys_cat_estados est On est.id = cit.estado_id
                                            
                                            Where t.account_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $account_id
                ),

                //
                "parseRows" => function(&$row){
                    //
                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }








    //
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];


        //
        $record_id = $args['id'];

        //
        $results = Sucursales::GetRecordById( $account_id, $record_id);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }





    //
    public function GetLocationInfo($request, $response, $args) {
        //var_dump($_SESSION); exit;
        //
        if ( isset($_SESSION['user_address']) && isset($_SESSION['user_address']['id']) ){
            //
            return $response->withJson($_SESSION['user_address'], 200);
        }
    }






    //
    public function PostSetLocationInfo($request, $response, $args) {
        //
        $app = $request->getAttribute("app");
        //dd($app); exit;
        $app_name = $app['app_name'];
        $account_id = $app['account_id'];


        //
        $place_address = Helper::safeVar($request->getParsedBody(), 'place_address');
        $place_lat = Helper::safeVar($request->getParsedBody(), 'place_lat');
        $place_lng = Helper::safeVar($request->getParsedBody(), 'place_lng');
        $place_city_code = Helper::safeVar($request->getParsedBody(), 'place_city_code');
        $place_state_code = Helper::safeVar($request->getParsedBody(), 'place_state_code');
        //echo "$place_address $place_lat $place_lng $place_city_code $place_state_code"; exit;




        // Obtenemos el city id almacenado para comparar posteriormente
        $previous_city_id = null;
        if ( isset($_SESSION['user_address']) && isset($_SESSION['user_address']['city_id']) ){
            $previous_city_id = $_SESSION['user_address']['city_id'];
        }
        //echo $previous_city_id; exit;




        //
        $_SESSION['user_address'] = array();


        //
        if ( $place_address && $place_city_code && $place_state_code){
            //echo "set val $place_address ";
            $_SESSION['user_address']['place_address'] = $place_address;
            $_SESSION['user_address']['place_city_code'] = $place_city_code;
            $_SESSION['user_address']['place_state_code'] = $place_state_code;
        }


        //
        $store_info = null;
        $prev_city_info = null;
        $rem_prods = false;

        //
        if ( $place_lat && $place_lng){
            //
            $_SESSION['user_address']['place_lat'] = $place_lat;
            $_SESSION['user_address']['place_lng'] = $place_lng;
            //
            $nearest_store_info = Query::Single("Select * From udfGetNearestStore(?, ?, ?, ?)", [$account_id, $place_lat, $place_lng, 0]);
            //dd($nearest_store_info); exit;
            if ( isset($nearest_store_info['id']) && $nearest_store_info['id'] ){
                //
                $_SESSION['user_address']['id'] = $nearest_store_info['id'];
                $_SESSION['user_address']['name'] = $nearest_store_info['nombre'];
                $_SESSION['user_address']['address'] = $nearest_store_info['address'] . ", " . $nearest_store_info['ciudad'] . ", " . $nearest_store_info['estado'];
                //
                $_SESSION['user_address']['city_id'] = $nearest_store_info['city_id'];
                $_SESSION['user_address']['state_id'] = $nearest_store_info['state_id'];
                //
                $_SESSION['user_address']['allow_pickup'] = $nearest_store_info['allow_pickup'];
                $_SESSION['user_address']['allow_delivery'] = $nearest_store_info['allow_delivery'];
                $_SESSION['user_address']['miles_from_store'] = $nearest_store_info['miles_from_store'];
                $_SESSION['user_address']['allow_pickup'] = $nearest_store_info['allow_pickup'];
                $_SESSION['user_address']['tax_percent'] = $nearest_store_info['tax_percent'];
                $_SESSION['user_address']['lat'] = $nearest_store_info['lat'];
                $_SESSION['user_address']['lng'] = $nearest_store_info['lng'];
                $_SESSION['user_address']['distance_meters'] =  number_format((float)$nearest_store_info['dist'], 2, '.', '');;

                /*
                 * CHECK IF PREV CITY ID IS THE SAME AS NEW
                 * si es cambio respecto al anterior mandamos el flag al cliente para que borre los productos
                 * */
                if ( $previous_city_id === $nearest_store_info['city_id'] ){
                    $prev_city_info = $nearest_store_info['city_id'] . " same as prev city id " . $previous_city_id;
                } else {
                    $prev_city_info = $nearest_store_info['city_id'] . " not the same as prev city id " . $previous_city_id;
                    $rem_prods = true;
                }

                //
                $store_info = $_SESSION['user_address'];
            }
        }


        //echo " $prev_city_info $rem_prods "; exit;
        //
        //dd($_SESSION); exit;
        return $response->withJson([
            "ok" => true,
            "prev_city_info" => $prev_city_info,
            "rem_prods" => $rem_prods,
            "store_info" => $store_info
        ], 200);
    }





    //
    public function PostGetCityStores($request, $response, $args) {
        //
        $app = $request->getAttribute("app");
        //dd($app); exit;
        $app_name = $app['app_name'];
        $account_id = $app['account_id'];


        //
        $lat = Helper::safeVar($request->getParsedBody(), 'lat');
        $lng = Helper::safeVar($request->getParsedBody(), 'lng');

        // nos traemos las sucursales con el city_id del nearest store
        if ( $lat && $lng ){
            //
            $nearest_store_info = Query::Single("Select * From udfGetNearestStore(?, ?, ?, ?)", [$account_id, $lat, $lng, 0]);
            //dd($nearest_store_info); exit;
            //
            if ( isset($nearest_store_info['id']) && $nearest_store_info['id'] ){
                //
                $sucursales = Sucursales::GetCityStores($account_id, $lat, $lng, $nearest_store_info['city_id']);
                return $response->withJson($sucursales, 200);
            }
        }


        //
        return $response->withJson([], 200);
    }



    //
    public function GetByLatLng($request, $response, $args) {


        //
        $app = $request->getAttribute("app");
        //var_dump($app); exit;
        $app_name = $app['app_name'];
        $account_id = $app['account_id'];
        //echo $account_id; exit;


        //
        $lat = $request->getQueryParam("lat");
        $lng = $request->getQueryParam("lng");
        $limit_to_city_id = $request->getQueryParam("cityid");
        //echo "$lat $lng $city_id"; exit;




        //
        $results = Query::Multiple("
                Select
                    
                    k.*
                
                
                       From (SELECT
                        
                        t.*,
                        cit.nombre ciudad,
                        geography::Point(?, ?, 4326).STDistance(geography::Point(t.lat, t.lng, 4326)) as dist
                      
                          FROM sucursales t
                       
                            Left Join sys_cat_ciudades cit On cit.id = t.city_id
                                
                            Where t.account_id = ?
                            And t.city_id = ?
                            And t.active = 1
                            -- And geography::Point(?, ?, 4326).STDistance(geography::Point(t.lat, t.lng, 4326)) <= ? 
                        ) k
                        Order By k.dist Asc
                        
                ", [
                $lat,
                $lng,
                $account_id,
                $limit_to_city_id
            ]
        );
        //var_dump($results); exit;
        //
        return $response->withJson($results, 200);
    }






    //
    public function GetAll($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];


        //
        $results = Sucursales::GetAll($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }






    //
    public function GetSearch($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("app");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];

        //
        $search_text = $request->getQueryParam("q");

        //
        $res = Query::Multiple("
				SELECT
                  
                    t.*,
                    cit.nombre ciudad,
                    cit.estado_id,
                    est.nombre estado
                        
                        FROM sucursales t
                  
                            Left Join sys_cat_ciudades cit On cit.id = t.city_id
                            Left Join sys_cat_estados est On est.id = cit.estado_id
                  
                        
                           Where t.account_id = ?
                            And (
                                t.name like ? Or t.address like ?
                            )
			", [
            $account_id,
            "%".$search_text."%",
            "%".$search_text."%"
        ]);
        //var_dump($res); exit;

        //
        return $response->withJson($res, 200);
    }



    




    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];



        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;



        //
        $results = array();


        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $city_id = Helper::safeVar($request->getParsedBody(), 'ciudad_id');
        $address = Helper::safeVar($request->getParsedBody(), 'address');
        //
        $phone_cc = Helper::safeVar($request->getParsedBody(), 'phone_cc');
        $phone_number = Helper::safeVar($request->getParsedBody(), 'phone_number');
        //
        $email = Helper::safeVar($request->getParsedBody(), 'email');
        //
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;



        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$city_id ){
            $results['error'] = "proporciona la ciudad";
            return $response->withJson($results, 200);
        }
        if ( !$address ){
            $results['error'] = "proporciona la direccion";
            return $response->withJson($results, 200);
        }
        if ( !$phone_cc ){
            $results['error'] = "proporciona la clave del telefono";
            return $response->withJson($results, 200);
        }
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
            return $response->withJson($results, 200);
        }
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "proporciona el email";
            return $response->withJson($results, 200);
        }







        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "debug" => false,
            "stmt" => "
                   Insert Into sucursales
                  ( 
                    account_id, name, city_id, address, 
                    phone_cc, phone_number, email, active, 
                    datetime_created 
                  )
                  Values
                  ( 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?,
                    GETDATE() 
                  )
                  ;SELECT SCOPE_IDENTITY()   
                ",
            "params" => [
                $account_id,
                $nombre,
                $city_id,
                $address,
                $phone_cc,
                $phone_number,
                $email,
                $active
            ],
            "parse" => function($insert_id, &$query_results){
                $query_results['id'] = (int)$insert_id;
            }
        ]);

        //
        if (isset($insert_results['id']) && $insert_results['id']){
            //
            $new_sucursal_id = $insert_results['id'];
            $pos_name = "Caja Principal";
            $pos_active = 1;
            //
            $insert_pos_results = Query::DoTask([
                "task" => "add",
                "debug" => true,
                "stmt" => "
                    Insert Into pos
                    ( app_id, sucursal_id, name, active, datetime_created )
                    Values
                    ( ?, ?, ?, ?, GETDATE() )
                    ;SELECT SCOPE_IDENTITY()
                    ",
                "params" => [
                    $app_id, $new_sucursal_id, $pos_name, $pos_active
                ],
                "parse" => function($insert_id, &$query_results){
                    $query_results['id'] = (int)$insert_id;
                }
            ]);
            //dd($insert_pos_results); exit;
        }


        //
        return $response->withJson($insert_results, 200);
    }








    //
    public function UpdateRecord($request, $response, $args) {



        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];



        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;




        //
        $results = array();



        //
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        $city_id = Helper::safeVar($request->getParsedBody(), 'ciudad_id');
        $address = Helper::safeVar($request->getParsedBody(), 'address');
        //
        $phone_cc = Helper::safeVar($request->getParsedBody(), 'phone_cc');
        $phone_number = Helper::safeVar($request->getParsedBody(), 'phone_number');
        //
        $email = Helper::safeVar($request->getParsedBody(), 'email');
        //
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;



        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$city_id ){
            $results['error'] = "proporciona la ciudad";
            return $response->withJson($results, 200);
        }
        if ( !$address ){
            $results['error'] = "proporciona la direccion";
            return $response->withJson($results, 200);
        }
        if ( !$phone_cc ){
            $results['error'] = "proporciona la clave del telefono";
            return $response->withJson($results, 200);
        }
        if ( !$phone_number ){
            $results['error'] = "proporciona el numero de telefono";
            return $response->withJson($results, 200);
        }
        if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $results['error'] = "proporciona el email";
            return $response->withJson($results, 200);
        }



        //
        $record_id = $args['id'];


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    sucursales
                  
                  Set 
                    --
                    name = ?,
                    city_id = ?,
                    address = ?,
                    phone_cc = ?,
                    phone_number = ?,
                    email = ?,                      
                    active = ?
                    
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT   
                ",
            "params" => [
                $nombre,
                $city_id,
                $address,
                $phone_cc,
                //
                $phone_number,
                $email,
                //
                $active,
                //
                $account_id,
                $record_id
            ],
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;



        //
        return $response->withJson($update_results, 200);
    }










    //
    public function UpdateCFDIData($request, $response, $args) {



        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $nombre_razon_social = Helper::safeVar($request->getParsedBody(), 'nombre_razon_social');
        $rfc = Helper::safeVar($request->getParsedBody(), 'rfc');


        //
        if ( !$nombre_razon_social ){
            $results['error'] = "proporciona el nombre de la razon social";
            return $response->withJson($results, 200);
        }
        if ( !$rfc ){
            $results['error'] = "proporciona el rfc";
            return $response->withJson($results, 200);
        }


        //
        $record_id = $args['id'];


        //
        $update_results = Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    sucursales
                  
                  Set 
                    --
                    nombre_razon_social = ?,
                    rfc = ?
                    
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT   
                ",
            "params" => [
                $nombre_razon_social,
                $rfc,
                //
                $account_id,
                $record_id
            ],
            "parse" => function($updated_rows, &$query_results) use($record_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
            }
        ]);
        //var_dump($update_results); exit;



        //
        return $response->withJson($update_results, 200);
    }












    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];


        //
        $auth_check = Helper::requireAdmin($ses_data, $response);
        if ($auth_check) return $auth_check;



        

        //
        $results = array();


        //
        $id = Helper::safeVar($request->getParsedBody(), 'id');
        //
        if ( !$id ){
            $results['error'] = "proporciona el id";
            return $response->withJson($results, 200);
        }



        //
        $results = Query::DoTask([
            "task" => "delete",
            "stmt" => "Delete FROM sucursales Where account_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $account_id,
                $id
            ],
            "exeptions_msgs" => [
                "FK_empleados_sucursales_sucursales" => "No se puede eliminar la sucursal por que esta asociada a un usuario"
            ],
            "parse" => function($updated_rows, &$query_results) use($id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$id;
            }
        ]);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }









}
