<?php
namespace Controllers\Historial;

//
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;


//
class HistorialCambiosController extends BaseController
{


    //
    public function Index($request, $response, $args) {
        //
        return $this->container->php_view->render($response, 'admin/historial_cambios/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data"))
        ]);
    }








}
