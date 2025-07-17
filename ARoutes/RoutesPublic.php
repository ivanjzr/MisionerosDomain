<?php


use Middleware\SiteAccountHandler;
use Middleware\AuthApiOptional;



/*
 *
 * PUBLIC API
 *
 * */
$app->group('/public', function () use ($app) {



    
    
    
    //
    $app->get('/makes/list[/]', 'Controllers\Buses\CatMakesController:GetAllForSite');
    //
    $app->get('/models/list[/]', 'Controllers\Buses\CatModelsController:GetAllForSite');
    //
    $app->get('/avail-years[/]', 'Controllers\Utils\UtilsController:GetGroupedBusesYears');


    
    //
    $app->get('/buses[/]', 'Controllers\Buses\BusesController:GetAllForWebsite');






})->add(new SiteAccountHandler());
