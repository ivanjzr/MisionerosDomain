<?php
namespace Controllers\Catalogues;

//
use App\Catalogues\CatSalesStatus;
use App\Paths;

use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;

//
class CatSalesStatusController extends BaseController
{




    //
    public function GetAll($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        //$app_id = $ses_data['app_id'];

        //
        $update_type = $request->getQueryParam("ut");
        $exclude_type = $request->getQueryParam("excl");
        //echo " $update_type $exclude_type "; exit;


        //
        $str_and_clause = "";
        //
        if ( $update_type === "kitchen" ){
            $str_and_clause = " And (
                ( t.sale_status = 'preparing' ) Or
                ( t.sale_status = 'ready' ) 
            )";
        }
        //
        else if ( $update_type === "delivery" || $update_type === "pickup" ){
            $str_and_clause = " And (
                ( t.sale_status = 'ready' ) Or
                ( t.sale_status = 'delivered' ) 
            )";
        }


        //
        $str_and_excl_sale_status = "";
        if ($exclude_type){
            $str_and_excl_sale_status = " And t.sale_status != '$exclude_type' ";
        }

        //
        $res = Query::Multiple("Select * from cat_sale_status t $str_and_clause $str_and_excl_sale_status", [
        ], function(&$row) use($update_type){

            //
            CatSalesStatus::renameStatusTitle($update_type, $row);

        });


        //
        return $response->withJson($res, 200);
    }
















    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = Query::GetRecordById("cat_sale_status", $app_id, $args["id"]);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }











}