<?php
namespace Controllers\Supadmin;

//
use Controllers\BaseController;
use App\App;



//
class HomeController extends BaseController
{


    //
    public function ViewIndex($request, $response, $args) {

        //
        return $this->container->php_view->render($response, 'supadmin/home.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }


}
