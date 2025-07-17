<?php
namespace Controllers\Ubicaciones;

//

use Controllers\BaseController;

//
use App\App;
//
use Helpers\Helper;
use App\Empleados\EmpleadosSucursales;
//
use Helpers\Query;


//
class UbicacionesController extends BaseController
{



    

    
    //
    public function GetAll($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        


        /**
         * 
         * Si el user es tipo backend y no es admin, limitamos a las sucurasles que puede reservar
         * 
         */
        if (isset($ses_data['t']) && $ses_data['t'] === "b" ){
            
            //
            $user_id = (int)$ses_data['id'];
            $user_is_admin = $ses_data['is_admin'];
            
            //
            if (!$user_is_admin){
                //
                $user_sucursales = EmpleadosSucursales::GetAll($account_id, $user_id);
                //dd($user_sucursales); exit;
                return $response->withJson($user_sucursales, 200);
            }
        }
        
        


        /**
         * 
         * Para otros casos que son backend user admin y front page user mostramos todas las sucursales
         */
        $results = Query::Multiple("Select * from sucursales Where account_id = ?", [$account_id]);
        //dd($results); exit;
        return $response->withJson($results, 200);
    }





    

    
    //
    public function GetAllAvailable($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data);
        $account_id = $ses_data['account_id'];
        $app_id = $ses_data['app_id'];
        

        //
        $user_type = (isset($ses_data['t']) && $ses_data['t']) ? $ses_data['t'] : null;
        //
        if (!$user_type){
            $results['error'] = "not a valid user type";
            return $response->withJson($results, 400);
        }



        /**
         * 
         * Si el user es tipo backend y no es admin, limitamos a las sucurasles que puede reservar
         * 
         */
        if ($user_type === "b" ){
            //
            $user_id = (int)$ses_data['id'];
            $user_is_admin = $ses_data['is_admin'];
            //
            if (!$user_is_admin){
                //
                $user_sucursales = EmpleadosSucursales::GetAll($account_id, $user_id);
                //dd($user_sucursales); exit;
                return $response->withJson($user_sucursales, 200);
            }
        }
        
        


        /**
         * 
         * Para otros casos que son backend user admin y front page user mostramos todas las sucursales
         */
        $results = Query::Multiple("Select * from sucursales Where account_id = ? And active = 1", [$account_id]);
        //dd($results); exit;
        return $response->withJson($results, 200);
    }








    //
    public function GetRecord($request, $response, $args) {

        //
        $ses_data = $request->getAttribute("ses_data");
        //dd($ses_data); exit;
        $app_id = $ses_data['app_id'];
        $user_id = $ses_data['id'];

        

        $record_id = $args['id'];

        //
        $results = Query::Single("Select * From sucursales Where app_id = ? And id = ?", [$app_id, $record_id]);
        //var_dump($results); exit;


        //
        return $response->withJson($results, 200);
    }


    
    
    



}
