<?php
namespace App\Employees;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use App\Sucursales\Sucursales;
use Helpers\EncryptHelper;
use Helpers\Helper;
use Helpers\Query;


//
class Employees
{






    public function GetAllAvailable($account_id) {
        //
        $stmt = "Select 

                    t.id,
                    t.account_id,
                    t.departamento_id,
                    t.titulo_id,
                    t.selected_sucursal_id,
                    t.nombre,
                    t.email,
                    t.img_ext,
                    t.phone_cc,
                    t.phone_number,
                    t.is_admin,
                    t.datetime_created,
                    t.notes,
                    t.active
                    
        from v_employees t Where t.account_id = ? And t.active = 1";
        return Query::Multiple($stmt, [$account_id]);
    }


    

    //
    public static function GetAll($account_id){
        //
        return Query::All([
            "stmt" => function(){
                return "
                    SELECT

                        t.id,
                        t.account_id,
                        t.departamento_id,
                        t.titulo_id,
                        t.selected_sucursal_id,
                        t.nombre,
                        t.email,
                        t.img_ext,
                        t.phone_cc,
                        t.phone_number,
                        t.is_admin,
                        t.datetime_created,
                        t.notes,
                        t.active

                        FROM v_employees t
                           Where t.account_id = ?
                ";
            },
            "params" => [
                $account_id
            ]
        ]);
    }










    //
    public static function GetRecordById($account_id, $id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                    SELECT * From v_employees t Where t.account_id = ? And t.id = ?
                ";
            },
            "params" => [
                $account_id,
                $id
            ], 
            "parse" => function(&$row){
                //dd($row);
                unset($row['password']);
            }
        ]);
    }








    //
    public static function UpdateImgExt($options){
        global $app;


        //
        $settings = $app->getContainer()->settings;

        //
        $results = array();



        //$sqlsrv_debug = $settings['sqlsrv_debug'];
        $sqlsrv_debug = true;


        //
        $exeptions_msgs = array(
            "default" => "Server Error, unable to do operation"
        );




        //
        try {



            //
            $sqlsrv_inst = new SqlServer($settings['sqlsrv_connection']);





            //
            $stmt = "
                  Update 
                    employees
                  
                  Set
                    img_ext = ?
                  
                  Where account_id = ?
                  And id = ?
                  ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                //
                $options['img_ext'],
                $options['account_id'],
                $options['id']
            );
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query_update = $sqlsrv_inst->query_next($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query_update) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }


            //
            $results['affected_rows'] = $query_update;
            $results['id'] = $options['id'];



            //
            return $results;
        }
        catch (\Exception $exeption){
            //
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $exception_msg, array(
                "default" => "Server Error, unable to do operation",
                "SQLSTATE: 28000" => "custom match err 28000"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            // debug connection - var_dump($sqlsrv_inst->getConn()); exit;
        }
    }










    


}
