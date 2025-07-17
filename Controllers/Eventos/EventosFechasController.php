<?php
namespace Controllers\Eventos;

//
use App\Eventos\EventosFechas;

use Controllers\BaseController;
use Helpers\Helper;
use Helpers\Query;


//
class EventosFechasController extends BaseController
{




    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.description like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $columns = $request->getQueryParam("columns", []);
        $order = $request->getQueryParam("order", []);

        //
        $evento_id = $args['evento_id'];

        //
        $results = Query::PaginateRecords(
            $request->getQueryParam("start"),
            $request->getQueryParam("length"),
            Helper::getOrderInfo($order, $columns),
            self::configSearchClause(trim($request->getQueryParam("search")['value'])),
            $request->getQueryParam("draw"),
            [
                //
                "table_name" => "eventos_fechas",

                /*
                 * Count Statement
                 * */
                "count_stmt" => function($table_name, $search_clause){
                    //
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? And t.evento_id = ? {$search_clause}";
                },

                /*
                 * Records Statement
                 * */
                "records_stmt" => function($table_name, $order_field, $order_direction, $search_clause, $where_row_clause){
                    //
                    return "Select
                                  *
                                From
                                    (Select
                                      *
                                      ,ROW_NUMBER() OVER (ORDER BY {$order_field} {$order_direction}) as row
                                    From
                                      (Select
                                      
                                        t.*
                                        
                                        From {$table_name} t
                                        
                                        
                                            Where t.app_id = ? 
                                            And t.evento_id = ?
                                            
                                            {$search_clause}
                                      ) t
                                    ) t
                                WHERE 1=1
                                {$where_row_clause}";
                },

                //
                "params" => array(
                    $app_id,
                    $evento_id
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
    public function AddRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];


        //
        $results = array();



        //
        $event_date = Helper::safeVar($request->getParsedBody(), 'event_date');
        $description = Helper::safeVar($request->getParsedBody(), 'description');


        //
        if ( !(Helper::is_valid_date($event_date, "Y-m-d", "Y-m-d") > 0) ){
            $results['error'] = "proporciona la fecha";
            return $response->withJson($results, 200);
        }
        //echo $event_date; exit;
        $description = ($description) ? $description : null;



        //
        $evento_id = $args['evento_id'];





        //
        $update_results = EventosFechas::AddRecord($app_id, $evento_id, $event_date, $description, $user_id);


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











    //
    public function GetAll($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];


        //
        $results = EventosFechas::GetAll($app_id, $args['evento_id']);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }










    //
    public function DeleteRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $user_id = $ses_data['id'];
        $app_id = $ses_data['app_id'];



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
        $results = EventosFechas::Remove($app_id, $args['evento_id'], $id);
        //var_dump($results); exit;



        /*
        HistorialCambios::Create(
            array(
                "user_id" => $usd['id'],
                "seccion_id" => SECTION_ID_CLIENTES,
                "accion_id" => ACTION_ID_DEL,
                "valor_nuevo" => "cliente folio: $id"
            ));
        */



        //
        return $response->withJson($results, 200);
    }











}