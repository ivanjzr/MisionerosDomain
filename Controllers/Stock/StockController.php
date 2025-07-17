<?php
namespace Controllers\Stock;


//
use App\App;
use App\Empleados\EmpleadosSucursales;
use App\Paths;
use App\Products\Products;
use App\Products\ProductsFeatures;
use App\Products\ProductsStock;

use App\Ventas\Ventas;
use App\Ventas\VentasItems;
use App\Ventas\VentasStatus;
use Helpers\Helper;
use Controllers\BaseController;
use Helpers\Query;


//
class StockController extends BaseController
{








    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/stock/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }
    //
    public function ViewEntradas($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/stock/entries.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }
    //
    public function ViewSalidas($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/stock/outputs.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }







    //
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = Products::GetRecordById( $args['id'] );
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }







    //
    public static function configSearchClause($search_value, $filter_tipo_ps_id){
        //
        $search_clause = "";

        //
        if ( $search_value ){
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.description like '%$search_value%' )
                    )";
        }
        //
        if ($filter_tipo_ps_id){
            $search_clause .= "And t.tipo_producto_servicio_id = $filter_tipo_ps_id";
        }

        //
        return $search_clause;
    }

    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "products";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']), $request->getQueryParam("filter_tipo_ps_id"));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


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
                    return "Select COUNT(*) total From {$table_name} t Where 1=1 {$search_clause}";
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
                                        last_entrada.proveedor,
                                        last_entrada.entrada_cantidad,
                                        -- 
                                        last_salida.tipo_salida,
                                        last_salida.salida_cantidad
                                      
                                        From v_products t
                                      
                                            -- get last entrada
                                            OUTER APPLY (
                                                Select
                                                
                                                    Top 1
                                                    prov.nombre proveedor,
                                                    k.cant_ajuste entrada_cantidad
                                                    
                                                    --
                                                    From products_stock k
                                
                                                        --
                                                        Left Join proveedores prov On prov.id = k.proveedor_id
                                                        
                                                        --
                                                        Where k.tipo_salida_id is Null
                                                        --
                                                        Order By k.id Desc
                                
                                                ) last_entrada
                
                                      
                                                -- get last salida
                                                OUTER APPLY (
                                                    Select
                                                    
                                                        Top 1
                                                        inv.tipo tipo_salida,
                                                        k.cant_ajuste salida_cantidad
                                                        
                                                        --
                                                        From products_stock k
                                    
                                                            --
                                                            Left Join sys_inventario_tipos_salidas inv On inv.id = k.tipo_salida_id
                                                            
                                                            --
                                                            Where k.tipo_salida_id is Not Null
                                                            --
                                                            Order By k.id Desc
                                    
                                                    ) last_salida
                                                      
                                                      
                                            Where 1=1
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id
                ),

                //
                "parseRows" => function(&$row){




                    //
                    $row['features'] = ProductsFeatures::GetAll($row['id']);




                    // SET PROD IMAGES URL
                    $files_path = Paths::$path_products . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_products . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_products . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                        }
                    }




                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }








    //
    public function PostUpdateInfo($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $product_id = $args['id'];


        //
        $results = array();



        //
        $aplica_stock = Helper::safeVar($request->getParsedBody(), 'aplica_stock') ? 1 : 0;
        $cant_min_notify = Helper::safeVar($request->getParsedBody(), 'cant_min_notify');


        //
        $cant_min_notify = (is_numeric($cant_min_notify)) ? $cant_min_notify : 0;




        //
        $update_results = Products::UpdateInfo($aplica_stock, $cant_min_notify, $app_id, $product_id);

        //
        return $response->withJson($update_results, 200);
    }






















}