<?php


use Middleware\AuthSessionSupadmin;



/*
 *
 *
 * SUPER ADMIN PLATFORM
 *
 * */
//
$app->group('/adm27', function () use ($app) {



    // Método recomendado (más preciso)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }




    // fix redirect
    $app->redirect('[/]', '/adm27/home');


    // View Login
    $app->get('/login[/]', 'Controllers\Supadmin\AuthController:ViewLogin');


    /*
    * Auth
    * */
    $app->group('/auth', function () use ($app) {
        //
        $app->post('/register[/]', 'Controllers\Supadmin\AuthController:Register');
        $app->post('/login[/]', 'Controllers\Supadmin\AuthController:Login');
        $app->get('/logout[/]', 'Controllers\Supadmin\AuthController:Logout');
    });



    // View Home
    $app->get('/index[/]', 'Controllers\Supadmin\HomeController:ViewIndex')->add(new AuthSessionSupadmin());
    $app->get('/home[/]', 'Controllers\Supadmin\HomeController:ViewIndex')->add(new AuthSessionSupadmin());






    /*
     * Cuentas
     * */
    $app->group('/accounts', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Accounts\AccountsController:ViewIndex');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Accounts\AccountsController:ViewEdit');

        // api
        $app->get('[/]', 'Controllers\Accounts\AccountsController:PaginateRecords');
        $app->get('/list[/]', 'Controllers\Accounts\AccountsController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Accounts\AccountsController:GetRecord');
        $app->post('[/]', 'Controllers\Accounts\AccountsController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Accounts\AccountsController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Accounts\AccountsController:DeleteRecord');


        //
        $app->group('/{account_id:[0-9]{0,11}}/models', function () use ($app) {

            //
            $app->get('/index[/]', 'Controllers\Accounts\AccountsModelsController:ViewModels');

            // Api
            $app->get('[/]', 'Controllers\Accounts\AccountsModelsController:PaginateRecords');
            $app->post('/set-mode[/]', 'Controllers\Accounts\AccountsModelsController:PostSetMode');

        });

    })->add(new AuthSessionSupadmin());




    //
    $app->group('/models', function () use ($app) {

        //
        $app->get('/index[/]', 'Controllers\Supadmin\ModelsController:ViewIndex');

        //
        $app->get('[/]', 'Controllers\Supadmin\ModelsController:PaginateRecords');
        $app->get('/{menu_id:[0-9]{0,11}}[/]', 'Controllers\Supadmin\ModelsController:GetRecord');
        $app->post('[/]', 'Controllers\Supadmin\ModelsController:PostUpsertRecord');
        $app->post('/{menu_id:[0-9]{0,11}}[/]', 'Controllers\Supadmin\ModelsController:PostUpsertRecord');
        $app->post('/del[/]', 'Controllers\Supadmin\ModelsController:DeleteRecord');
        //
        $app->post('/update-order[/]', 'Controllers\Supadmin\ModelsController:PostUpdateOrder');


    })->add(new AuthSessionSupadmin());



    /*
     * Catalogo de Ubicaciones
     * */
    $app->group('/locations', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Locations\CatEstadosController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Locations\CatEstadosController:PaginateRecords');
        $app->get('/list', 'Controllers\Locations\CatEstadosController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Locations\CatEstadosController:GetRecord');
        $app->post('[/]', 'Controllers\Locations\CatEstadosController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Locations\CatEstadosController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Locations\CatEstadosController:DeleteRecord');



        /*
         * Estados -> Ciudades
         * */
        $app->group('/{estado_id:[0-9]{0,11}}/ciudades', function () use ($app) {

            // views
            $app->get('/index', 'Controllers\Locations\CatCiudadesController:ViewIndex');

            // api
            $app->get('[/]', 'Controllers\Locations\CatCiudadesController:PaginateRecords');
            $app->get('/list', 'Controllers\Locations\CatCiudadesController:GetAll');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Locations\CatCiudadesController:GetRecord');
            $app->post('[/]', 'Controllers\Locations\CatCiudadesController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Locations\CatCiudadesController:UpdateRecord');
            $app->post('/del[/]', 'Controllers\Locations\CatCiudadesController:DeleteRecord');

        })->add(new AuthSessionSupadmin());

    });









    /*
    * Administrators
    * */
    $app->group('/administrators', function () use ($app) {


        // views
        $app->get('/index[/]', 'Controllers\Administrators\AdministratorsController:Index');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Administrators\AdministratorsController:Edit');

        // api
        $app->get('[/]', 'Controllers\Administrators\AdministratorsController:PaginateRecords');
        $app->get('/list', 'Controllers\Administrators\AdministratorsController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Administrators\AdministratorsController:GetRecord');
        $app->post('[/]', 'Controllers\Administrators\AdministratorsController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Administrators\AdministratorsController:UpdateRecord');
        $app->post('/{id:[0-9]{0,11}}/upload-img[/]', 'Controllers\Administrators\AdministratorsController:PostUploadImage');
        $app->post('/del[/]', 'Controllers\Administrators\AdministratorsController:DeleteRecord');



    })->add(new AuthSessionSupadmin());


});




