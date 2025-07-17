<?php
namespace Controllers\Companies;

//
use App\Auth\ActivationCodes;
use App\Locations\CatPaises;
use App\Paths;

//
use App\Customers\Customers;
use Controllers\BaseController;
use App\App;
use App\Stores\Stores;;
use Helpers\Geolocation;
use Helpers\Helper;
use Helpers\EncryptHelper;
use Helpers\PHPMicroParser;
use Helpers\Query;
use Helpers\ValidatorHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;


//
class CompaniesController extends BaseController
{





    //
    public function ViewIndex($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/companies/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
            "user_session_data" => $request->getAttribute("ses_data")
        ]);
    }





    //
    public function GetListByType($request, $response, $args) {

        //
        $company_type_id = $args['company_type_id'];

        //
        $results = Query::All([
            "stmt" => function(){
                return "SELECT t.* FROM companies t Where t.active = 1 And cat_company_type_id = ?";
            },
            "params" => [
                $company_type_id
            ],
            "parse" => function(){

            }
        ]);
        //
        return $response->withJson($results, 200);
    }







    //
    public function GetRecordById($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $app_id = $ses_data['app_id'];


        //
        $results = Stores::GetRecordById($app_id, $args['id']);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }













}
