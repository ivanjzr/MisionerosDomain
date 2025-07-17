<?php
namespace Controllers\Sys;

//
use Controllers\BaseController;
//
use Helpers\Query;


//
class SysController extends BaseController
{





    public function GetAppsTypes($request, $response, $args) {
        return $response->withJson(Query::Multiple("select * from accounts_apps", []), 200);
    }



    //
    public function GetTiposProductos($request, $response, $args) {

        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_tipo_producto_servicio t Where t.active = 1";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);

        //
        return $response->withJson($results, 200);
    }



    //
    public function GetListAppointmentsStatus($request, $response, $args) {
        //
        //$marca_id = $request->getQueryParam("marca_id");
        $results = Query::Multiple("SELECT t.* FROM sys_cat_appointments_status t where t.active = 1 ", []);
        //
        return $response->withJson($results, 200);
    }




    //
    public function GetPOSPaymentsMethods($request, $response, $args) {
        //
        //$marca_id = $request->getQueryParam("marca_id");
        $results = Query::Multiple("SELECT t.* FROM sys_payment_types t where t.is_pos = 1 ", []);
        //
        return $response->withJson($results, 200);
    }





    //
    public function GetVentasEstatus($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;
        $account_id = $ses_data['account_id'];


        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_sale_status t";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);

        //
        return $response->withJson($results, 200);
    }



    //
    public function GetTiposProductosServicios($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($usd_admin); exit;
        $account_id = $ses_data['account_id'];



        //
        $str_uses_features = "";
        if ($request->getQueryParam("uses_features")){
            $str_uses_features = " And t.uses_features = 1";
        }
        //
        $results = Query::All([
            "stmt" => function() use($str_uses_features){
                return "
                    SELECT
                        t.*
                            FROM sys_tipo_producto_servicio t
    
                            Where t.active = 1
                            $str_uses_features
			";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);


        //
        return $response->withJson($results, 200);
    }





    //
    public function GetUnidadesDeMedida($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;
        $account_id = $ses_data['account_id'];


        //
        $results = Query::All([
            "stmt" => function(){
                return "
                    SELECT
                        t.*
                            FROM sys_unidades_medida t
			";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);

        //
        return $response->withJson($results, 200);
    }















    //
    public function GetTitulos($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;
        $account_id = $ses_data['account_id'];


        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_titulos t order by orden asc";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);

        //
        return $response->withJson($results, 200);
    }






    //
    public function GetMaquetasTiposCorreos($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;
        $account_id = $ses_data['account_id'];


        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_maquetas t Where t.display_in_admin = 1";
            },
            "params" => [
                //
            ],
            "parse" => function(){
                //
            }
        ]);

        //
        return $response->withJson($results, 200);
    }





    //
    public function GetMaquetasTiposDocumentos($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;
        $account_id = $ses_data['account_id'];


        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_tipos_documentos t";
            },
            "params" => [
                //
            ],
            "parse" => function(){
                //
            }
        ]);

        //
        return $response->withJson($results, 200);
    }





    //
    public function GetPaises($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;
        
        // default id
        $default_id = $request->getQueryParam("id");
        $str_where_id = "";
        if ($default_id){
            $str_where_id = " And id = {$default_id}";
        }

        //
        $results = Query::Multiple("SELECT t.* FROM sys_cat_paises t where t.display_in_fp = 1 {$str_where_id}");
        //
        return $response->withJson($results, 200);
    }



    //
    public function GetRelativesList($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;
        //
        $results = Query::Multiple("SELECT t.* FROM sys_cat_relatives_types t where t.active = 1 ");
        //
        return $response->withJson($results, 200);
    }









    
    //
    public function GetCategoriesList($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($_SESSION); exit;


        //
        $results = Query::Multiple("
            SELECT t.* FROM sys_cat_companies_types t where t.active = 1 
            ",[
        ], function(&$row){
            //
            $row['sub_types'] = Query::Multiple("SELECT t.sub_type FROM sys_cat_companies_subtypes t where t.company_type_id = ? And t.active = 1", [$row['id']]);
        });

        //
        return $response->withJson($results, 200);
    }







    




    //
    public function GetPeriodicidad($request, $response, $args) {

        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_periodicidad t where t.active = 1";
            }
        ]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }








    //
    public function GetEstados($request, $response, $args) {
        //
        $pais_id_mexico = 467;
        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_cat_estados t Where pais_id = ?";
            },
            "params" => [
                $pais_id_mexico
            ],
            "parse" => function(){

            }
        ]);
        //
        return $response->withJson($results, 200);
    }






    //
    public function GetCities($request, $response, $args) {


        //
        $estado_id = $args['estado_id'];
        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_cat_ciudades t Where estado_id = ?";
            },
            "params" => [
                $estado_id
            ],
            "parse" => function(){

            }
        ]);

        //
        return $response->withJson($results, 200);
    }




    //
    public function GetCompaniesTypes($request, $response, $args) {
        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_cat_companies_types t Where t.active = 1";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);
        //
        return $response->withJson($results, 200);
    }



    //
    public function GetParentModels($request, $response, $args) {

        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM sys_admin_secciones t Where t.parent_id Is Null";
            }
        ]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }



}
