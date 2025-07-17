<?php
/**
 * Created by PhpStorm.
 * User: Yoyis
 * Date: 30/03/2016
 * Time: 12:25 PM
 */

namespace Helpers;



class PHPMicroParser{

    //
    private $variables = array();


    //
    public $variable_start = "{";
    public $variable_end = "}";


    //
    public function __construct($var_start = "{", $var_end = "}"){
        // set variables start/end
        //
        if ($var_start){
            $this->variable_start = $var_start;
        }
        //
        if ($var_end){
            $this->variable_end = $var_end;
        }
    }

    //
    public function setVariablesStartEndRegnizers($variable_start, $variable_end){
        $this->variable_start = $variable_start;
        $this->variable_end = $variable_end;
    }

    //
    function varName($variable_name){
        return $this->variable_start.$variable_name.$this->variable_end;
    }

    //
    function setVariable($variable_name, $content_to_replace){
        $this->variables[$this->varName($variable_name)] = $content_to_replace;
    }

    // get variable
    function getVariable($variable_name){
        if (isset($this->variables[$this->varName($variable_name)])){
            return $this->variables[$this->varName($variable_name)];
        }
        return false;
    }

    // parse extra content (widgets, plugins, etc) if any
    function parseVariables($content){
        return str_replace(
            array_keys($this->variables),
            array_values($this->variables),
            $content
        );
    }

    function getAllVariables(){
        return $this->variables;
    }


}