<?php
namespace Controllers\Proveedores;

//
use App\Paths;
use App\Proveedores\Proveedores;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\ImagesHandler;
use Helpers\Query;


//
class ProveedoresController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/suppliers/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }








    //
    public static function configSearchClause($search_value){
        //
        $search_clause = "";
        //
        if ( $search_value ){
            //
            $search_clause .= " And (
                        ( t.nombre like '%$search_value%' ) Or 
                        ( t.phone_cc like '%$search_value%' ) Or
                        ( t.address like '%$search_value%' )
                    )";
        }
        return $search_clause;
    }
    //
    public function PaginateRecords($request, $response, $args) {


        //
        $table_name = "proveedores";
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
                    return "Select COUNT(*) total From {$table_name} t Where t.app_id = ? {$search_clause}";
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
                                        cit.estado_id,
                                        cit.nombre ciudad,
                                        est.nombre estado
                                      
                                        From {$table_name} t
                                      
                                            Left Join sys_cat_ciudades cit On cit.id = t.city_id
                                            Left Join sys_cat_estados est On est.id = cit.estado_id
                                      
                                            Where t.app_id = ?
                                            
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
                    $files_path = Paths::$path_proveedores . DS . $row['id'];
                    $main_files_path = $files_path .DS . "main";
                    //
                    $row["orig_img_url"] = "";
                    $row["thumb_img_url"] = "";
                    //
                    if ( isset($row['img_ext']) && ($img_ext = $row['img_ext']) ){
                        //
                        $img_path = $main_files_path.DS."orig." . $img_ext;
                        if ( is_file($img_path) ){
                            $row["orig_img_url"] = Paths::$url_proveedores . UD . $row['id'] . UD . "main" . UD . "orig." . $img_ext;
                        }
                        //
                        $thumb_img_path = $main_files_path.DS."thumb." . $img_ext;
                        if ( is_file($thumb_img_path) ){
                            $row["thumb_img_url"] = Paths::$url_proveedores . UD . $row['id'] . UD . "main" . UD . "thumb." . $img_ext;
                        }
                    }


                }

            ]
        );
        //var_dump($results); exit;
        return $response->withJson($results, 200);
    }


















    //
    public function GetAll($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = Proveedores::GetAll($app_id);
        //var_dump($results); exit;

        //
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
        $results = Proveedores::GetRecordById( $app_id, $args['id'] );
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
        $uploadedFiles = $request->getUploadedFiles();
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        //
        $phone_cc = Helper::safeVar($request->getParsedBody(), 'phone_cc');
        $phone = Helper::safeVar($request->getParsedBody(), 'phone');
        $city_id = Helper::safeVar($request->getParsedBody(), 'city_id');
        $address = Helper::safeVar($request->getParsedBody(), 'address');
        $rfc = Helper::safeVar($request->getParsedBody(), 'rfc');
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;


        //
        $img_section = false;
        $file_extension = null;
        // get imagen large
        if ( isset($uploadedFiles['img_section']) &&
            ( $uploadedFile = $uploadedFiles['img_section'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $img_section = $uploadedFile;
            //
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
        }
        //var_dump($img_section); exit;







        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$phone_cc ){
            $results['error'] = "proporciona la clave del pais";
            return $response->withJson($results, 200);
        }
        if ( !$phone ){
            $results['error'] = "proporciona el telefono";
            return $response->withJson($results, 200);
        }
        if ( !($city_id > 0) ){
            $results['error'] = "proporciona la ciudad";
            return $response->withJson($results, 200);
        }

        //
        $address = ($address) ? $address : null;
        $notes = ($notes) ? $notes : null;
        $rfc = ($rfc) ? $rfc : null;








        //
        $insert_results = Query::DoTask([
            "task" => "add",
            "stmt" => "
                   Insert Into proveedores
                  ( app_id, nombre, phone_cc, phone, city_id, 
                    address, rfc, notes, active, 
                    datetime_created )
                  Values
                  ( ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    GETDATE() )
                  ;SELECT SCOPE_IDENTITY()
                ",
            "params" => [
                $app_id,
                $nombre,
                $phone_cc,
                $phone,
                $city_id,
                $address,
                $rfc,
                $notes,
                $active
            ],
            "parse" => function($insert_id, &$query_results) use ($img_section, $file_extension){
                //
                $query_results['id'] = (int)$insert_id;
                //
                if ( $img_section && $file_extension ) {
                    $results['update_img_results'] = Proveedores::updateProveedorLogo($img_section, $file_extension, $query_results['id']);
                }
            }
        ]);



        //
        return $response->withJson($insert_results, 200);
    }















    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


        //
        $results = array();



        //
        $uploadedFiles = $request->getUploadedFiles();
        $nombre = Helper::safeVar($request->getParsedBody(), 'nombre');
        //
        $phone_cc = Helper::safeVar($request->getParsedBody(), 'phone_cc');
        $phone = Helper::safeVar($request->getParsedBody(), 'phone');
        $city_id = Helper::safeVar($request->getParsedBody(), 'city_id');
        $address = Helper::safeVar($request->getParsedBody(), 'address');
        $rfc = Helper::safeVar($request->getParsedBody(), 'rfc');
        $notes = Helper::safeVar($request->getParsedBody(), 'notes');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;


        //
        $img_section = false;
        $file_extension = null;
        // get imagen large
        if ( isset($uploadedFiles['img_section']) &&
            ( $uploadedFile = $uploadedFiles['img_section'] ) &&
            ( $uploadedFile->getError() === UPLOAD_ERR_OK ) ) {
            //
            $img_section = $uploadedFile;
            //
            $file_extension = pathinfo($img_section->getClientFilename(), PATHINFO_EXTENSION);
            $file_extension = strtolower($file_extension);
        }
        //var_dump($img_section); exit;







        //
        if ( !$nombre ){
            $results['error'] = "proporciona el nombre";
            return $response->withJson($results, 200);
        }
        if ( !$phone_cc ){
            $results['error'] = "proporciona la clave del pais";
            return $response->withJson($results, 200);
        }
        if ( !$phone ){
            $results['error'] = "proporciona el telefono";
            return $response->withJson($results, 200);
        }
        if ( !($city_id > 0) ){
            $results['error'] = "proporciona la ciudad";
            return $response->withJson($results, 200);
        }

        //
        $notes = ($notes) ? $notes : null;
        $address = ($address) ? $address : null;
        $rfc = ($rfc) ? $rfc : null;








        //
        $record_id = $args['id'];





        //
        $update_results = Query::DoTask([
            "task" => "update",
            "stmt" => "
                   Update 
                    proveedores
                  
                  Set
                    nombre = ?,
                    phone_cc = ?,
                    phone = ?,
                    city_id = ?,
                      
                    address = ?,
                    rfc = ?,
                    notes = ?,
                    active = ?
                  
                  Where app_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $nombre,
                $phone_cc,
                $phone,
                $city_id,
                $address,
                $rfc,
                $notes,
                $active,
                //
                $app_id,
                $record_id
            ],
            "parse" => function($updated_rows, &$query_results) use($record_id, $img_section, $file_extension){
                //
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$record_id;
                //
                if ( $img_section && $file_extension ) {
                    $results['update_img_results'] = Proveedores::updateProveedorLogo($img_section, $file_extension, $query_results['id']);
                }
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
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];


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
            "stmt" => "Delete FROM proveedores Where app_id = ? And id = ?;SELECT @@ROWCOUNT",
            "params" => [
                $app_id,
                $id
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