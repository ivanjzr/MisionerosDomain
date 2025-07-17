<?php
namespace Controllers\Admin;

//
use Controllers\BaseController;
use App\App;
use Helpers\Helper;


//
class HomeController extends BaseController
{


    //
    public function ViewIndex($request, $response, $args) {
        //dd($request->getAttribute("ses_data")); exit;

        //
        return $this->container->php_view->render($response, 'admin/home.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }






}
