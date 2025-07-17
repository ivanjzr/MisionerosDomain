<?php
namespace Helpers;


//
use Helpers\Helper;



//
class ValidatorHelper {

    //
    function safeVar($arr, $var_name){
        if (isset($arr[$var_name])){
            return $arr[$var_name];
        }
        return null;
    }

    function get($var_name){
        return safeVar($_GET, $var_name);
    }

    function post($var_name){
        return safeVar($_POST, $var_name);
    }

    function sess($var_name){
        return safeVar($_SESSION, $var_name);
    }



    //
    function validateString($arr_len, $str){
        //
        $min = $arr_len[0];
        $max = $arr_len[1];
        //
        $str_len = strlen($str);
        // si strleng(5) >= min(1)
        if ( !empty($str) && $str_len >= $min && $str_len <= $max ){
            return true;
        }
        return false;
    }



    /*
     *
     * LA VALIDACION DE PWD NIVEL 1 VALIDA QUE TENGA ALFABETO
     *
     * */
    function validatePasswordLevel1($arr_len, $str){
        //
        $min = $arr_len[0];
        $max = $arr_len[1];
        //
        $str_len = strlen($str);



        // debe de contener letras
        if (!Helper::str_contains_alphabet($str)){
            return false;
        }

        // si strleng(5) >= min(1)
        if ( !empty($str) && $str_len >= $min && $str_len <= $max ){
            return true;
        }
        return false;
    }



    /*
     *
     * LA VALIDACION DE PWD NIVEL 2 VALIDA QUE TENGA ALFABETO Y NUMEROS
     *
     * */
    function validatePasswordLevel2($arr_len, $str){
        //
        $min = $arr_len[0];
        $max = $arr_len[1];
        //
        $str_len = strlen($str);



        // debe de contener letras
        if (!Helper::str_contains_alphabet($str)){
            return false;
        }

        // debe de contener numeros
        if (!Helper::str_contains_number($str)){
            return false;
        }

        // si strleng(5) >= min(1)
        if ( !empty($str) && $str_len >= $min && $str_len <= $max ){
            return true;
        }
        return false;
    }




    //
    function validateEmail($arr_len, $email){
        //
        $min = $arr_len[0];
        $max = $arr_len[1];
        //
        $email_len = strlen($email);
        // si strleng(5) >= min(1)
        if ( !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && $email_len >= $min && $email_len <= $max ){
            return true;
        }
        return false;
    }


    //
    function validateNumber($max_len, $number){

        //
        $number_len = strlen($number);

        //
        if ( is_numeric($number) && $number_len <= $max_len ){
            return true;
        }
        return false;
    }




}

?>