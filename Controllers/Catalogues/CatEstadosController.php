<?php
namespace Controllers\Catalogues;

//
use App\Catalogues\CatTags;
use App\Paths;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;


//
class CatEstadosController extends BaseController
{











    //
    public function GetAll($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];

        //
        $pais_id_us = 2;
        //
        $results = Query::Multiple("select t.*, tp.nombre as pais_nombre, tp.abreviacion as pais_abreviacion from cat_estados t left join cat_paises tp On tp.id = t.pais_id where t.pais_id = ?", [$pais_id_us]);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }











}