<?php

namespace Controllers\Ventas;

//
use App\Config\ConfigAuthorizeNet\ConfigAuthorizeNet;
use App\Config\ConfigClabe\ConfigClabe;
use App\Config\ConfigPayPal\ConfigPayPal;
use App\Config\ConfigSquare\ConfigSquare;
use App\Config\ConfigStripe\ConfigStripe;
use Google\Service\Compute\Help;
use Helpers\Helper;
use Helpers\Query;

// PayPal
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;


// Stripe
use Square\Models\CreatePaymentRequest;
use Square\Models\Currency;
use Stripe\PaymentIntent;
use Stripe\Stripe;

// Authorizenet
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use net\authorize\api\constants\ANetEnvironment;

// Square
use Square\Models\Money;
use Square\SquareClient;
use Square\Exceptions\ApiException;
use Square\Http\ApiResponse;
use Square\Models\ListLocationsResponse;
use Square\Environment;



/*
 *
 *
 * HELPER DE PAYMENTS GATEWAYS
 * PARA NO TENER TODO EL AMONTONADERO EN VENTASCONTROLLER
 *
 *
 * */
class PaymentsGateways{







    //
    public static function pushPaymentSquare($account_id, $sale_id, &$arr_payments, $forced_decimals_grand_total, $str_products, $nonce, $idempotency_key, $location_id, $is_test = false){
        //echo " $nonce, $idempotency_key, $location_id "; exit;


        //
        $results = array();

        //
        $square_info = ConfigSquare::GetRecord($account_id);
        //var_dump($square_info); exit;
        if (isset($square_info['error']) && $square_info['error']){
            $results['error'] = $square_info['error'];
            return $results;
        }

        // FLAG FOR DEV
        //$square_info['active'] = 1; $square_info['is_prod'] = 0;


        //
        if ($is_test){
            $square_info['active'] = 1; $square_info['is_prod'] = 0;
        }


        if ( !(isset($square_info['id']) && $square_info['id'] && $square_info['active']) ){
            $results['error'] = "Stripe method not active";
            return $results;
        }

        //
        if (isset($square_info['is_prod']) && $square_info['is_prod']){
            //
            $square_options = [
                'accessToken' => $square_info['prod_access_token'],
                'environment' => Environment::PRODUCTION
            ];
        } else {
            //
            $square_options = [
                'accessToken' => $square_info['dev_access_token'],
                'environment' => Environment::SANDBOX
            ];
        }
        //dd($square_info); exit;

        //
        $client = new SquareClient($square_options);

        //
        try {
            $locationsApi = $client->getLocationsApi();
            $apiResponse = $locationsApi->listLocations();

            //
            $results['vals'] = array();

            if ($apiResponse->isSuccess()) {
                $listLocationsResponse = $apiResponse->getResult();
                $locationsList = $listLocationsResponse->getLocations();
                foreach ($locationsList as $location) {
                    array_push($results['vals'], $location);
                }
            } else {
                $results['error'] = $apiResponse->getErrors();
            }
        } catch (ApiException $e) {
            $results['error'] = "Recieved error while calling Square: " . $e->getMessage();
        }





        /*
         *
         * PARA DESPUES QUITAR EL PUNTO Y QUE DE MANERA SEGURA NOS DE EL MONTO CORRECTO
         *
         * */
        $square_amt = str_replace(".", "", $forced_decimals_grand_total);;
        //echo $square_amt; exit;



        //
        $body_amountMoney = new Money;
        $body_amountMoney->setAmount($square_amt);
        $body_amountMoney->setCurrency(Currency::USD);

        //
        $body = new CreatePaymentRequest(
            $nonce,
            $idempotency_key,
            $body_amountMoney
        );
        //$body->setTipMoney(new Money);
        //$body->getTipMoney()->setAmount(198);
        //$body->getTipMoney()->setCurrency(Currency::USD);
        //
        //$body->setAppFeeMoney(new Money);
        //$body->getAppFeeMoney()->setAmount(10);
        //$body->getAppFeeMoney()->setCurrency(Currency::USD);
        //$body->setDelayDuration('delay_duration6');
        //
        $body->setAutocomplete(true);
        //
        //$body->setOrderId('order 1234');
        //$body->setCustomerId('VDKXEEKPJN48QDG3BGGFAK05P8');
        //$body->setReferenceId('123456');
        $body->setLocationId($location_id);
        $body->setNote($str_products);



        //
        $paymentsApi = $client->getPaymentsApi();
        //var_dump($paymentsApi); exit;
        //
        $apiResponse = $paymentsApi->createPayment($body);
        //
        if ($apiResponse->isSuccess()) {



            //
            $createPaymentResponse = $apiResponse->getResult();
            $payment_id = $createPaymentResponse->getPayment()->getId();
            $order_id = $createPaymentResponse->getPayment()->getOrderId();
            //echo $payment_id; exit;
            //echo "<pre>"; print_r($createPaymentResponse); echo "</pre>"; exit;



            // actualizar a pagado y notifica
            //
            $payment_type_id_square = 8;
            $payment_status_completed = 1;
            $tipo_moneda_id_usd = 2;
            //
            array_push($arr_payments, array(
                "payment_type_id" => $payment_type_id_square,
                "payer_name" => null,
                "payer_address" => null,
                "email_address" => null,
                "phone_number" => null,
                "transaction_id" => $payment_id,
                "auth_code" => null,
                "paypal_payer_id" => null,
                "intent" => null,
                "paypal_status" => null,
                "payment_status_id" => $payment_status_completed,
                "tipo_moneda_id" => $tipo_moneda_id_usd,
                "amount" => $forced_decimals_grand_total
            ));
            //
            return [
                "success" => true
            ];
        }
        //
        else {
            //
            $errors = $apiResponse->getErrors();
            //echo "<pre>";print_r($errors);echo "</pre>";
            $err_detail = $errors[0]->getDetail();
            $results['error'] = "Square Payment Error: " . $err_detail;
        }


        //[locationId:Square\Models\Payment:private] => LAY7YD7W98969
        //[orderId:Square\Models\Payment:private] => 5YpnFKgvZc1Bj3HGhBRTUKQavSbZY
        //[referenceId:Square\Models\Payment:private] => 123456


        //
        return $results;
    }





    /*
     *
     *
     * STRIPE PAYMENTS
     * testing cards link:
     * https://stripe.com/docs/testing
     *
     *
     * */
    public static function pushPaymentStripe($account_id, $app_id, $payment_method_id, &$arr_payments, $grand_total, $payment_description){

        //
        $results = array();

        //
        $stripe_info = ConfigStripe::GetRecord($account_id);
        //dd($stripe_info); exit;
        if (isset($stripe_info['error']) && $stripe_info['error']){
            $results['error'] = $stripe_info['error'];
            return $results;
        } else if ( !(isset($stripe_info['id']) && $stripe_info['id']) ){
            $results['error'] = "No se encontro configuracion de pago de stripe";
            return $results;
        }

        
        //$stripe_info['is_prod'] = false; $stripe_info['active'] = 1;
        $api_secret = null;
        $is_prod = false;
        if (isset($stripe_info['id']) && $stripe_info['id']){
            if (isset($stripe_info['is_prod']) && $stripe_info['is_prod']){
                $api_secret = $stripe_info['secret_key'];
                $is_prod = true;
            } else {
                $api_secret = $stripe_info['secret_key_test'];
            }
        }



        /*
         *
         * Set Stripe Api Key
         * //
         *
         * \Stripe\Stripe::setApiKey($stripeSecretKey);
         * Stripe::setApiKey($stripe_info['secret_key']); // secret_key_test
         * */
        //
        Stripe::setApiKey($api_secret);





        //
        $folio = uniqid();


        $tipo_moneda_id_mxn = 1;
        /*
        $tipo_moneda_results = Query::Single("SELECT t.* FROM sys_tipos_monedas t Where t.id = ?", [$tipo_moneda_id_mxn]);
        //var_dump($tipo_moneda_results); exit;
        if ($tipo_moneda_results && isset($tipo_moneda_results['error']) && $tipo_moneda_results['error']){
            $results['error'] = $tipo_moneda_results['error'];
            return $results;
        }
        $tipo_moneda = strtolower($tipo_moneda_results['tipo_moneda']);
        //echo $tipo_moneda; exit;
        */



        //
        $amount_in_cents = floatval($grand_total) * 100;
        $new_total_pedido = intval($amount_in_cents);
        //echo $new_total_pedido; exit;



        //
        $paymentIntent = null;


        //
        $pedido_id = 1234;
        $sucursal_id = 6;
        $user_type = "site"; // or "admin"
        $customer_id = null;
        $name = null;
        $user_or_customer_id = null;
        $pedido_payment_id = null;
        //echo 'mxn' . "-" . $tipo_moneda; exit;




        try {


            // .strtoupper($prod_periodicidad);
            $strip_statement_descriptor = "YONKEPARTS-COM";
            $strip_statement_descriptor_suffix = "YONKEPARTS";

            //
            $paymentIntent = PaymentIntent::create([
                'amount' => $new_total_pedido,
                'currency' => 'mxn',
                'confirm' => true,
                "payment_method_types" => ["oxxo", "card"],
                'description' => $payment_description,
                'payment_method' => $payment_method_id,
                "statement_descriptor" => $strip_statement_descriptor,
                'statement_descriptor_suffix' => $strip_statement_descriptor_suffix,
                /*
                'confirmation_method' => 'manual',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                */
                'metadata' => [
                    'pedido_id' => $pedido_id,
                    'account_id' => $account_id,
                    'app_id' => $app_id,
                    'sucursal_id' => $sucursal_id,
                    'user_type' => $user_type,
                    'folio' => $folio,
                    'customer_id' => $customer_id,
                    'name' => $name,
                    'user_or_customer_id' => $user_or_customer_id
                ]
            ]);
            //dd(123456); exit;

            /*
         * INTENTO EXITOSO
         * */
            if ($paymentIntent->status == 'succeeded') {


                // actualizar a pagado y notifica
                //
                $payment_type_id_stripe_card = 4;
                $payment_status_completed = 1;

                $arr_payments = [array(
                    "payment_type_id" => $payment_type_id_stripe_card,
                    "payer_name" => null,
                    "payer_address" => null,
                    "email_address" => null,
                    "phone_number" => null,
                    "transaction_id" => $paymentIntent->id,
                    "auth_code" => null,
                    "paypal_payer_id" => null,
                    "intent" => null,
                    "paypal_status" => null,
                    "payment_status_id" => $payment_status_completed,
                    "tipo_moneda_id" => $tipo_moneda_id_mxn,
                    "amount" => $grand_total
                )];

                //
                return [
                    "success" => true,
                    "mode" => ($is_prod) ? "prod" : "dev",
                ];

            }
            /*
             * SI EL INTENTO REQUIRE ACCION
             * */
            elseif ($paymentIntent->status == 'requires_action') {


                //
                $payment_type_id_stripe_oxxo = 5;
                $payment_status_pending = 2;
                //
                $arr_payments = [array(
                    "payment_type_id" => $payment_type_id_stripe_oxxo,
                    "payer_name" => $paymentIntent->next_action->oxxo_display_details->hosted_voucher_url,
                    "payer_address" => null,
                    "email_address" => null,
                    "phone_number" => null,
                    "transaction_id" => $paymentIntent->id,
                    "auth_code" => null,
                    "paypal_payer_id" => null,
                    "intent" => null,
                    "paypal_status" => null,
                    "payment_status_id" => $payment_status_pending,
                    "tipo_moneda_id" => $tipo_moneda_id_mxn,
                    "amount" => $grand_total
                )];

                //
                return [
                    'requiresAction' => true,
                    'pedido_payment_id' => $pedido_payment_id,
                    'clientSecret' => $paymentIntent->client_secret
                ];
            }


        } catch (\Exception $e) {
            //
            return [
                "error" => $e->getMessage()
            ];
        }


        //
        return [
            "error" => 'Invalid PaymentIntent status'
        ];

    }




    //
    public static function pushPaymentCash($account_id, $app_id, $customer_name, $customer_email, $phone_number, &$arr_payments, $total_amount){
        //
        $results = array();
        //
        $payment_type_id_efectivo = 2;
        $payment_type = "Efectivo";
        //
        $tipo_moneda_id_usd = 2;
        $tipo_moneda = "USD";
        $payment_status_id_completed = 1;
        //
        array_push($arr_payments, array(
            "account_id" => $account_id,
            "app_id" => $app_id,
            "payment_type_id" => $payment_type_id_efectivo,
            "payer_name" => $customer_name,
            "payer_address" => null,
            "email_address" => $customer_email,
            "phone_number" => $phone_number,
            "transaction_id" => null,
            "auth_code" => null,
            "paypal_payer_id" => null,
            "intent" => null,
            "paypal_status" => null,
            "payment_status_id" => $payment_status_id_completed,
            "tipo_moneda_id" => $tipo_moneda_id_usd,
            "amount" => $total_amount
        ));

         //
         return [
            "success" => true
        ];
    }






    //
    public static function pushPaymentClabe($app_id, &$arr_payments, $grand_total){

        //
        $results = array();



        //
        $clabe_info = ConfigClabe::GetRecord($app_id);
        //var_dump($clabe_info); exit;
        if (isset($clabe_info['error']) && $clabe_info['error']){
            $results['error'] = $clabe_info['error'];
            return $results;
        }
        if ( !(isset($clabe_info['id']) && $clabe_info['id'] && $clabe_info['active']) ){
            $results['error'] = "Clabe info not found";
            return $results;
        }


        //
        $payment_type_id_clabe = 1;
        $payment_status_pending = 2;
        $tipo_moneda_id_mxn = 1;

        //
        array_push($arr_payments, array(
            "payment_type_id" => $payment_type_id_clabe,
            "payer_name" => null,
            "payer_address" => null,
            "email_address" => null,
            "phone_number" => null,
            "transaction_id" => null,
            "auth_code" => null,
            "paypal_payer_id" => null,
            "intent" => null,
            "paypal_status" => null,
            "payment_status_id" => $payment_status_pending,
            "tipo_moneda_id" => $tipo_moneda_id_mxn,
            "amount" => $grand_total
        ));
    }










    //
    public static function pushPaymentPayPal($app_id, $paypal_order_id, &$arr_payments){

        //
        $results = array();


        //
        $paypal_config = ConfigPayPal::GetRecord($app_id);
        //var_dump($paypal_config); exit;
        if (isset($paypal_config['error']) && $paypal_config['error']){
            $results['error'] = $paypal_config['error'];
            return $results;
        }


        // FLAG FOR DEV
        //$paypal_config['active'] = 1; $paypal_config['is_prod'] = 0;


        if ( !(isset($paypal_config['id']) && $paypal_config['id'] && $paypal_config['active']) ){
            $results['error'] = "Paypal method not active";
            return $results;
        }


        //
        if (isset($paypal_config['is_prod']) && $paypal_config['is_prod']){
            $environment = new SandboxEnvironment($paypal_config['client_id'], $paypal_config['client_secret']);
        } else {
            //echo " is dev test " . $paypal_config['client_id_test']; exit;
            $environment = new SandboxEnvironment($paypal_config['client_id_test'], $paypal_config['client_secret_test']);
        }
        //
        $client = new PayPalHttpClient($environment);
        //var_dump($client); exit;


        /*
         *
         * GET ORDER INFO
         *
         * */
        $get_order_response = $client->execute(new OrdersGetRequest($paypal_order_id));
        //var_dump($get_order_response); exit;

        //
        $payment_status_id = 2;
        if ( $get_order_response->result->status === "COMPLETED" ){
            $payment_status_id = 1;
        }
        //
        $transaction_id = $get_order_response->result->id;
        $intent = $get_order_response->result->intent;
        $payer_name = $get_order_response->result->payer->name->given_name;
        $email_address = $get_order_response->result->payer->email_address;
        $paypal_payer_id = $get_order_response->result->payer->payer_id;
        $payer_address = $get_order_response->result->payer->address->country_code;
        $paypal_status = $get_order_response->result->status;
        //
        //var_dump($get_order_response->result->purchase_units); exit;
        $sale_info = $get_order_response->result->purchase_units[0]->amount;
        $currency_code = $sale_info->currency_code;
        $amount = $sale_info->value;
        //echo " $currency_code $amount "; exit;
        //
        $tipo_moneda_results = Query::Single("SELECT t.* FROM sys_tipos_monedas t Where t.tipo_moneda = ?", [$currency_code]);
        //var_dump($tipo_moneda_results); exit;
        if ($tipo_moneda_results && isset($tipo_moneda_results['error']) && $tipo_moneda_results['error']){
            return $tipo_moneda_results;
        }
        //
        $payment_type_id_paypal = 2;
        //echo "$transaction_id $intent $payer_given_name $email_address $payer_id"; exit;
        //var_dump($payer_address); exit;

        //
        array_push($arr_payments, array(
            "payment_type_id" => $payment_type_id_paypal,
            "payer_name" => $payer_name,
            "payer_address" => $payer_address,
            "email_address" => $email_address,
            "phone_number" => null,
            "transaction_id" => $transaction_id,
            "auth_code" => null,
            "paypal_payer_id" => $paypal_payer_id,
            "intent" => $intent,
            "paypal_status" => $paypal_status,
            "payment_status_id" => $payment_status_id,
            "tipo_moneda_id" => $tipo_moneda_results['id'],
            "amount" => $amount
        ));
    }







    /*
     *
     * PAGO CHASE
     *
     * */
    // https://github.com/AuthorizeNet/sdk-php
    // https://github.com/AuthorizeNet/sample-code-php
    //
    // https://developer.authorize.net/api/reference/dist/json/responseCodes.json
    // https://developer.authorize.net/api/reference/features/errorandresponsecodes.html

    // https://support.orderlogix.com/index.php?/Knowledgebase/Article/View/395/90/authoizenet-result-reason-code-252---your-order-has-been-received-thank-you-for-your-business

    // -- todo test tarjetas
    // https://developer.authorize.net/hello_world/testing_guide/
    //
    public static function pushPaymentAuthorizeNet($account_id, $app_id, $card_number, $exp_date, $cvv, $refId, &$arr_payments, $usd_grand_total){


        //
        $results = array();



        //
        $authorizenet_info = ConfigAuthorizeNet::GetRecord($account_id);
        //dd($authorizenet_info); exit;
        if (isset($authorizenet_info['error']) && $authorizenet_info['error']){
            $results['error'] = $authorizenet_info['error'];
            return $results;
        } else if ( !(isset($authorizenet_info['id']) && $authorizenet_info['id']) ){
            $results['error'] = "No se encontro configuracion de pago de authorize net";
            return $results;
        }

        //
        $login_id = null;
        $trans_key = null;
        $is_prod = false;
        if (isset($authorizenet_info['id']) && $authorizenet_info['id']){                
            if (isset($authorizenet_info['is_prod']) && $authorizenet_info['is_prod']){
                $login_id = $authorizenet_info['login_id'];
                $trans_key = $authorizenet_info['trans_key'];
                $is_prod = true;
            } else {
                $login_id = $authorizenet_info['login_id_test'];
                $trans_key = $authorizenet_info['trans_key_test'];
            }
        }
        //echo "$login_id, $trans_key, $is_prod"; exit;
        


        //
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        //
        $merchantAuthentication->setName($login_id);
        $merchantAuthentication->setTransactionKey($trans_key);
        

        /*
         * Transaction type credit card
         * */
        $creditCardType = new AnetAPI\CreditCardType();
        $creditCardType->setCardNumber($card_number);
        $creditCardType->setExpirationDate($exp_date);
        $creditCardType->setCardCode($cvv);


        /*
         * Add Card Payment Type
         * */
        $paymentTypeCard = new AnetAPI\PaymentType();
        $paymentTypeCard->setCreditCard($creditCardType);


        /*
         * Set Transaction Request Type
         * */
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($usd_grand_total);
        $transactionRequestType->setPayment($paymentTypeCard);


		/*
		 * Evita el error de ventana duplicada, lo limita a 5 segundos
		 * se recomienda mostrar el error: please wait 5 seconds
 		 */
		$duplicateWindowSetting = new AnetAPI\SettingType();
		$duplicateWindowSetting->setSettingName("duplicateWindow");
		$duplicateWindowSetting->setSettingValue("5");
		$transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
			
			
        /*
        *
        * Set Currency
        * MONEDAS QUE ACEPTA AUTHORIZE.NET POR EL MOMENTO SOLAMENTE USD Y OTROS
        * https://support.authorize.net/s/article/Which-Currencies-Does-Authorize-Net-Support
         *
        * */
        $supportedCurrencyCodeANet = "USD";
        $transactionRequestType->setCurrencyCode($supportedCurrencyCodeANet);


        /*
         * Set Transaction Request
         * */
        $transactionRequest = new AnetAPI\CreateTransactionRequest();
        $transactionRequest->setMerchantAuthentication($merchantAuthentication);
        $transactionRequest->setRefId($refId);
        $transactionRequest->setTransactionRequest($transactionRequestType);



        /*
         * Set Transaction Controller
         * */
        $transactionController = new AnetController\CreateTransactionController($transactionRequest);



        /*
         *
         * GET API RESPONSE
         * With mode Production/Sandbox
         *
         * */
        if ( $is_prod ){
            $anet_response = $transactionController->executeWithApiResponse(ANetEnvironment::PRODUCTION);
        } else {
            $anet_response = $transactionController->executeWithApiResponse(ANetEnvironment::SANDBOX);
        }
        

        //
        $authorize_response = self::getAuthorizeNetResponse($anet_response);
        //dd($authorize_response); exit;
        if (isset($authorize_response['error']) && $authorize_response['error']){
            $results['error'] = $authorize_response['error'];
            return $results;
        }
        //
        else if ( isset($authorize_response['success']) && isset($authorize_response['auth_code']) && $authorize_response['auth_code'] ){
            //
            $payment_type_id_authorizenet = 3;
            $payment_status_completed = 1;
            $tipo_moneda_id_usd = 2;

            //
            $card_last_four = substr($card_number, -4);
            //
            array_push($arr_payments, array(
                "payment_type_id" => $payment_type_id_authorizenet,
                "payer_name" => null,
                "payer_address" => null,
                "email_address" => null,
                "phone_number" => null,
                "card_last_four" => $card_last_four,
                "transaction_id" => $authorize_response['transaction_id'],
                "auth_code" => $authorize_response['auth_code'],
                "paypal_payer_id" => null,
                "intent" => null,
                "paypal_status" => null,
                "payment_status_id" => $payment_status_completed,
                "tipo_moneda_id" => $tipo_moneda_id_usd,
                "amount" => $usd_grand_total
            ));

            //
            return [
                "success" => true,
                "mode" => ($is_prod) ? "prod" : "dev",
            ];
        }

        // return anet response
        return $authorize_response;
    }








    //
    public static function getAuthorizeNetResponse($anet_response){
        //dd($anet_response); exit;


        //
        $results = array();


        if (!$anet_response){
            $results['error'] = "No response returned";
            return $results;
        }
        
        //
        $arr_msgs = $anet_response->getMessages();
        $resultCode = $arr_msgs->getResultCode();
        //dd($arr_msgs); exit;


        //
        $transaction_response = $anet_response->getTransactionResponse();        
        if ($transaction_response) {
    
        
            //
            $response_code = $transaction_response->getResponseCode();
            $transaction_id = $transaction_response->getTransId();
            $auth_code = $transaction_response->getAuthCode();
            $transaction_response_messages = $transaction_response->getMessages();
            $errors = $transaction_response->getErrors();
            //dd($errors); exit;
            //echo $response_code . " " . $transaction_id; exit;



            // si tenemos code y description
            $msg_code = "";
            $msg_description = "";
            //
            if ($transaction_response_messages != null) {
                $msg_code = $transaction_response_messages[0]->getCode();
                $msg_description = $transaction_response_messages[0]->getDescription();
            }
            //echo "$msg_code $msg_description"; exit;

            // si tenemos errores los mostramos
            //
            if ($errors != null) {
                //$err_code = $errors[0]->getErrorCode();
                $err_text = $errors[0]->getErrorText();
                $results['error'] = "$err_text";
                return $results;
            }
            //echo "$err_code $err_text"; exit;


            //
            $results['response_code'] = $response_code;
            $results['transaction_id'] = $transaction_id;
            $results['auth_code'] = $auth_code;
            $results['msg_code'] = $msg_code;
            $results['msg_description'] = $msg_description;


             /*
                Response Code - indicates the overall status of the transaction with possible values of Approved, Declined, Errored or Held for Review:
                1: Approved
                2: Declined
                3: Error
                4: Action Required (typically used for AFDS transactions that are held for review)
            **/
            //
            if ( $response_code == "1" || $msg_code == "252" || $msg_code == "253" ) {
                $results['success'] = true;
                return $results;
            }            
        }
        

        //
        $str_error = "";
        //
        if ( $resultCode  === 'Error' ) {
            $str_error = "";
            foreach($arr_msgs->getMessage() as $item){
                //dd($item); exit;
                //$code = $item->getCode() . " - ";
                $text = $item->getText(). "\n <br />";
                $str_error .= $text;
            }
        }
        //echo $str_error; exit;
        if (!$str_error){
            $str_error = "Ocurrio error al realizar la solicitud";
        }


        //
        $results['error'] = $str_error;
        return $results;
    }








}