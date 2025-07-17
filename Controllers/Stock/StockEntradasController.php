<?php
namespace Controllers\Stock;


//
use App\App;
use App\Departamentos\Departamentos;
use App\Paths;
use App\Products\Products;
use App\Products\ProductsFeatures;
use App\Products\ProductsStock;

use App\Ventas\Ventas;
use App\Ventas\VentasItems;
use App\Ventas\VentasStatus;
use Helpers\Helper;
use Controllers\BaseController;



//
class StockEntradasController extends BaseController
{









    //
    public static function configSearchClause($search_value){
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
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {

        //
        $table_name = "products_stock";
        //
        $order_info = Helper::getOrderInfo($request->getQueryParam("order", []), $request->getQueryParam("columns", []));
        $search_clause = self::configSearchClause( trim($request->getQueryParam("search")['value']), $request->getQueryParam("filter_tipo_ps_id"));
        //
        $order_field = $order_info['field'];
        $order_direction = $order_info['direction'];


        //------------ params
        //
        $product_id = $args['product_id'];
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
                    return "Select COUNT(*) total From {$table_name} t Where t.product_id = ? And t.proveedor_id Is Not Null {$search_clause}";
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
                                        prov.nombre as proveedor
                                        
                                        From {$table_name} t
                                      
                                      
                                            Left Join proveedores prov On prov.id = t.proveedor_id
                                      
                                                      
                                            Where t.product_id = ?
                                            And t.proveedor_id Is Not Null
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $product_id
                ),

                //
                "parseRows" => function(&$row){

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
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = Products::GetRecordById( $args['id'] );
        //var_dump($results); exit;
        //
        return $response->withJson($results, 200);
    }











    //
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $new_cantidad = Helper::safeVar($request->getParsedBody(), 'new_cantidad');
        $proveedor_id = Helper::safeVar($request->getParsedBody(), 'proveedor_id');
        $proveedor_monto = Helper::safeVar($request->getParsedBody(), 'proveedor_monto');
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');



        //
        if ( !($new_cantidad > 0) ){
            $results['error'] = "proporciona la cantidad del stock";
            return $response->withJson($results, 200);
        }
        //
        if ($proveedor_id){
            if ( !is_numeric($proveedor_monto) ){
                $results['error'] = "proporciona el monto a pagar";
                return $response->withJson($results, 200);
            }
        } else {
            $proveedor_monto = null;
        }


        //
        $proveedor_id = ( $proveedor_id > 0 ) ? $proveedor_id : null;
        $notes = ( $notes ) ? $notes : null;




        //
        $product_id = $args['product_id'];





        //
        $product_info = Products::GetRecordById($product_id);
        //var_dump($product_info); exit;
        if ( isset($product_info['error']) && $product_info['error'] ){
            $results['error'] = $product_info['error'];
            return $response->withJson($results, 200);
        }
        //
        $valor_anterior = ($product_info['cant_stock'] > 0) ? $product_info['cant_stock'] : 0;
        //echo $valor_anterior; exit;

        //
        $valor_nuevo = ( $valor_anterior + $new_cantidad );


        //
        $update_results = Products::UpdateStock(
            array(
                "cant_stock" => $valor_nuevo,
                //
                "app_id" => $app_id,
                "id" => $product_id
            )
        );
        //var_dump($update_results); exit;
        if ( isset($update_results['error']) && $update_results['error'] ){
            $results['error'] = $update_results['error'];
            return $response->withJson($update_results, 200);
        }

        //
        $update_results['hist_stock'] = ProductsStock::Create(array(
            "product_id" => $product_id,
            "tipo_salida_id" => null,
            "proveedor_id" => $proveedor_id,
            "proveedor_monto" => $proveedor_monto,
            "valor_anterior" => $valor_anterior,
            "valor_nuevo" => $valor_nuevo,
            "cant_ajuste" => $new_cantidad,
            "notes" => $notes,
            "user_id" => $user_id
        ));


        /*
        //
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_EDIT,
                "valor_nuevo" => "cliente folio: $cliente_id, nombre: $nombre, email: $email, telefono1: $telefono1, telefono2: $telefono2"
            ));
        */


        //
        return $response->withJson($update_results, 200);
    }









}