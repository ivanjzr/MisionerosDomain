<?php
namespace App\Administrators;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\EncryptHelper;
use Helpers\Helper;
use Helpers\Query;


//
class Administrators
{







    //
    public static function GetAll(){
        //
        return Query::All([
            "stmt" => function(){
                return "
				SELECT
					t.*
					FROM administrators t
			";
            },
            "params" => [

            ],
            "parse" => function(){

            }
        ]);
    }









    //
    public static function GetRecordById($id){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    
                    t.*
                  
                      FROM administrators t
                  
                  
                        Where t.id = ?
                ";
            },
            "params" => [
                $id
            ]
        ]);
    }









    //
    public static function GetValidAdmin($email, $password){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                   SELECT
                    t.*
                      FROM administrators t
                      
                        Where t.email = ?
                        And t.password = ?
                          
                        And t.active = 1
                ";
            },
            "params" => [
                $email,
                $password
            ]
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
                    administrators
                  
                  Set
                    img_ext = ?
                  
                  Where id = ?
                  ;SELECT @@ROWCOUNT
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                //
                $options['img_ext'],
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
