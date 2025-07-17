<?php


//
use Middleware\AuthSessionAdmin;
use Middleware\AuthSessionAdminLoggedOnly;



/*
 * Buses
 * */
$app->group('/buses', function () use ($app) {

    // views
    $app->get('/index', 'Controllers\Buses\BusesController:ViewIndex');
    $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Buses\BusesController:ViewEdit');


    
    /*
    * Makes
    * */
    $app->group('/makes', function () use ($app) {
        // views
        $app->get('/index', 'Controllers\Buses\CatMakesController:ViewIndex');
        // api
        $app->get('[/]', 'Controllers\Buses\CatMakesController:PaginateRecords');        
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Buses\CatMakesController:GetRecord');
        $app->post('[/]', 'Controllers\Buses\CatMakesController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Buses\CatMakesController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Buses\CatMakesController:DeleteRecord');
    });


    /*
    * Models
    * */
    $app->group('/models', function () use ($app) {
        // views
        $app->get('/index', 'Controllers\Buses\CatModelsController:ViewIndex');
        // api
        $app->get('[/]', 'Controllers\Buses\CatModelsController:PaginateRecords');        
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Buses\CatModelsController:GetRecord');
        $app->post('[/]', 'Controllers\Buses\CatModelsController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Buses\CatModelsController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Buses\CatModelsController:DeleteRecord');
    });


    /*
    * Features
    * */
    $app->group('/features', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Buses\CatFeaturesController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Buses\CatFeaturesController:PaginateRecords');            
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Buses\CatFeaturesController:GetRecord');
        $app->post('[/]', 'Controllers\Buses\CatFeaturesController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Buses\CatFeaturesController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Buses\CatFeaturesController:DeleteRecord');

    });





    /*
    * Config
    * */
    $app->group('/config', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Products\ProductsConfigController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Products\ProductsConfigController:GetRecord');
        $app->post('[/]', 'Controllers\Products\ProductsConfigController:UpsertRecord');



        //
        $app->group('/taxes', function () use ($app) {
            $app->get('[/]', 'Controllers\Products\ProductsConfigController:PaginateRecords');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Products\ProductsConfigController:UpdateStateTax');
        });


    });





    //$app->get('/update-images', 'Controllers\Buses\BusesController:UpdateImagesSizeAndExtencion');

    // api
    $app->get('[/]', 'Controllers\Buses\BusesController:PaginateRecords');    
    $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Buses\BusesController:GetRecord');
    $app->post('[/]', 'Controllers\Buses\BusesController:Upsert');
    $app->post('/upload[/]', 'Controllers\Buses\BusesController:UploadXlsFile');
    $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Buses\BusesController:Upsert');
    $app->post('/{id:[0-9]{0,11}}/update-price-type[/]', 'Controllers\Buses\BusesController:UpdatePriceType');

    //
    $app->post('/clone-prices[/]', 'Controllers\Buses\BusesController:PostClonePrices');

    //
    $app->get('/get-prices-info[/]', 'Controllers\Buses\BusesController:GetPricesInfo');
    //
    $app->post('/del[/]', 'Controllers\Buses\BusesController:DeleteRecord');

    //
    $app->group('/{id:[0-9]{0,11}}/prices', function () use ($app) {
        $app->get('[/]', 'Controllers\Buses\BusesPricesController:PaginateRecords');
        $app->post('[/]', 'Controllers\Buses\BusesPricesController:UpdatePrecio');
    });

    //
    $app->group('/{id:[0-9]{0,11}}/promociones', function () use ($app) {
        $app->get('[/]', 'Controllers\Buses\BusesPromocionesController:PaginateRecords');
        $app->post('[/]', 'Controllers\Buses\BusesPromocionesController:AddRecord');
        $app->post('/del[/]', 'Controllers\Buses\BusesPromocionesController:DeleteRecord');
    });
    
    //
    $app->group('/{id:[0-9]{0,11}}/features', function () use ($app) {
        $app->get('[/]', 'Controllers\Buses\BusesFeaturesController:PaginateRecords');
        $app->post('[/]', 'Controllers\Buses\BusesFeaturesController:AddRecord');
        $app->post('/del[/]', 'Controllers\Buses\BusesFeaturesController:DeleteRecord');
    });

    //
    $app->group('/{id:[0-9]{0,11}}/gallery', function () use ($app) {
        $app->get('[/]', 'Controllers\Buses\BusesGalleryController:PaginateRecords');
        $app->post('[/]', 'Controllers\Buses\BusesGalleryController:AddRecord');
        $app->post('/del[/]', 'Controllers\Buses\BusesGalleryController:DeleteRecord');
    });

})->add(new AuthSessionAdmin());



//
$app->get('/buses/search[/]', 'Controllers\Buses\BusesController:GetSearch')->add(new AuthSessionAdminLoggedOnly());
//
$app->get('/buses/list', 'Controllers\Buses\BusesController:GetAll')->add(new AuthSessionAdminLoggedOnly());
$app->get('/buses/pos-available', 'Controllers\Buses\BusesController:GetAvaiableForPOS')->add(new AuthSessionAdminLoggedOnly());
$app->get('/buses/available-services', 'Controllers\Buses\BusesController:GetAllAvailableServices')->add(new AuthSessionAdminLoggedOnly());
//
$app->get('/buses/tipos/{tipo_producto_servicio_id:[0-9]{0,11}}[/]', 'Controllers\Buses\BusesController:GetAllByType')->add(new AuthSessionAdminLoggedOnly());
$app->get('/buses/makes/list[/]', 'Controllers\Buses\CatMakesController:GetAll')->add(new AuthSessionAdminLoggedOnly());
$app->get('/buses/models/list[/]', 'Controllers\Buses\CatModelsController:GetAll')->add(new AuthSessionAdminLoggedOnly());
$app->get('/buses/features/list[/]', 'Controllers\Buses\CatFeaturesController:GetAll')->add(new AuthSessionAdminLoggedOnly());
