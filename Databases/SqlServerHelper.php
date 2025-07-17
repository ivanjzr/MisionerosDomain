<?php
namespace App\Databases;

//
use Helpers\Helper;



//
class SqlServerHelper {







    /*
     *
     * LEE TODOS LOS REGISTROS DEL QUERY
     *
     * */
    public static function GetRecords($options){


        //
        $results = array();


        //
        try {

            //
            $sqlsrv_inst = new SqlServer($options['connection']);


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($options['stmt'], $options['params']);


            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($options['sqlsrv_debug'], $sql_exception, $options['exceptions']);
                return $results;
            }

            //
            while($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                //var_dump($row); exit;
                array_push($results, $row);
            }

            //
            return $results;
        }
        catch (\Exception $exeption){
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($options['sqlsrv_debug'], $exception_msg, array(
                "default" => "Server Error, unable to do operation"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            //var_dump($sqlsrv_inst->getConn()); exit; /*Debug closed connection*/
        }
    }









    /*
     *
     * LEE UN SOLO REGISTRO
     *
     * */
    public static function GetRecord($options){


        //
        $results = array();


        //
        try {

            //
            $sqlsrv_inst = new SqlServer($options['connection']);


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query($options['stmt'], $options['params']);


            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($options['sqlsrv_debug'], $sql_exception, $options['exceptions']);
                return $results;
            }

            //
            if ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
                //var_dump($row); exit;
                return $row;
            }

            //
            return $results;
        }
        catch (\Exception $exeption){
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($options['sqlsrv_debug'], $exception_msg, array(
                "default" => "Server Error, unable to do operation"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            //var_dump($sqlsrv_inst->getConn()); exit; /*Debug closed connection*/
        }
    }







    /*
     *
     * AGREGA UN REGISTRO
     * NOTA: SE DEBE DE UTILIZAR ;SELECT SCOPE_IDENTITY()
     *
     * */
    public static function InsertRecord($options){

        //
        $results = array();

        //
        try {

            //
            $sqlsrv_inst = new SqlServer($options['connection']);


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query_next($options['stmt'], $options['params']);


            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($options['sqlsrv_debug'], $sql_exception, $options['exceptions']);
                return $results;
            }

            //
            if ( $query && is_numeric($query) ){
                $results['id'] = (int)$query;
            }

            //
            return $results;
        }
        catch (\Exception $exeption){
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($options['sqlsrv_debug'], $exception_msg, array(
                "default" => "Server Error, unable to do operation"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            //var_dump($sqlsrv_inst->getConn()); exit; /*Debug closed connection*/
        }
    }







    /*
     *
     * ACTUALIZA O ELIMINA UN REGISTRO
     * NOTA: SE DEBE DE PROPORCIONAR @@ROWCOUNT
     *
     * */
    public static function UpdateRecord($options){

        //
        $results = array();

        //
        try {

            //
            $sqlsrv_inst = new SqlServer($options['connection']);


            //echo $stmt; exit;
            $query = $sqlsrv_inst->query_next($options['stmt'], $options['params']);


            // sql exceptions
            if ( $sql_exception = SqlServerHelper::parse_sql_exception($query) ){
                $results['error'] = SqlServerHelper::catch_err_msg($options['sqlsrv_debug'], $sql_exception, $options['exceptions']);
                return $results;
            }

            //
            if ( $query && is_numeric($query) ){
                $results['affected_rows'] = $query;
            }


            //
            return $results;
        }
        catch (\Exception $exeption){
            $exception_msg = SqlServerHelper::parse_exception_error($exeption);
            $results['error'] = SqlServerHelper::catch_err_msg($options['sqlsrv_debug'], $exception_msg, array(
                "default" => "Server Error, unable to do operation"
            ));
            return $results;
        }
        finally {
            if ( isset($sqlsrv_inst) && $sqlsrv_inst ){$sqlsrv_inst->closeConnection();}
            //var_dump($sqlsrv_inst->getConn()); exit; /*Debug closed connection*/
        }
    }










    /**
    SQL EXCEPTIONS PARSER
     */
    //getMessage
    //getPrevious
    //getCode
    //getFile
    //getLine
    //getTrace
    //getTraceAsString
    public static function parse_sql_exception(){
        $str_errors = "";
        if( ($errors = sqlsrv_errors() ) != null) {
            //print_r($errors);exit;
            foreach( $errors as $error ) {
                $str_errors .= "SQLSTATE: ".$error['SQLSTATE'] . ", code: ".$error['code'] . ", message: ".$error['message'];
            }
        }
        return $str_errors;
    }



    /**
    EXCEPTION ERROR PARSER
     */
    public static function parse_exception_error($exception){
        return "Error: {$exception->getMessage()}, code: {$exception->getCode()}, file: {$exception->getFile()}, line: {$exception->getLine()}";
    }



    /**
    REPLACES STRING FOR FOR VALUE
     */
    public static function catch_err_msg($sqlsrv_debug, $error_msg, $array_error_msgs){
        //var_dump($array_error_msgs); exit;
        // echo $error_msg; exit;
        // for production
        if ( $sqlsrv_debug == false ){
            //
            foreach($array_error_msgs as $key => $custom_msg){
                //
                if( Helper::str_contains($error_msg, $key) ) {
                    //
                    if ($custom_msg){
                        return $custom_msg;
                    }
                    //
                    else {
                        $pos = strpos($error_msg, $key);
                        return substr($error_msg, $pos, strlen($error_msg));
                    }
                }
            }
            foreach($array_error_msgs as $key => $custom_msg){
                //
                if ( $key === 'default' ){
                    return $custom_msg;
                }
            }
            // return
            return "Error, unable to complete operation";
        }

        // Para Dev solo devuele el mensaje de error
        return $error_msg;
    }



}

?>