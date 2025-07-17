<?php


use Middleware\AuthSessionAdmin;
use Middleware\AuthSessionAdminLoggedOnly;
use Middleware\SiteAccountHandler;



/*
 *
 *
 * ADMIN PLATFORM
 *
 * */
$app->group('/admin', function () use ($app) {




    
    // Método recomendado (más preciso)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    //session_destroy(); exit;




    // fix redirect
    $app->redirect('[/]', '/admin/home');



    // View Login
    $app->get('/login[/]', 'Controllers\Admin\AuthController:ViewLogin')->add(new SiteAccountHandler());

    /*
    * Auth
    * */
    $app->group('/auth', function () use ($app) {
        //
        $app->post('/register[/]', 'Controllers\Admin\AuthController:Register');
        $app->post('/login[/]', 'Controllers\Admin\AuthController:Login');
        //
        $app->get('/logout[/]', 'Controllers\Admin\AuthController:Logout');
    });


    /*
    * Current user
    * */
    $app->group('/user', function () use ($app) {

        // view
        $app->get('/select-sucursal[/]', 'Controllers\Admin\UserController:ViewSelectSucursal');

        //
        $app->post('/update-sucursal[/]', 'Controllers\Admin\UserController:PostSelectSucursal');
        $app->get('/sucursales[/]', 'Controllers\Admin\UserController:GetUserSucursales');

    })->add(new AuthSessionAdminLoggedOnly());



    // ADMIN HOME VIEWS
    $app->get('/dashboard/index[/]', 'Controllers\Admin\HomeController:ViewIndex')->add(new AuthSessionAdmin("all"));
    $app->get('/index[/]', 'Controllers\Admin\HomeController:ViewIndex')->add(new AuthSessionAdmin("all"));
    $app->get('/home[/]', 'Controllers\Admin\HomeController:ViewIndex')->add(new AuthSessionAdmin("all"));



    /*
     * Users
     * */
    $app->group('/users', function () use ($app) {        

        // views
        $app->get('/index', 'Controllers\Users\UsersController:Index');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Users\UsersController:Edit');

        // api
        $app->get('[/]', 'Controllers\Users\UsersController:PaginateRecords');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Users\UsersController:GetRecord');
        $app->post('[/]', 'Controllers\Users\UsersController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Users\UsersController:UpdateRecord');
        $app->post('/{id:[0-9]{0,11}}/update-pos[/]', 'Controllers\Users\UsersController:PostUpdatePOSData');
        $app->post('/{id:[0-9]{0,11}}/upload-img[/]', 'Controllers\Users\UsersController:PostUploadImage');
        $app->post('/del[/]', 'Controllers\Users\UsersController:DeleteRecord');


        /*
         * Users -> Sucursales 
         * */
        $app->group('/{user_id:[0-9]{0,11}}/sucursales', function () use ($app) {


            //
            $app->get('[/]', 'Controllers\Users\UsersSucursalesController:PaginateRecords');


            /*
             * Users -> Sucursales -> Permisos
             * */
            $app->group('/{sucursal_id:[0-9]{0,11}}/permisos', function () use ($app) {
                //
                $app->get('[/]', 'Controllers\Users\UsersSucursalesPermisosController:GetAll');
                $app->post('[/]', 'Controllers\Users\UsersSucursalesPermisosController:upsertPermisos');
            });

        });



    })->add(new AuthSessionAdmin("all"));




    $app->get('/users/list', 'Controllers\Users\UsersController:GetAll')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/users/list-available[/]', 'Controllers\Users\UsersController:GetAllAvailable')->add(new AuthSessionAdminLoggedOnly());
    //$app->get('/get-subscriptions[/]', 'Controllers\Products\ProductosController:GetByCustomerTypeId')->add(new AuthSessionAdmin("all"));
    





    /*
     * Empleados
     * */
    $app->group('/employees', function () use ($app) {        

        // views
        $app->get('/index', 'Controllers\Employees\EmployeesController:Index');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Employees\EmployeesController:Edit');

        // api 
        $app->get('[/]', 'Controllers\Employees\EmployeesController:PaginateRecords');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Employees\EmployeesController:GetRecord');
        $app->post('[/]', 'Controllers\Employees\EmployeesController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}/commissions[/]', 'Controllers\Employees\EmployeesController:PostUpdateCommissions');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Employees\EmployeesController:UpdateRecord');
        $app->post('/{id:[0-9]{0,11}}/upload-img[/]', 'Controllers\Employees\EmployeesController:PostUploadImage');
        $app->post('/del[/]', 'Controllers\Employees\EmployeesController:DeleteRecord');
        

    })->add(new AuthSessionAdmin("all"));
    
    //
    $app->get('/employees/list', 'Controllers\Employees\EmployeesController:GetAll')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/employees/search[/]', 'Controllers\Employees\EmployeesController:GetSearch')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/employees/list-available[/]', 'Controllers\Employees\EmployeesController:GetAllAvailable')->add(new AuthSessionAdminLoggedOnly());
    //
    $app->get('/job-titles/list', 'Controllers\Catalogues\CatJobTitlesController:GetAll')->add(new AuthSessionAdminLoggedOnly());

    

    
    
    

    /*
    * Ciudades
    * */
    $app->group('/ciudades', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Loc\CiudadesController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Loc\CiudadesController:PaginateRecords');
        
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Loc\CiudadesController:GetRecord');
        $app->post('[/]', 'Controllers\Loc\CiudadesController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Loc\CiudadesController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Loc\CiudadesController:DeleteRecord');

    })->add(new AuthSessionAdminLoggedOnly());

    $app->get('/ciudades/list[/]', 'Controllers\Loc\CiudadesController:GetAll');



    /*
    * Estados
    * */
    $app->group('/estados', function () use ($app) {


        // api
        $app->get('/list[/]', 'Controllers\Loc\EstadosController:GetAll');

    })->add(new AuthSessionAdminLoggedOnly());






    /*
      * Salidas
      * */
    $app->group('/salidas', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Salidas\SalidasController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Salidas\SalidasController:PaginateRecords');
        $app->get('/list', 'Controllers\Salidas\SalidasController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Salidas\SalidasController:GetRecord');
        $app->get('/{id:[0-9]{0,11}}/ruta[/]', 'Controllers\Salidas\SalidasController:GetRutaInfo');
        $app->get('/{id:[0-9]{0,11}}/ocupacion[/]', 'Controllers\Salidas\SalidasController:GetSalidaOcupacion');
        $app->post('[/]', 'Controllers\Salidas\SalidasController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Salidas\SalidasController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Salidas\SalidasController:DeleteRecord');

    })->add(new AuthSessionAdmin("all"));




    $app->group('/ventas-t4b', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Ventas\VentasController:ViewT4BIndex');

    })->add(new AuthSessionAdmin("all"));


    $app->group('/ventas-plabuz', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Ventas\VentasController:ViewPlabuzIndex');

    })->add(new AuthSessionAdmin("all"));



    /*
      * Ventas
      * */
    $app->group('/ventas', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Ventas\VentasController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Ventas\VentasController:PaginateRecords');
        $app->get('/list', 'Controllers\Ventas\VentasController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Ventas\VentasController:GetRecord');
        $app->get('/{id:[0-9]{0,11}}/ruta[/]', 'Controllers\Ventas\VentasController:GetRutaInfo');
        $app->post('[/]', 'Controllers\Ventas\VentasController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Ventas\VentasController:UpdateRecord');

        //        
        $app->post('/update-ocup[/]', 'Controllers\Ventas\VentasController:PostActualizarOcupacion');

        
        //
        $app->post('/{sale_id:[0-9]{0,11}}/send-confirmation-link[/]', 'Controllers\Ventas\VentasController:PostSendConfirmationLink');
        $app->post('/{sale_id:[0-9]{0,11}}/send-ticket[/]', 'Controllers\Ventas\VentasController:PostSendTicket');

        // 
        $app->post('/{sale_id:[0-9]{0,11}}/add-to-invoice[/]', 'Controllers\Ventas\VentasController:PostAddToInvoice');
        


        //
        $app->post('/{sale_id:[0-9]{0,11}}/accept[/]', 'Controllers\Ventas\VentasController:PostAcceptSale');
        $app->post('/{sale_id:[0-9]{0,11}}/pay[/]', 'Controllers\Ventas\VentasController:PostPaySale');

        $app->post('/{sale_id:[0-9]{0,11}}/pay-square[/]', 'Controllers\Ventas\VentasController:PostPaySaleSquare');

        //
        $app->post('/add[/]', 'Controllers\Ventas\VentasController:PostAddSale');


    })->add(new AuthSessionAdmin("all"));










    /*
      * Tipos de Precios
      * */
    $app->group('/tipos-precios', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\TiposPrecios\TiposPreciosController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\TiposPrecios\TiposPreciosController:PaginateRecords');
        $app->get('/list', 'Controllers\TiposPrecios\TiposPreciosController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\TiposPrecios\TiposPreciosController:GetRecord');
        $app->post('[/]', 'Controllers\TiposPrecios\TiposPreciosController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\TiposPrecios\TiposPreciosController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\TiposPrecios\TiposPreciosController:DeleteRecord');

    })->add(new AuthSessionAdmin("all"));









    /*
     * Invoices  
     * */
    $app->group('/invoices', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Invoices\InvoicesController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Invoices\InvoicesController:PaginateRecords');
        $app->get('/list', 'Controllers\Invoices\InvoicesController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Invoices\InvoicesController:GetRecord');
        $app->post('[/]', 'Controllers\Invoices\InvoicesController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Invoices\InvoicesController:UpdateNotes');
        //$app->post('/{id:[0-9]{0,11}}/update[/]', 'Controllers\Invoices\InvoicesController:UpdateRecord');
        $app->post('/{id:[0-9]{0,11}}/update-comisiones[/]', 'Controllers\Invoices\InvoicesController:PostUpdateComisionesRecord');
        $app->post('/del[/]', 'Controllers\Invoices\InvoicesController:PostDeleteRecord');

        
        $app->post('/{invoice_id:[0-9]{0,11}}/update-status[/]', 'Controllers\Invoices\InvoicesController:PostUpdateStatus');


        //
        $app->post('/{invoice_id:[0-9]{0,11}}/send[/]', 'Controllers\Invoices\InvoicesController:PostSendInvoice');
        $app->get('/{invoice_id:[0-9]{0,11}}/send[/]', 'Controllers\Invoices\InvoicesController:PostSendInvoice');


        //
        $app->post('/send-email/{id:[0-9]{0,11}}/{type}[/]', 'Controllers\Invoices\InvoicesController:PostSendEmail');
        


    })->add(new AuthSessionAdmin("all"));





    



    /*
     * Customers
     * */
    $app->group('/customers', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Customers\CustomersController:ViewIndex');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Customers\CustomersController:ViewEdit');
        

        // api
        $app->get('[/]', 'Controllers\Customers\CustomersController:PaginateRecords');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersController:GetRecord');
        $app->post('[/]', 'Controllers\Customers\CustomersController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersController:UpdateRecord');
        $app->post('/{id:[0-9]{0,11}}/update-comisiones[/]', 'Controllers\Customers\CustomersController:PostUpdateComisionesRecord');
        $app->post('/del[/]', 'Controllers\Customers\CustomersController:PostDeleteRecord');

        //
        $app->post('/send-email/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersController:PostSendEmail');


        //
        $app->group('/{customer_id:[0-9]{0,11}}/relatives', function () use ($app) {
            //
            $app->get('[/]', 'Controllers\Customers\CustomersRelativesController:PaginateRecords');
            
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersRelativesController:GetRecord');
            //            
            $app->post('[/]', 'Controllers\Customers\CustomersRelativesController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersRelativesController:UpdateRecord');
            $app->post('/del[/]', 'Controllers\Customers\CustomersRelativesController:DeleteRecord');
        });


        // Expedientes Clínicos 
        $app->group('/{customer_id:[0-9]{0,11}}/clinical-records', function () use ($app) {
            
            // Vista principal
            $app->get('/index[/]', 'Controllers\Customers\ClinicalRecordsDentalController:ViewIndex');
            
            // API CRUD principal
            $app->get('[/]', 'Controllers\Customers\ClinicalRecordsDentalController:PaginateRecords');
            // paginate for relatives
            $app->get('/rel/{customer_person_id:[0-9]{0,11}}[/]', 'Controllers\Customers\ClinicalRecordsDentalController:PaginateRecords');

            //
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\ClinicalRecordsDentalController:GetRecord');
            $app->post('[/]', 'Controllers\Customers\ClinicalRecordsDentalController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\ClinicalRecordsDentalController:UpdateRecord');
            $app->post('/del[/]', 'Controllers\Customers\ClinicalRecordsDentalController:DeleteRecord');



            /**
             * 
             * Operaciones cliente o familiar 
             */
            //
            $app->get('/last[/]', 'Controllers\Customers\ClinicalRecordsDentalController:GetLastClinicalRecord');
            $app->get('/last/{customer_person_id:[0-9]{0,11}}[/]', 'Controllers\Customers\ClinicalRecordsDentalController:GetLastClinicalRecord');
            //
            $app->get('/print-last-expediente[/]', 'Controllers\Customers\ClinicalRecordsDentalController:GetPrintLastClinicalRecord');
            $app->get('/print-last-expediente/{customer_person_id:[0-9]{0,11}}[/]', 'Controllers\Customers\ClinicalRecordsDentalController:GetPrintLastClinicalRecord');
            //
            $app->post('/send-last-expediente[/]', 'Controllers\Customers\ClinicalRecordsDentalController:PostSendLastClinicalRecord');
            $app->post('/send-last-expediente/{customer_person_id:[0-9]{0,11}}[/]', 'Controllers\Customers\ClinicalRecordsDentalController:PostSendLastClinicalRecord');

        });
        


    })->add(new AuthSessionAdmin("all"));


    
    //
    $app->get('/customers/list', 'Controllers\Customers\CustomersController:GetAll')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/customers/search[/]', 'Controllers\Customers\CustomersController:GetSearch')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/customers/{customer_id:[0-9]{0,11}}/relatives/list[/]', 'Controllers\Customers\CustomersRelativesController:GetRelativesList')->add(new AuthSessionAdminLoggedOnly());
    //
    $app->get('/customers/{customer_id:[0-9]{0,11}}/services/search[/]', 'Controllers\Customers\CustomersController:GetSearchCustomerCitas')->add(new AuthSessionAdminLoggedOnly());





    /*
     * Contacts
     * */
    $app->group('/contacts', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Contacts\ContactsController:ViewIndex');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Contacts\ContactsController:ViewEdit');

        // api
        $app->get('[/]', 'Controllers\Contacts\ContactsController:PaginateRecords');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Contacts\ContactsController:GetRecord');
        $app->post('[/]', 'Controllers\Contacts\ContactsController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Contacts\ContactsController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Contacts\ContactsController:PostDeleteRecord');
        //
        $app->post('/send-email/{id:[0-9]{0,11}}[/]', 'Controllers\Contacts\ContactsController:PostSendEmail');


    })->add(new AuthSessionAdmin("all"));






    /*
     * Customer Account
     * */
    $app->group('/account', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Account\AccountController:ViewIndex');
        

        // api
        $app->get('[/]', 'Controllers\Account\AccountController:GetRecord');
        $app->post('[/]', 'Controllers\Account\AccountController:postUpdateBasicInfo');
        
        //
        $app->group('/apps', function () use ($app) {
            //
            $app->get('[/]', 'Controllers\Account\AccountAppsController:PaginateRecords');            
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Account\AccountAppsController:GetRecord');
            //            
            $app->post('[/]', 'Controllers\Account\AccountAppsController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Account\AccountAppsController:UpdateRecord');
            $app->post('/del[/]', 'Controllers\Account\AccountAppsController:DeleteRecord');
        });


    })->add(new AuthSessionAdmin("all"));






    /*
     * Departamentos
     * */
    $app->group('/departments', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Settings\DepartmentsController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Settings\DepartmentsController:PaginateRecords');
        $app->get('/list', 'Controllers\Settings\DepartmentsController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Settings\DepartmentsController:GetRecord');
        $app->post('[/]', 'Controllers\Settings\DepartmentsController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Settings\DepartmentsController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Settings\DepartmentsController:DeleteRecord');

    })->add(new AuthSessionAdmin("all"));

    
    
    /*
     * Tipos de Cambio
     * */
    $app->group('/exchange-rate', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Settings\ExchangeRateController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Settings\ExchangeRateController:PaginateRecords');
        $app->get('/list', 'Controllers\Settings\ExchangeRateController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Settings\ExchangeRateController:GetRecord');
        $app->post('[/]', 'Controllers\Settings\ExchangeRateController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Settings\ExchangeRateController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Settings\ExchangeRateController:DeleteRecord');

    })->add(new AuthSessionAdmin("all"));







    /*
     * Sale Status
     * */
    /*
    $app->group('/sale-status', function () use ($app) {

        $app->get('/list', 'Controllers\Catalogues\CatSalesStatusController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Catalogues\CatSalesStatusController:GetRecord');

    })->add(new AuthSessionAdmin("all"));
    */




    /*
     * Docs Manager
     * */
    /*
    $app->group('/docs-manager', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\DocsManager\FoldersController:ViewIndex');

        //
        $app->group('/folders', function () use ($app) {

            // api
            $app->get('[/]', 'Controllers\DocsManager\FoldersController:PaginateRecords');
            $app->get('/list', 'Controllers\DocsManager\FoldersController:GetAll');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\DocsManager\FoldersController:GetRecord');
            $app->post('[/]', 'Controllers\DocsManager\FoldersController:UpsertRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\DocsManager\FoldersController:UpsertRecord');
            $app->post('/del[/]', 'Controllers\DocsManager\FoldersController:DeleteRecord');


            //
            $app->group('/{folder_id:[0-9]{0,11}}/files', function () use ($app) {

                // api
                $app->get('[/]', 'Controllers\DocsManager\FoldersFilesController:GetAll');
                $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\DocsManager\FoldersFilesController:GetRecord');
                $app->post('[/]', 'Controllers\DocsManager\FoldersFilesController:UpsertRecord');
                $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\DocsManager\FoldersFilesController:UpsertRecord');
                $app->post('/del[/]', 'Controllers\DocsManager\FoldersFilesController:DeleteRecord');

            });


        });


    })->add(new AuthSessionAdmin("all"));
    */



    /*
     * Site Sections
     * */
    /*
    $app->group('/site', function () use ($app) {



        // Pages
        $app->group('/pages', function () use ($app) {

            // views
            $app->get('/index', 'Controllers\Site\SitePagesController:ViewIndex');

            // api
            $app->get('[/]', 'Controllers\Site\SitePagesController:PaginateRecords');
            $app->get('/list', 'Controllers\Site\SitePagesController:GetAll');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Site\SitePagesController:GetRecord');
            $app->post('[/]', 'Controllers\Site\SitePagesController:UpsertRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Site\SitePagesController:UpsertRecord');
            $app->post('/del[/]', 'Controllers\Site\SitePagesController:DeleteRecord');

        })->add(new AuthSessionAdmin("all"));



        // Layouts
        $app->group('/layouts', function () use ($app) {

            // api
            $app->get('[/]', 'Controllers\Site\SiteLayoutsController:PaginateRecords');
            $app->get('/list', 'Controllers\Site\SiteLayoutsController:GetAll');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Site\SiteLayoutsController:GetRecord');
            $app->post('[/]', 'Controllers\Site\SiteLayoutsController:UpsertRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Site\SiteLayoutsController:UpsertRecord');
            $app->post('/del[/]', 'Controllers\Site\SiteLayoutsController:DeleteRecord');

        })->add(new AuthSessionAdmin("all"));


        // Config
        $app->group('/config', function () use ($app) {

            // views
            $app->get('/index', 'Controllers\Site\SiteConfigController:ViewIndex');

            // api
            $app->get('[/]', 'Controllers\Site\SiteConfigController:GetRecord');
            $app->post('[/]', 'Controllers\Site\SiteConfigController:UpsertRecord');


        })->add(new AuthSessionAdmin("all"));


    });
    */








    /*
     * Catalogos del Sistema
     * */
    $app->group('/sys', function () use ($app) {




        //
        $app->get('/appointments-status[/]', 'Controllers\Sys\SysController:GetListAppointmentsStatus');


        $app->get('/pos-payments-methods[/]', 'Controllers\Sys\SysController:GetPOSPaymentsMethods');


        $app->get('/tipos-productos-servicios/list[/]', 'Controllers\Sys\SysController:GetTiposProductos');


        $app->get('/citas-status/list[/]', 'Controllers\Sys\SysController:GetRelativesList');



        /*
         * MENU SUPERIOR SOLO SUPER ADMIN
         * */
        //
        $app->get('/parent-models[/]', 'Controllers\Sys\SysController:GetParentModels');



        //
        $app->get('/apps/list[/]', 'Controllers\Sys\SysController:GetAppsTypes');



        /*
        * Ciudades
        * */
        $app->get('/states[/]', 'Controllers\Sys\SysController:GetEstados');
        $app->get('/states/{estado_id:[0-9]{0,11}}/cities[/]', 'Controllers\Sys\SysController:GetCities');




        $app->get('/relatives/list[/]', 'Controllers\Sys\SysController:GetRelativesList');

        
        /*
         * Titulos
         * */
        $app->group('/titulos', function () use ($app) {
            //
            $app->get('/list[/]', 'Controllers\Sys\SysController:GetTitulos');
        });





        /*
         * Maquetas
         * */
        $app->group('/maquetas', function () use ($app) {
            
            //
            $app->get('/tipos-documentos[/]', 'Controllers\Sys\SysController:GetMaquetasTiposDocumentos');
            $app->get('/tipos-correos[/]', 'Controllers\Sys\SysController:GetMaquetasTiposCorreos');

        });



        

        /*
        * Languages
        * */
        $app->group('/languages', function () use ($app) {

            // api
            $app->get('/list[/]', 'Controllers\Admin\SysController:GetLanguages');

        });




    })->add(new AuthSessionAdminLoggedOnly());







    /*
     * Historial de Cambios
     * */
    $app->group('/history-changes', function () use ($app) {

        // views
        $app->get('/index[/]', 'Controllers\Historial\HistorialCambiosController:Index');

        // api
        $app->get('[/]', 'Controllers\Historial\HistorialCambiosController:PaginateRecords');

        //
        $app->get('/salida/{salida_id:[0-9]{0,11}}[/]', 'Controllers\Historial\HistorialCambiosController:GetAllBySalida');



    })->add(new AuthSessionAdmin("all"));


    


    /*
    $app->group('/notifications', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Notifications\NotificationsController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Notifications\NotificationsController:PaginateRecords');
        $app->get('/list[/]', 'Controllers\Notifications\NotificationsController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Notifications\NotificationsController:GetRecord');
        $app->post('[/]', 'Controllers\Notifications\NotificationsController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Notifications\NotificationsController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Notifications\NotificationsController:DeleteRecord');


        //
        $app->group('/{id:[0-9]{0,11}}/dismissed', function () use ($app) {
            $app->get('[/]', 'Controllers\Notifications\NotificationsDismissedController:PaginateRecords');
        });

    })->add(new AuthSessionAdmin("all"));
    */





    /*
    $app->group('/sales', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Ventas\VentasController:ViewIndex');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Ventas\VentasController:ViewEdit');

        // api
        $app->get('[/]', 'Controllers\Ventas\VentasController:PaginateRecords');
        $app->get('/list', 'Controllers\Ventas\VentasController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Ventas\VentasController:GetRecord');
        $app->post('/del[/]', 'Controllers\Ventas\VentasController:DeleteRecord');
        $app->post('/{id:[0-9]{0,11}}/update-kitchen[/]', 'Controllers\Ventas\VentasController:PostUpdateKitchen');
        //
        $app->get('/report[/]', 'Controllers\Ventas\VentasController:GetDownloadReport');

        //
        $app->get('/{id:[0-9]{0,11}}/pp-preview[/]', 'Controllers\Ventas\VentasController:GetPreviewPhitPhuel');
        $app->get('/{id:[0-9]{0,11}}/ct-preview[/]', 'Controllers\Ventas\VentasController:GetPreviewConexTubi');


        //
        $app->post('/bulk-update-status[/]', 'Controllers\Ventas\VentasController:PostUpdateBulkStatus');

        //
        $app->group('/{id:[0-9]{0,11}}/status', function () use ($app) {
            $app->get('[/]', 'Controllers\Ventas\VentaStatusController:PaginateRecords');
            $app->post('/update[/]', 'Controllers\Ventas\VentaStatusController:PostUpdateStatus');
        });


        //
        $app->group('/{id:[0-9]{0,11}}/msgs', function () use ($app) {
            $app->get('[/]', 'Controllers\Ventas\VentaNotificationsController:PaginateRecords');
            $app->post('[/]', 'Controllers\Ventas\VentaNotificationsController:PostSendMsg');
        });


    })->add(new AuthSessionAdmin("all"));
    */




    //    
    include PATH_CONTROLLERS.DS."Buses".DS."RouteBuses.php";




    /*
    * Cat Blog Categories
    * */
    /*
    $app->group('/blog-categories', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Catalogues\CatBlogCategoriesController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Catalogues\CatBlogCategoriesController:PaginateRecords');
        $app->get('/list[/]', 'Controllers\Catalogues\CatBlogCategoriesController:GetAll');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Catalogues\CatBlogCategoriesController:GetRecord');
        $app->post('[/]', 'Controllers\Catalogues\CatBlogCategoriesController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Catalogues\CatBlogCategoriesController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Catalogues\CatBlogCategoriesController:DeleteRecord');


    })->add(new AuthSessionAdmin("all"));
    */


    
    /*
     * Blog
     * */
    /*
    $app->group('/blog', function () use ($app) {

        // Posts
        $app->group('/posts', function () use ($app) {

            // views
            $app->get('/index', 'Controllers\Blog\PostsController:ViewIndex');
            $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Blog\PostsController:ViewEdit');

            // api
            $app->get('[/]', 'Controllers\Blog\PostsController:PaginateRecords');
            $app->get('/list', 'Controllers\Blog\PostsController:GetAll');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Blog\PostsController:GetRecord');
            $app->post('[/]', 'Controllers\Blog\PostsController:UpsertRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Blog\PostsController:UpsertRecord');
            //
            $app->post('/del[/]', 'Controllers\Blog\PostsController:DeleteRecord');

            //
            $app->group('/{id:[0-9]{0,11}}/visits', function () use ($app) {
                $app->get('[/]', 'Controllers\Blog\PostsVisitsController:PaginateRecords');
            });

        })->add(new AuthSessionAdmin("all"));
    });
    */







    /*
    * Maquetas de Mensajes
    * */
    $app->group('/templates-messages', function () use ($app) {



        // views
        $app->get('/index', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:PaginateRecords');
        $app->get('/{maqueta_id:[0-9]{0,11}}/maqueta-info[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:GetMaquetaCount');
        $app->get('/list[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:GetAll');
        $app->get('/type/{maqueta_id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:GetAllByType');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:GetRecord');
        $app->post('[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:UpsertRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:UpsertRecord');
        $app->post('/del[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesController:DeleteRecord');




        /*
            * Mensajes -> Copias de Telefonos
            * */
        $app->group('/{mensaje_id:[0-9]{0,11}}/copias-phones', function () use ($app) {
            // api
            $app->get('[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasPhonesController:PaginateRecords');
            $app->get('/list[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasPhonesController:GetAll');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasPhonesController:GetRecord');
            $app->post('[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasPhonesController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasPhonesController:UpdateRecord');
            $app->post('/del[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasPhonesController:DeleteRecord');
        });




        /*
            * Mensajes -> Copias de Correos
            * */
        $app->group('/{mensaje_id:[0-9]{0,11}}/copias-emails', function () use ($app) {
            // api
            $app->get('[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasEmailsController:PaginateRecords');
            $app->get('/list[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasEmailsController:GetAll');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasEmailsController:GetRecord');
            $app->post('[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasEmailsController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasEmailsController:UpdateRecord');
            $app->post('/del[/]', 'Controllers\Maquetas\MaquetasMensajes\MaquetasMensajesCopiasEmailsController:DeleteRecord');
        });



    })->add(new AuthSessionAdmin("all"));




    /*
    * Maquetas de Documentos
    * */
    $app->group('/templates-documents', function () use ($app) {



        // views
        $app->get('/index', 'Controllers\Maquetas\MaquetasDocumentosController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Maquetas\MaquetasDocumentosController:PaginateRecords');
        $app->get('/{tipo_documento_id:[0-9]{0,11}}/maqueta-info[/]', 'Controllers\Maquetas\MaquetasDocumentosController:GetMaquetaCount');
        $app->get('/list[/]', 'Controllers\Maquetas\MaquetasDocumentosController:GetAll');
        $app->get('/type/{tipo_documento_id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasDocumentosController:GetAllByType');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasDocumentosController:GetRecord');
        $app->post('[/]', 'Controllers\Maquetas\MaquetasDocumentosController:UpsertRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Maquetas\MaquetasDocumentosController:UpsertRecord');
        $app->post('/del[/]', 'Controllers\Maquetas\MaquetasDocumentosController:DeleteRecord');



    })->add(new AuthSessionAdmin("all"));







 

    /*
    * Configuracion de Correo
    * */
    $app->group('/mail-config', function () use ($app) {

        // view
        $app->get('/index', 'Controllers\MailConfig\MailConfigController:ViewIndex');

        //
        $app->get('[/]', 'Controllers\MailConfig\MailConfigController:GetRecord');
        $app->post('[/]', 'Controllers\MailConfig\MailConfigController:UpsertRecord');

        //
        $app->post('/send-test[/]', 'Controllers\MailConfig\MailConfigController:PostSendEmailTest');

    })->add(new AuthSessionAdmin("all"));




    /*
   * Configuracion
   * */
    $app->group('/config', function () use ($app) {



        /*
        * Configuracion Twilio
        * */
        $app->group('/twilio', function () use ($app) {

            // view
            $app->get('/index', 'Controllers\Config\ConfigTwilio\ConfigTwilioController:ViewIndex');

            //
            $app->get('[/]', 'Controllers\Config\ConfigTwilio\ConfigTwilioController:GetRecord');
            $app->post('[/]', 'Controllers\Config\ConfigTwilio\ConfigTwilioController:UpdateRecord');

            //
            $app->post('/send-test[/]', 'Controllers\Config\ConfigTwilio\ConfigTwilioController:PostSendSMS');

        })->add(new AuthSessionAdmin("all"));




         /*
        * Configuracion Quickbooks
        * */
        $app->group('/quickbooks', function () use ($app) {

            // view
            $app->get('/index', 'Controllers\Config\ConfigQBO\ConfigQBOController:ViewIndex');

            //
            $app->get('[/]', 'Controllers\Config\ConfigQBO\ConfigQBOController:GetRecord');
            $app->post('[/]', 'Controllers\Config\ConfigQBO\ConfigQBOController:UpdateRecord');

            //
            $app->get('/auth-url[/]', 'Controllers\Config\ConfigQBO\ConfigQBOController:getAuthUrl');
            $app->post('/revoke[/]', 'Controllers\Config\ConfigQBO\ConfigQBOController:postRevokeToken');
            $app->post('/sync-customers[/]', 'Controllers\Config\ConfigQBO\ConfigQBOController:postSyncCustomers');
            $app->post('/sync-sales[/]', 'Controllers\Config\ConfigQBO\ConfigQBOController:postSyncSales');

            //
            $app->post('/send-test[/]', 'Controllers\Config\ConfigQBO\ConfigQBOController:PostSendSMS');

        })->add(new AuthSessionAdmin("all"));




        /*
        * Configuracion Stripe
        * */
        $app->group('/stripe', function () use ($app) {

            // view
            $app->get('/index', 'Controllers\Config\ConfigStripe\ConfigStripeController:ViewIndex');

            //
            $app->get('[/]', 'Controllers\Config\ConfigStripe\ConfigStripeController:GetRecord');
            $app->post('[/]', 'Controllers\Config\ConfigStripe\ConfigStripeController:UpdateRecord');

        })->add(new AuthSessionAdmin("all"));



        /*
        * Configuracion Square
        * */
        $app->group('/square', function () use ($app) {

            // view
            $app->get('/index', 'Controllers\Config\ConfigSquare\ConfigSquareController:ViewIndex');

            //
            $app->get('[/]', 'Controllers\Config\ConfigSquare\ConfigSquareController:GetRecord');
            $app->post('[/]', 'Controllers\Config\ConfigSquare\ConfigSquareController:UpdateRecord');

        })->add(new AuthSessionAdmin("all"));




        /*
        * Configuracion PayPal
        * */
        $app->group('/paypal', function () use ($app) {

            // view
            $app->get('/index', 'Controllers\Config\ConfigPayPal\ConfigPayPalController:ViewIndex');

            //
            $app->get('[/]', 'Controllers\Config\ConfigPayPal\ConfigPayPalController:GetRecord');
            $app->post('[/]', 'Controllers\Config\ConfigPayPal\ConfigPayPalController:UpdateRecord');

        })->add(new AuthSessionAdmin("all"));



        /*
        * Configuracion AuthorizeNet
        * */
        $app->group('/authorizenet', function () use ($app) {

            // view
            $app->get('/index', 'Controllers\Config\ConfigAuthorizeNet\ConfigAuthorizeNetController:ViewIndex');

            //
            $app->get('[/]', 'Controllers\Config\ConfigAuthorizeNet\ConfigAuthorizeNetController:GetRecord');
            $app->post('[/]', 'Controllers\Config\ConfigAuthorizeNet\ConfigAuthorizeNetController:UpdateRecord');

        })->add(new AuthSessionAdmin("all"));



        /*
        * Configuracion CLABE Interbancaria
        * */
        $app->group('/bank-key', function () use ($app) {

            // view
            $app->get('/index', 'Controllers\Config\ConfigClabe\ConfigClabeController:ViewIndex');

            //
            $app->get('[/]', 'Controllers\Config\ConfigClabe\ConfigClabeController:GetRecord');
            $app->post('[/]', 'Controllers\Config\ConfigClabe\ConfigClabeController:UpdateRecord');

        })->add(new AuthSessionAdmin("all"));



    })->add(new AuthSessionAdmin("all"));











    /*
    * Promociones
    * */
    $app->group('/promos', function () use ($app) {

        // views
        $app->get('/index', 'Controllers\Promociones\PromocionesController:ViewIndex');

        // api
        $app->get('[/]', 'Controllers\Promociones\PromocionesController:PaginateRecords');        
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Promociones\PromocionesController:GetRecord');
        $app->post('[/]', 'Controllers\Promociones\PromocionesController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Promociones\PromocionesController:UpdateRecord');
        $app->post('/del[/]', 'Controllers\Promociones\PromocionesController:DeleteRecord');


    })->add(new AuthSessionAdmin("all"));

    //
    $app->get('/promos/search[/]', 'Controllers\Promociones\PromocionesController:GetForPOSTotals')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/promos/list-available[/]', 'Controllers\Promociones\PromocionesController:GetAllAvailable')->add(new AuthSessionAdminLoggedOnly());





    /*
    * Citas 
    * */
    $app->group('/appointments', function () use ($app) {




        /*
        * Recursos
        * */
        $app->group('/resources', function () use ($app) {

            // views
            $app->get('/index', 'Controllers\Appointments\ResourcesController:ViewIndex');
            $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Appointments\ResourcesController:ViewEdit');

            // api
            $app->get('[/]', 'Controllers\Appointments\ResourcesController:PaginateRecords');

            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Appointments\ResourcesController:GetRecord');
            $app->post('[/]', 'Controllers\Appointments\ResourcesController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Appointments\ResourcesController:EditRecord');
            $app->post('/del[/]', 'Controllers\Appointments\ResourcesController:DeleteRecord');

            //
            $app->group('/{resource_id:[0-9]{0,11}}/services', function () use ($app) {
                $app->get('[/]', 'Controllers\Appointments\ResourcesServicesController:PaginateRecords');
                $app->post('[/]', 'Controllers\Appointments\ResourcesServicesController:AddRecord');
                $app->post('/del[/]', 'Controllers\Appointments\ResourcesServicesController:DeleteRecord');
            });
            
            //
            $app->group('/{resource_id:[0-9]{0,11}}/schedule-exceptions', function () use ($app) {
                $app->get('[/]', 'Controllers\Appointments\ResourcesScheduleExceptionsController:PaginateRecords');
                $app->post('[/]', 'Controllers\Appointments\ResourcesScheduleExceptionsController:AddRecord');
                $app->post('/del[/]', 'Controllers\Appointments\ResourcesScheduleExceptionsController:DeleteRecord');
            });
            

        });

        

        // views 
        $app->get('/index', 'Controllers\Appointments\AppointmentsController:ViewIndex');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Appointments\AppointmentsController:ViewEdit');

        // View List 
        $app->get('/list/index', 'Controllers\Appointments\AppointmentsController:ViewAppointmentsList');

        //
        $app->get('[/]', 'Controllers\Appointments\AppointmentsController:PaginateRecords');


        // initialice resources
        $app->get('/res-init[/]', 'Controllers\Appointments\AppointmentsController:GetInitialResources');
        
        // get resources info and availability for all or for single resource
        $app->post('/{location_id:[0-9]{0,11}}/resources[/]', 'Controllers\Appointments\AppointmentsController:PostGetLocationResources');
        $app->post('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}[/]', 'Controllers\Appointments\AppointmentsController:PostGetLocationResources');
        
        // agregar y ditar evento 
        $app->post('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}/add[/]', 'Controllers\Appointments\AppointmentsController:AddAppointment');
        $app->post('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}/event/{appointment_id:[0-9]{0,11}}[/]', 'Controllers\Appointments\AppointmentsController:EditAppointment');
        $app->post('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}/event/{appointment_id:[0-9]{0,11}}/update-status[/]', 'Controllers\Appointments\AppointmentsController:PostUpdateAdminAppointmentStatus');
        
        // get events by resources ids
        $app->post('/{location_id:[0-9]{0,11}}/resources/events[/]', 'Controllers\Appointments\AppointmentsController:PostGetLocationResourcesEvents');

        // event info for view & edit
        $app->get('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}/events/{appointment_id:[0-9]{0,11}}[/]', 'Controllers\Appointments\AppointmentsController:GetEventInfo');
        $app->get('/{appointment_id:[0-9]{0,11}}/event[/]', 'Controllers\Appointments\AppointmentsController:GetEventInfoWithAppointmentIdOnly');


    })->add(new AuthSessionAdmin("all"));

    // 
    $app->get('/appointments/resources/list', 'Controllers\Appointments\ResourcesController:GetAll')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/appointments/resources/{location_id:[0-9]{0,11}}/list-available[/]', 'Controllers\Appointments\ResourcesController:GetAllAvailable')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/appointments/resources/{resource_id:[0-9]{0,11}}/services/list[/]', 'Controllers\Appointments\ResourcesServicesController:GetResourceServicesList')->add(new AuthSessionAdminLoggedOnly());
    



    

    /*
    * Ubicaciones / Sucursales
    * */
    $app->group('/locations', function () use ($app) {

        //
        $app->get('/list[/]', 'Controllers\Ubicaciones\UbicacionesController:GetAll');
        $app->get('/list-available[/]', 'Controllers\Ubicaciones\UbicacionesController:GetAllAvailable');
        //$app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Ubicaciones\UbicacionesController:GetRecord');


    })->add(new AuthSessionAdminLoggedOnly());






    /*
    * Point of Sale 
    * */
    $app->group('/pos', function () use ($app) {

        
        // views
        $app->get('/index', 'Controllers\Pos\PosMainController:ViewIndex');
        
        

        /**
         *  
         * Main POS Section
         */
        $app->group('/main', function () use ($app) {
            //
            $app->get('[/]', 'Controllers\Pos\PosMainController:ViewMain');
            //
            $app->post('/add[/]', 'Controllers\Pos\PosMainController:PostAddSale');

            $app->get('/{sale_id:[0-9]{0,11}}/ticket[/]', 'Controllers\Pos\PosMainController:ViewTicketHtml');

            //
            $app->post('/open-register[/]', 'Controllers\Pos\PosMainController:PostOpenRegister');
            $app->post('/validate-user[/]', 'Controllers\Pos\PosMainController:PostValidateUser');
            $app->post('/update-register-user[/]', 'Controllers\Pos\PosMainController:PostUpdatePosRegisterUser');
            $app->post('/continue[/]', 'Controllers\Pos\PosMainController:PostContinueSale');
            $app->post('/close-register[/]', 'Controllers\Pos\PosMainController:PostCloseRegister');
        });

        

        /*
        * Listado de Cajas
        * */
        $app->group('/registers', function () use ($app) {

            // views
            $app->get('/index', 'Controllers\Pos\PosRegistersController:ViewIndex');
            $app->get('/{id:[0-9]{0,11}}/view[/]', 'Controllers\Pos\PosRegistersController:ViewCaja');

            // api
            $app->get('[/]', 'Controllers\Pos\PosRegistersController:PaginateRecords');
            $app->get('/report-xls[/]', 'Controllers\Pos\PosRegistersController:GetPosRegistersReportXls');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Pos\PosRegistersController:GetRecord');

            //
            $app->get('/{pos_register_id:(?:all|[0-9]+)}/users/search[/]', 'Controllers\pos\PosMainController:GetSearchPosRegistersSalesUsers')->add(new AuthSessionAdminLoggedOnly());

            //
            $app->get('/{pos_register_id:[0-9]{0,11}}/sales[/]', 'Controllers\Pos\PosRegistersController:PaginateRegisterSales');
            
        });



        /*
        * Listado de Ventas
        * */
        $app->group('/sales', function () use ($app) {

            // views
            $app->get('/index', 'Controllers\Pos\PosSalesController:ViewIndex');

            //
            $app->get('/report-xls[/]', 'Controllers\Pos\PosSalesController:GetSalesReportXls');
            $app->get('/report-comissions-xls[/]', 'Controllers\Pos\PosSalesController:GetComissionsReportXls');

            // api
            $app->get('[/]', 'Controllers\Pos\PosSalesController:PaginateRecords');
            
        });



        /*
        * Lista de puntos de venta disponibles 
        * */
        $app->group('/list', function () use ($app) {

            // views
            $app->get('/index', 'Controllers\Pos\PosController:ViewIndex');
            $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Pos\PosController:ViewEdit');

            $app->get('/users/search[/]', 'Controllers\pos\PosMainController:GetSearchPosRegistersUsers')->add(new AuthSessionAdminLoggedOnly());

            // api
            $app->get('[/]', 'Controllers\Pos\PosController:PaginateRecords');     
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Pos\PosController:GetRecord');
            $app->post('[/]', 'Controllers\Pos\PosController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Pos\PosController:EditRecord');
            $app->post('/del[/]', 'Controllers\Pos\PosController:DeleteRecord');
        });
        

    })->add(new AuthSessionAdmin("all"));

    //
    $app->get('/pos/users[/]', 'Controllers\pos\PosMainController:GetPosAvailableUsers')->add(new AuthSessionAdminLoggedOnly());    
    $app->get('/pos/list-avail[/]', 'Controllers\Pos\PosMainController:GetPosListAvailableItems')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/pos/items[/]', 'Controllers\Pos\PosMainController:GetPosListAllItems')->add(new AuthSessionAdminLoggedOnly());

    $app->get('/pos/config[/]', 'Controllers\Pos\PosMainController:GetPosConfig')->add(new AuthSessionAdminLoggedOnly());

    //
    $app->get('/pos/list/{pos_id:(?:all|[0-9]+)}/{start_date:[0-9]{4}-[0-9]{2}-[0-9]{2}}/{end_date:[0-9]{4}-[0-9]{2}-[0-9]{2}}/get-registers[/]', 'Controllers\Pos\PosRegistersController:GetPosRegistersListByDate')->add(new AuthSessionAdminLoggedOnly());


    




    //
    $app->group('/working-hours', function () use ($app) {

        //
        $app->get('/index', 'Controllers\Horarios\WorkingHoursController:ViewIndex')->add(new AuthSessionAdmin("all"));
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Horarios\WorkingHoursController:ViewEdit')->add(new AuthSessionAdmin("all"));
        
        //        
        $app->get('[/]', 'Controllers\Horarios\WorkingHoursController:PaginateRecords')->add(new AuthSessionAdmin("all"));
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Horarios\WorkingHoursController:GetRecord')->add(new AuthSessionAdmin("all"));
        $app->post('[/]', 'Controllers\Horarios\WorkingHoursController:AddRecord')->add(new AuthSessionAdmin("all"));
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Horarios\WorkingHoursController:EditRecord')->add(new AuthSessionAdmin("all"));
        $app->post('/del[/]', 'Controllers\Horarios\WorkingHoursController:DeleteRecord')->add(new AuthSessionAdmin("all"));



        //
        $app->group('/schedule-exceptions', function () use ($app) {
            
            //
            $app->get('/index', 'Controllers\Horarios\ScheduleExceptionsController:ViewIndex');
            
            //
            $app->get('[/]', 'Controllers\Horarios\ScheduleExceptionsController:PaginateRecords');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Horarios\ScheduleExceptionsController:GetRecord');
            $app->post('[/]', 'Controllers\Horarios\ScheduleExceptionsController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Horarios\ScheduleExceptionsController:EditRecord');
            $app->post('/del[/]', 'Controllers\Horarios\ScheduleExceptionsController:DeleteRecord');

        })->add(new AuthSessionAdmin("all"));


        //
        $app->group('/{working_hours_id:[0-9]{0,11}}/items', function () use ($app) {

            //   
            $app->get('[/]', 'Controllers\Horarios\WorkingHoursItemsController:PaginateRecords');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Horarios\WorkingHoursItemsController:GetRecord');
            $app->post('[/]', 'Controllers\Horarios\WorkingHoursItemsController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Horarios\WorkingHoursItemsController:EditRecord');
            $app->post('/del[/]', 'Controllers\Horarios\WorkingHoursItemsController:PostDeleteRecord');

        })->add(new AuthSessionAdmin("all"));


    });


    //
    $app->get('/working-hours/schedule-exceptions/list-available[/]', 'Controllers\Horarios\ScheduleExceptionsController:GetAllAvailable')->add(new AuthSessionAdminLoggedOnly());
    $app->get('/working-hours/list-available[/]', 'Controllers\Horarios\WorkingHoursController:GetAllAvailable')->add(new AuthSessionAdminLoggedOnly());



    



    /*
     * Sucursales
     * */
    $app->group('/sucursales', function () use ($app) {


        // views
        $app->get('/index', 'Controllers\Sucursales\SucursalesController:Index');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Sucursales\SucursalesController:Edit');


        // api
        $app->get('[/]', 'Controllers\Sucursales\SucursalesController:PaginateRecords');
        $app->get('/list', 'Controllers\Sucursales\SucursalesController:GetAll');
        $app->get('/list-kitchens', 'Controllers\Sucursales\SucursalesController:GetAllKitchens');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Sucursales\SucursalesController:GetRecord');
        //
        $app->post('[/]', 'Controllers\Sucursales\SucursalesController:AddRecord');
        $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Sucursales\SucursalesController:UpdateRecord');
        $app->post('/{id:[0-9]{0,11}}/update-cfdi-info[/]', 'Controllers\Sucursales\SucursalesController:UpdateCFDIData');
        $app->post('/del[/]', 'Controllers\Sucursales\SucursalesController:DeleteRecord');



        //
        $app->group('/{sucursal_id:[0-9]{0,11}}/schedule', function () use ($app) {
            $app->get('[/]', 'Controllers\Sucursales\SucursalesScheduleController:getScheduleInfo');
            $app->post('[/]', 'Controllers\Sucursales\SucursalesScheduleController:postUpdateScheduleInfo');
        });


        //
        $app->group('/{sucursal_id:[0-9]{0,11}}/schedule-exceptions', function () use ($app) {
            $app->get('[/]', 'Controllers\Sucursales\SucursalesScheduleExceptionsController:PaginateRecords');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Sucursales\SucursalesScheduleExceptionsController:GetRecord');
            $app->post('[/]', 'Controllers\Sucursales\SucursalesScheduleExceptionsController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Sucursales\SucursalesScheduleExceptionsController:EditRecord');
            $app->post('/del[/]', 'Controllers\Sucursales\SucursalesScheduleExceptionsController:DeleteRecord');
        });



    })->add(new AuthSessionAdmin("all"));


});


