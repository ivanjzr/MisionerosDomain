<?php
namespace App;

//
use App\Databases\SqlServer;
use App\Databases\SqlServerHelper;
use Helpers\Helper;



//
class Pages
{



    //
    public static function GetPage($lang_id, $section_name){
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
                  SELECT
                    t.*
                      FROM pages t                    
                        
                        Where t.name = ?
                        And t.lang_id = ?
                        
                        And t.active = 1                        
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                $section_name,
                $lang_id
            );
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }


            //
            if($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){

                //
                $row['content'] = self::GetPageContent($row['id']);

                //
                return $row;
            }


            //
            return null;
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
    public static function GetPageContent($page_id){
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
              SELECT
                    t.*
                      FROM pages_content t                    
                        
                        Where t.page_id = ?     
                        And t.active = 1                        
                  ";
            //echo $stmt; exit;

            //
            $params = array(
                $page_id
            );
            //var_dump($params); exit;


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($stmt, $params);



            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($sqlsrv_debug, $sql_exception, $exeptions_msgs);
                return $results;
            }


            //
            $records = array();

            //
            if($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                array_push($records, $row);
            }


            //
            return $records;
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
