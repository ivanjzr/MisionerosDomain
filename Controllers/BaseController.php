<?php
namespace Controllers;


//
class BaseController{

    //
    protected $container;

    //
    public function __construct($container){
        //
        $this->container = $container;
    }

}
