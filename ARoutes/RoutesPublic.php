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
    $app->get('/qbo-redir-url', 'Controllers\PublicController:ViewQBORedirUrl');



    //
    $app->get('/get-recommended-plans[/]', 'Controllers\Products\SubscriptionsController:GetRecommendedPlans')
        ;



    //
    $app->get('/search[/]', 'Controllers\Sucursales\SucursalesController:GetSearch')
        ;


    



    //
    $app->get('/loc-info[/]', 'Controllers\Sucursales\SucursalesController:GetLocationInfo');
    $app->post('/loc-info[/]', 'Controllers\Sucursales\SucursalesController:PostSetLocationInfo');
    $app->get('/sucursales/by[/]', 'Controllers\Sucursales\SucursalesController:GetByLatLng');





    //
    $app->get('/categories/list[/]', 'Controllers\Catalogues\CatCategoriesController:GetAllForSite');
    //
    $app->get('/workers-types[/]', 'Controllers\Sys\SysController:GetWorkersTypes');
    //
    $app->get('/workers-subscriptions[/]', 'Controllers\Products\SubscriptionsController:GetWorkersSubscriptions');
    $app->get('/companies-subscriptions[/]', 'Controllers\Products\SubscriptionsController:GetCompaniesSubscriptions');



    //
    $app->get('/pconfig[/]', 'Controllers\Api\AuthController:getPlatformConfig');


    
    //
    $app->group('/utils', function () use ($app) {

        // 
        $app->get('/origenes[/]', 'Controllers\Utils\UtilsController:GetOrigenesList');
        $app->get('/{origen_ciudad_id:[0-9]{0,11}}/destinos[/]', 'Controllers\Utils\UtilsController:GetDestinosList');
        $app->post('/buscar[/]', 'Controllers\Utils\UtilsController:PostBuscar');
        //
        $app->post('/calc-dob[/]', 'Controllers\Utils\UtilsController:PostCalcDob');
        //
        $app->get('/results/{ruta_id:[0-9]{0,11}}/{salida_id:[0-9]{0,11}}/{origen_ciudad_id:[0-9]{0,11}}/{destino_ciudad_id:[0-9]{0,11}}[/]', 'Controllers\Salidas\SalidasController:GetSalidaInfo');

        //
        $app->get('/temp-ocupacion/{temp_sale_id}[/]', 'Controllers\Ventas\VentasController:GetTempOcupacionBySaleId');
        $app->post('/apartar[/]', 'Controllers\Ventas\VentasController:PostApartarLugar');
        $app->post('/clear[/]', 'Controllers\Ventas\VentasController:PostClearVenta');
        $app->post('/del-item[/]', 'Controllers\Ventas\VentasController:PostDelTempItem');
        
    
        


    });

    
    //    
    $app->post('/pay[/]', 'Controllers\Ventas\VentasController:PostPayPublicOrRegistered')->add(new AuthApiOptional());



    


    //
    $app->post('/apply-coupon[/]', 'Controllers\Coupons\StripsCouponsController:PostCheckCoupon');
    //    
    $app->get('/sales/{id:[0-9]{0,11}}[/]', 'Controllers\Ventas\VentasController:GetSaleInfo');


    //
    $app->get('/tickets/{sale_code_item_id:[0-9-]{0,11}}[/]', 'Controllers\Ventas\VentasController:generatePublicTicketUrl');
    $app->get('/invoices/{invoice_code:[0-9-]{0,11}}[/]', 'Controllers\Invoices\InvoicesController:generatePublicInvoiceUrl');
    
    



    //
    $app->get('/cat-customers-types/list[/]', 'Controllers\Catalogues\CatCustomersTypesController:GetAllPublic');



    /*
     * Paises
     * */
    $app->group('/paises', function () use ($app) {
        //
        $app->get('/list[/]', 'Controllers\Sys\SysController:GetPaises');
    });



    /*
     * Estados
     * */
    $app->group('/estados', function () use ($app) {


        // api
        $app->get('/list', 'Controllers\Locations\CatEstadosController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Locations\CatEstadosController:GetRecord');



        /*
         * Estados -> Ciudades
         * */
        $app->group('/{estado_id:[0-9]{0,11}}/ciudades', function () use ($app) {


            // api
            $app->get('/list', 'Controllers\Locations\CatCiudadesController:GetAll');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Locations\CatCiudadesController:GetRecord');


        });

    });



    /*
     * Periodicidad
     * */
    $app->group('/periodicidad', function () use ($app) {

        //
        $app->get('[/]', 'Controllers\Sys\SysController:GetPeriodicidad');
    });




    /*
     * Clasification Foods
     * */
    $app->group('/clasification-foods', function () use ($app) {
        //
        $app->get('[/]', 'Controllers\Sys\SysController:GetClasificationFoods');
    });






})->add(new SiteAccountHandler());
