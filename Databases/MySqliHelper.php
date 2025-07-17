<?php
namespace App\Databases;

//
use App\Helpers\Helper;



//
class MySqliHelper {



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
            foreach($array_error_msgs as $key=>$custom_msg){
                //
                if( Helper::str_contains($error_msg, $key) ) {
                    return $custom_msg;
                }
            }
            foreach($array_error_msgs as $key=>$custom_msg){
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