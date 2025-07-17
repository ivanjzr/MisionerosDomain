<?php
namespace App\Users;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use App\Paths;
use App\Sucursales\Sucursales;
use Helpers\EncryptHelper;
use Helpers\Helper;
use Helpers\Query;


//
class Users
{






    public function GetAllAvailable($account_id, $show_pwd = false) {
        //
        $str_passwrod = "";
        if ($show_pwd){
            $str_passwrod = "t.password,";
        }
        //
        $stmt = "Select 

                    t.id,
                    t.account_id,
                    t.departamento_id,
                    t.titulo_id,
                    t.selected_sucursal_id,
                    t.name,
                    t.email,
                    t.img_ext,
                    {$str_passwrod}
                    t.phone_cc,
                    t.phone_number,
                    t.is_admin,
                    t.datetime_created,
                    t.notes,
                    t.active
                    
        from v_users t Where t.account_id = ? And t.active = 1";
        return Query::Multiple($stmt, [$account_id]);
    }


    public static function GetAllAdminUsers($account_id, $hide_pwd = true){
        //
        return Query::Multiple("SELECT * From v_users t Where t.account_id = ? And t.active = 1 And t.is_admin = 1", [$account_id], function(&$row) use($hide_pwd){
                //dd($row);
                if ($hide_pwd){
                    unset($row['password']);
                }
            });

    }


    public static function GetAllPosAdminUsers($account_id, $hide_pwd = true){
        //
        return Query::Multiple("SELECT * From v_pos_users t Where t.account_id = ? And t.active = 1 And t.is_admin = 1", [$account_id], function(&$row) use($hide_pwd){
                //dd($row);
                if ($hide_pwd){
                    unset($row['password']);
                }
            });

    }


    //
    public static function GetAll($account_id, $show_pwd = false){
        //
        $str_passwrod = "";
        if ($show_pwd){
            $str_passwrod = "t.password,";
        }
        //
        return Query::All([
            "stmt" => function() use($str_passwrod){
                return "
                    SELECT

                        t.id,
                        t.account_id,
                        t.departamento_id,
                        t.titulo_id,
                        t.selected_sucursal_id,
                        t.name,
                        t.email,
                        t.img_ext,
                        {$str_passwrod}
                        t.phone_cc,
                        t.phone_number,
                        t.is_admin,
                        t.datetime_created,
                        t.notes,
                        t.active

                        FROM v_users t
                           Where t.account_id = ?
                ";
            },
            "params" => [
                $account_id
            ]
        ]);
    }





    //
    public static function getContactId($account_id, $id){
        return Query::SingleRetValue("contact_id", "Select contact_id from users where account_id = ? And id = ?", [$account_id, $id]);
    }






    //
    public static function GetRecordById($account_id, $id, $hide_pwd = true){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                    SELECT * From v_users t Where t.account_id = ? And t.id = ?
                ";
            },
            "params" => [
                $account_id,
                $id
            ], 
            "parse" => function(&$row) use($hide_pwd){
                //dd($row);
                if ($hide_pwd){
                    unset($row['password']);
                }
            }
        ]);
    }








    /*
     *
     * Nos traemos users activos
     * limitados a una cuenta
     *
     * */
    //
    public static function GetValidUserAccount($account_id, $email, $password){
        //
        return Query::Get([
            "stmt" => function(){
                return "
                    SELECT
                    
                      t.*
                    
                        FROM v_users t
                    
                        Where t.account_id = ? 
                        And t.email = ?
                        And t.password = ?
                          
                        And t.active = 1
                ";
            },
            "params" => [
                $account_id,
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
                    users
                  
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












    //
    public static function UpdateSelectedSucursal($sucursal_id, $account_id, $user_id){
        //
        return Query::DoTask([
            "task" => "update",
            "debug" => true,
            "stmt" => "
                   Update 
                    users
                  
                  Set
                    selected_sucursal_id = ?
                  
                  Where account_id = ?             
                  And id = ?
                  ;SELECT @@ROWCOUNT
                ",
            "params" => [
                $sucursal_id,
                //
                $account_id,
                $user_id
            ],
            "parse" => function($updated_rows, &$query_results) use($user_id){
                $query_results['affected_rows'] = $updated_rows;
                $query_results['id'] = (int)$user_id;
            }
        ]);
        //var_dump($update_results); exit;
    }







}
