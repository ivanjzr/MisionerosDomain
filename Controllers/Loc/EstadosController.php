<?php
namespace Controllers\Loc;

//
use App\Databases\MySqli;
use App\Databases\MySqliHelper;
use App\Paths;


use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class EstadosController extends BaseController
{





    //
    public function GetAll($request, $response, $args) {
        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $account_id = $ses_data['account_id'];
        $user_id = $ses_data['id'];
        //
        $app_id = Helper::getAppByAccountId($account_id);



        //
        $pais_id_us = 1;
        $results = Query::Multiple("select t.* from cat_estados t where t.pais_id = ? And active = 1 order by t.nombre Asc", [$pais_id_us]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }











}