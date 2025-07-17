<?php
namespace Controllers\Config\ConfigClabe;

//
use App\Config\ConfigClabe\ConfigClabe;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class ConfigClabeController extends BaseController
{









    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/config/config_clabe/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }














    //
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = ConfigClabe::GetRecord($app_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }





















    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = array();


        //
        $nombre_cuenta = Helper::safeVar($request->getParsedBody(), 'nombre_cuenta');
        $clabe_interbancaria = Helper::safeVar($request->getParsedBody(), 'clabe_interbancaria');
        $notas = Helper::safeVar($request->getParsedBody(), 'notas');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$nombre_cuenta ){
            $results['error'] = "proporciona el nombre de la cuenta";
            return $response->withJson($results, 200);
        }
        if ( !$clabe_interbancaria ){
            $results['error'] = "proporciona la clabe interbancaria";
            return $response->withJson($results, 200);
        }
        $notas = ($notas) ? $notas : null;





        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertConfigClabe(?,?,?,?,?,?)}";
            },
            "params" => function() use($app_id, &$param_record_id, $nombre_cuenta, $clabe_interbancaria, $notas, $active){
                return [
                    //
                    array($nombre_cuenta, SQLSRV_PARAM_IN),
                    array($clabe_interbancaria, SQLSRV_PARAM_IN),
                    array($notas, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array($app_id, SQLSRV_PARAM_IN),
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            return $response->withJson($sp_res, 200);
        }
        //
        $update_results['id'] = $param_record_id;



        //
        return $response->withJson($update_results, 200);
    }













}