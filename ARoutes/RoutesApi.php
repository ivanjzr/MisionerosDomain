<?php

use Middleware\SiteAccountHandler;
use Middleware\AuthApiAll;
use Middleware\AuthApiStore;
use Middleware\AuthApiCustomer;


/*
 *
 * API V1
 *
 * */
$app->group('/api', function () use ($app) {




    $app->get('/ping', function ($request, $response, $args) use ($app) {
        $response->getBody()->write('conn Ok');
        return $response->withStatus(200);
    });


    /*
     *
     * PUBLIC ENDPOINTS
     *
     * */
    $app->group('/public', function () use ($app) {



        // 
        $app->get('/paises/list[/]', 'Controllers\Sys\SysController:GetPaises');
        $app->get('/relatives/list[/]', 'Controllers\Sys\SysController:GetRelativesList');

        //
        $app->get('/categories/list[/]', 'Controllers\Sys\SysController:GetCategoriesList');

        //
        $app->group('/companies', function () use ($app) {
            //
            $app->get('/{company_type_id:[0-9]{0,11}}[/]', 'Controllers\Companies\CompaniesController:GetListByType');
            $app->get('/list-types[/]', 'Controllers\Sys\SysController:GetCompaniesTypes');
        });



        //
        $app->get('/marcas/list[/]', 'Controllers\Sys\SysController:GetMarcas');
        $app->get('/modelos/list[/]', 'Controllers\Sys\SysController:GetModelos');
        $app->get('/submarcas/list[/]', 'Controllers\Sys\SysController:GetSubMarcas');
        $app->get('/cilindros/list[/]', 'Controllers\Sys\SysController:GetCilindros');



    });









    /*
     * CUSTOMERS/STORES AUTH OPERATIONS
     * */
    $app->group('/auth', function () use ($app) {

        //
        $app->post('/register-customer[/]', 'Controllers\Api\AuthController:PostRegisterCustomer');
        //
        $app->post('/activate[/]', 'Controllers\Api\AuthController:ActivateAccount');
        //
        $app->post('/req-ac[/]', 'Controllers\Api\AuthController:PostAuthRequestActivationCode');
        //
        $app->post('/recover-activate[/]', 'Controllers\Api\AuthController:PostRecoverActivate');
        $app->post('/recover-update-pwd[/]', 'Controllers\Api\AuthController:PostAuthUpdatePassword');
        //
        $app->post('/login[/]', 'Controllers\Api\AuthController:PostLogin');
        $app->post('/logout[/]', 'Controllers\Api\AuthController:PostLogout')->add(new AuthApiCustomer());

        
        //
        $app->get('/cust-state[/]', 'Controllers\Customers\CustomersController:GetCustomerState');
        $app->post('/l[/]', 'Controllers\Api\AuthController:PostGetUserLocationInfo');


    })->add(new SiteAccountHandler());






    $app->get('/store/pid/{place_id}[/]', 'Controllers\Stores\StoresController:GetStoreByPlaceId');



    //
    $app->group('/stores/{store_id:[0-9]{0,11}}', function () use ($app) {

        //
        $app->get('[/]', 'Controllers\Stores\StoresController:GetPublicStoreById');

        //
        $app->group('/coupons', function () use ($app) {
            //
            $app->get('/s[/]', 'Controllers\Stores\StoresCouponsController:PaginatePublicScrollRecords');
            $app->get('/list-active[/]', 'Controllers\Stores\StoresCouponsController:GetPublicListActive');
        });

    });






    // search-details
    $app->post('/search-details[/]', 'Controllers\Coupons\CouponsController:GetSearchDetails');
    $app->post('/company-info[/]', 'Controllers\Coupons\CouponsController:getCompanyInfo');



    //
    $app->group('/coupons', function () use ($app) {

        //
        $app->get('/search/{latitude}/{longitude}[/]', 'Controllers\Coupons\CouponsController:GetPublicSearch');
        $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Coupons\CouponsController:GetCouponById');

    })->add(new AuthApiCustomer(true));









    /*
     *
     * COMMON LOGGED
     *
     * */
    $app->post('/choose-plan[/]', 'Controllers\Ventas\VentasController:PostCreatePlan')->add(new AuthApiAll());
    //
    $app->get('/list-jobs[/]', 'Controllers\Companies\CompaniesJobsController:GetAll')->add(new AuthApiAll());
    //
    $app->get('/sq-conf[/]', 'Controllers\Config\ConfigSquare\ConfigSquareController:getConfig')->add(new AuthApiAll());








    /*
     * Subscriptions
     * */
    //
    $app->get('/list-subscriptions[/]', 'Controllers\Products\SubscriptionsController:PaginateScrollRecords')->add(new AuthApiAll());
    $app->get('/get-discounts[/]', 'Controllers\Products\SubscriptionsController:GetDiscounts')->add(new AuthApiAll());
    //
    $app->get('/pconfig[/]', 'Controllers\Api\AuthController:getPaymentConfig')->add(new AuthApiAll());

    /*
     * Pay Stripe
     * */
    $app->group('/strp', function () use ($app) {
        //
        $app->post('/payment-intent[/]', 'Controllers\Ventas\VentasController:PostGetPaymentIntent');
        
    })->add(new AuthApiAll());


    //
    $app->post('/pay-stripe[/]', 'Controllers\Ventas\VentasController:PostPayStripe')->add(new AuthApiAll());
    $app->post('/pay-anet[/]', 'Controllers\Ventas\VentasController:PostPayAuthorizenet')->add(new AuthApiAll());





    // Post Send Message
    $app->post('/ap-upload-img[/]', 'Controllers\ChatMessages\UsersChatMessages:postUploadImages')->add(new AuthApiAll());
    $app->post('/ap-send-msg[/]', 'Controllers\ChatMessages\UsersChatMessages:postSendMessage')->add(new AuthApiAll());




    $app->group('/sales', function () use ($app) {

        //
        $app->get('/{sale_code:[0-9]{0,11}}[/]', 'Controllers\Ventas\VentasController:GetPublicCustomerSaleByCode');
        $app->post('/{sale_code:[0-9]{0,11}}/confirm[/]', 'Controllers\Ventas\VentasController:PostConfirmSaleByCode');

        //$app->get('/sales/{sale_code:[0-9]{0,11}}/paginate[/]', 'Controllers\Ventas\VentasController:PaginatePublicSaleOcupacion');
        //$app->get('/sales/{sale_code:[0-9]{0,11}}/confirm[/]', 'Controllers\Ventas\VentasController:ViewConfirmSale');
        

    })->add(new SiteAccountHandler());





    /*
     *
     * LOGGED CUSTOMER SPECIFIC
     *
     * */
    $app->group('/customer', function () use ($app) {




        //
        $app->get('/ud[/]', 'Controllers\Api\AuthController:GetCheckValidToken');


        /*
         * EXPO PUSH NOTIFICATIONS PARA CLIENTES
         * */
        $app->group('/epn', function () use ($app) {
            $app->post('/reg-token[/]', 'Controllers\EPN\EPNController:PostUpdatePushTokenForCustomer');
            $app->post('/send-msg[/]', 'Controllers\EPN\EPNController:PostSendPushForCustomer');
        });





        // Customer operations
        $app->get('[/]', 'Controllers\Customers\CustomersController:GetCurrentUser');
        $app->post('/basic[/]', 'Controllers\Customers\CustomersController:postUpdateBasicInfo');
        //
        $app->post('/pwd[/]', 'Controllers\Customers\CustomersController:PostEditCurrentPassword');
        $app->post('/update-img[/]', 'Controllers\Customers\CustomersController:PostUpdateCustomerImage');
        $app->post('/del-img[/]', 'Controllers\Customers\CustomersController:PostDeleteCustomerImage');




        //
        $app->group('/relatives', function () use ($app) {
            //
            $app->get('/pr[/]', 'Controllers\Customers\CustomersRelativesController:PaginateRecordsPrimeReact');
            $app->get('/list[/]', 'Controllers\Customers\CustomersRelativesController:GetRelativesList');
            $app->post('[/]', 'Controllers\Customers\CustomersRelativesController:AddRecord');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersRelativesController:UpdateRecord');
            $app->post('/del[/]', 'Controllers\Customers\CustomersRelativesController:DeleteRecord');
        });



        //
        $app->group('/sales', function () use ($app) {
            
            //            
            $app->get('[/]', 'Controllers\Ventas\VentasController:PaginateRecordsForCustomer');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Ventas\VentasController:GetCustomerSale');

            //
            $app->post('/{sale_id:[0-9]{0,11}}/confirm[/]', 'Controllers\Ventas\VentasController:PostConfirmSaleById');

        });



        //
        $app->group('/orders', function () use ($app) {
            //
            $app->get('[/]', 'Controllers\Customers\CustomersOrdersController:PaginateRecords');
            $app->get('/s[/]', 'Controllers\Customers\CustomersOrdersController:PaginateScrollRecords');
            $app->get('/pr[/]', 'Controllers\Customers\CustomersOrdersController:PaginateRecordsPrimeReact');
            $app->get('/list-stores[/]', 'Controllers\Customers\CustomersOrdersController:GetListStores');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersOrdersController:GetRecordById');

            /** LA ORDEN SE PIDE DESDE SOCKET MSG, ESTE ES DE TESTING SOLAMENTE */
            //$app->post('[/]', 'Controllers\Customers\CustomersOrdersController:PostRequestOrder');

        });



        //
        $app->group('/parts-requests', function () use ($app) {


            //
            $app->get('[/]', 'Controllers\Customers\CustomersPartsRequestsController:PaginateScrollRecords');
            $app->get('/pr[/]', 'Controllers\Customers\CustomersPartsRequestsController:PaginateRecordsPrimeReact');
            $app->get('/list-stores[/]', 'Controllers\Customers\CustomersPartsRequestsController:GetListStores');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersPartsRequestsController:GetRecordById');


            /** LA ORDEN SE PIDE DESDE SOCKET MSG, ESTE ES DE TESTING SOLAMENTE */
            //
            $app->post('[/]', 'Controllers\Customers\CustomersPartsRequestsController:postRequestPart');

            //
            $app->group('/{part_request_id:[0-9]{0,11}}/responses', function () use ($app) {
                //
                $app->get('/{deal_with_store_id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersPartsRequestsResponsesController:GetStorePartRequestForCustomer');
                $app->get('[/]', 'Controllers\Customers\CustomersPartsRequestsResponsesController:PaginateScrollRecords');
            });

        });








        //
        $app->group('/coupons', function () use ($app) {
            //
            $app->get('[/]', 'Controllers\Customers\CustomersCouponsController:PaginateRecords');
            $app->get('/s[/]', 'Controllers\Customers\CustomersCouponsController:PaginateScrollRecords');
            $app->get('/pr[/]', 'Controllers\Customers\CustomersCouponsController:PaginateScrollRecordsPrimeReact');
            $app->get('/list-stores[/]', 'Controllers\Customers\CustomersCouponsController:GetListStores');
            $app->post('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersCouponsController:PostCutCoupon');
            $app->post('/del[/]', 'Controllers\Customers\CustomersCouponsController:PostDeleteRecord');

        });




        //
        $app->group('/subscriptions', function () use ($app) {
            //
            $app->get('/last[/]', 'Controllers\Customers\CustomersSubscriptionsController:getLastActiveSubscription');
            $app->get('[/]', 'Controllers\Customers\CustomersSubscriptionsController:PaginateScrollRecords');
        });



        //
        $app->group('/notifications', function () use ($app) {

            //
            $app->get('/{other_id:[0-9]{0,11}}/msgs[/]', 'Controllers\Notifications\NotificationsController:PaginateScrollRecords');
            $app->post('/{id:[0-9]{0,11}}/dsms[/]', 'Controllers\Notifications\NotificationsDismissedController:PostDismissCustomerNotification');

        });




        //
        $app->group('/n', function () use ($app) {

            //
            $app->get('[/]', 'Controllers\Customers\CustomersNotificationsController:PaginateRecords');
            $app->get('/s[/]', 'Controllers\Customers\CustomersNotificationsController:PaginateScrollRecords');
            $app->get('/pr[/]', 'Controllers\Customers\CustomersNotificationsController:PaginateRecordsPrimeReact');
            $app->get('/{id:[0-9]{0,11}}[/]', 'Controllers\Customers\CustomersNotificationsController:GetRecordById');

            // set openned
            $app->post('/so[/]', 'Controllers\Customers\CustomersNotificationsController:postSetCountOpenned');
            // set viewed
            $app->post('/sv[/]', 'Controllers\Customers\CustomersNotificationsController:postSetAsViewed');

        });






    })->add(new AuthApiCustomer());





    /**
     * 
     * Recursos
     */
    $app->group('/resources', function () use ($app) {

        //
        $app->group('/{resource_id:[0-9]{0,11}}/services', function () use ($app) {
            //
            $app->get('/list[/]', 'Controllers\Resources\ResourcesServicesController:GetResourceServicesList');
        });


    })->add(new AuthApiCustomer());


    /*
    * Citas 
    * */
    $app->group('/appointments', function () use ($app) {

        // views 
        $app->get('/index', 'Controllers\Appointments\AppointmentsController:ViewIndex');
        $app->get('/{id:[0-9]{0,11}}/edit[/]', 'Controllers\Appointments\AppointmentsController:ViewEdit');
        $app->get('/pr[/]', 'Controllers\Appointments\AppointmentsController:GetPaginatePrimeReactCustomerAppointments');

        

        // 
        $app->get('/{location_id:[0-9]{0,11}}/list-available[/]', 'Controllers\Resources\ResourcesController:GetAllAvailable');
        // initialice resources
        $app->get('/res-init[/]', 'Controllers\Appointments\AppointmentsController:GetInitialResources');        
        
        // get resources info and availability for all or for single resource
        $app->post('/{location_id:[0-9]{0,11}}/resources[/]', 'Controllers\Appointments\AppointmentsController:PostGetLocationResources');
        $app->post('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}[/]', 'Controllers\Appointments\AppointmentsController:PostGetLocationResources');
        
        // agregar y ditar evento
        $app->post('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}/add[/]', 'Controllers\Appointments\AppointmentsController:AddAppointment');
        $app->post('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}/edit/{appointment_id:[0-9]{0,11}}[/]', 'Controllers\Appointments\AppointmentsController:EditAppointment');
        
        // get events by resources ids 
        $app->post('/{location_id:[0-9]{0,11}}/resources/events[/]', 'Controllers\Appointments\AppointmentsController:PostGetLocationResourcesEvents');

        // event info for view & edit
        $app->get('/{location_id:[0-9]{0,11}}/resources/{resource_id:[0-9]{0,11}}/events/{appointment_id:[0-9]{0,11}}[/]', 'Controllers\Appointments\AppointmentsController:GetEventInfo');
        
        
        //
        $app->get('/{appointment_id:[0-9]{0,11}}/event[/]', 'Controllers\Appointments\AppointmentsController:GetEventInfoWithAppointmentIdOnly');
        $app->post('/{appointment_id:[0-9]{0,11}}/update-status[/]', 'Controllers\Appointments\AppointmentsController:PostUpdateCustomerAppointmentStatus');



    })->add(new AuthApiCustomer());

    



});
