<?php
namespace Controllers\Config\ConfigQBO;

//
use App\Config\ConfigQBO;
use Controllers\BaseController;
use App\App;
//
use Helpers\Helper;
use Helpers\Query;
use Helpers\QboHelper;

//
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Exception\ServiceException;
use QuickBooksOnline\API\Facades\SalesReceipt;
use QuickBooksOnline\API\Core\ServiceContext;
use QuickBooksOnline\API\PlatformService\PlatformService;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Data\IPPLine;
use QuickBooksOnline\API\Data\IPPSalesItemLineDetail;
use QuickBooksOnline\API\Data\IPPReferenceType;

//
class ConfigQBOController extends BaseController
{




    /*
        https://developer.intuit.com/app/developer/playground?code=AB11717286097VXuc60VwC2tW0fyCfadSfOVuZ8YhaywwI5055&state=PlaygroundAuth&realmId=9341452381109521
        https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization/oauth-2.0
        https://developer.api.intuit.com/.well-known/openid_sandbox_configuration
        
        

        var redirect_uri = "https://developer.intuit.com/v2/OAuth2Playground/RedirectUrl";
    "userinfo_endpoint":"https://sandbox-accounts.platform.intuit.com/v1/openid_connect/userinfo",
    "revocation_endpoint":"https://developer.api.intuit.com/v2/oauth2/tokens/revoke",
    "jwks_uri":"https://oauth.platform.intuit.com/op/v1/jwks",
    */



    


    


   

    //
    //
    public function getAuthUrl($request, $response, $args) {
        try{


            //
            $config = QboHelper::getQBOLoginConfig();
            //
            $dataService = QboHelper::getDataService($config);
            //dd($dataService); exit;

            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            //
            $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
            //dd($authUrl); exit;

            //
            return $response->withJson([
                "auth_url" => $authUrl
            ], 200);            
            
            //
            return $response->withJson($authUrl, 200);            

        } catch(Exception $ex){
            //
            return $response->withJson([
                "error" => "error ocurred"
            ], 200);            
        }        
    }




    
    
    

    //
    public function ViewIndex($request, $response, $args) {

        //
        return $this->container->php_view->render($response, 'admin/config/config_qbo/index.phtml', [
            "App" => new App(null, $request->getAttribute("ses_data")),
        ]);
    }











    // 
    public function GetRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];

        //
        $results = ConfigQBO::GetRecord($account_id);
        //var_dump($results); exit;

        //
        return $response->withJson($results, 200);
    }






    //
    public function postRevokeToken($request, $response, $args) {


        
        //
        $queryParams = $request->getQueryParams();
        //
        $token_id = $queryParams['token_id'];



        //
        $qbo_res = QboHelper::getTokenInfo($token_id);
        //dd($qbo_res); exit;
        //
        if ( isset($qbo_res['error']) && $qbo_res['error'] ){
            return $response->withJson($qbo_res, 400);
        }
        //
        $realm_id = $qbo_res['realm_id'];
        $access_token = $qbo_res['access_token'];
        $refresh_token = $qbo_res['refresh_token'];
        //echo $access_token; exit;
        
        

        //
        $config = QboHelper::getQbOApiCallsConfig($realm_id, $access_token, $refresh_token);
        //dd($config); exit;
        $dataService = DataService::Configure($config);
        //dd($dataService); exit;

        
        
        // 
        $token_results = QboHelper::revokeToken($dataService, $token_id, $refresh_token);
        //dd($token_results); exit;
        //
        if ( isset($token_results['error']) && $token_results['error'] ){
            return $response->withJson($token_results, 400);
        } 

        $results['success'] = true;
        //
        return $response->withJson($results, 200);
    }


    

    //
    public function postSyncCustomers($request, $response, $args) {


        
        //
        $queryParams = $request->getQueryParams();
        

        //
        $token_id = $queryParams['token_id'];



        //
        $qbo_res = QboHelper::getTokenInfo($token_id);
        //dd($qbo_res); exit;
        //
        if ( isset($qbo_res['error']) && $qbo_res['error'] ){
            return $response->withJson($qbo_res, 400);
        }
        //
        $realm_id = $qbo_res['realm_id'];
        $access_token = $qbo_res['access_token'];
        $refresh_token = $qbo_res['refresh_token'];
        
        

        //
        $config = QboHelper::getQbOApiCallsConfig($realm_id, $access_token, $refresh_token);
        //dd($config); exit;
        $dataService = DataService::Configure($config);
        //dd($dataService); exit;

        
        

        /*
        //
        $token_results = QboHelper::refreshToken($dataService, $config['QBORealmID']);
        //dd($token_results); exit;
        //
        if ( isset($token_results['error']) && $token_results['error'] ){
            return $response->withJson($token_results, 200);
        }        
        //
        $token_id = $token_results['id'];
        */
        
       
        
        //$dataService->setLogLocation("/Users/hlu2/Desktop/newFolderForLog");

        
        

        /*
        $allCompanies = $dataService->FindAll('CompanyInfo');
        dd($allCompanies); exit;
        foreach ($allCompanies as $oneCompany) {
            $oneCompanyReLookedUp = $dataService->FindById($oneCompany);
            echo "Company Name: {$oneCompanyReLookedUp->CompanyName}\n";
        }
        */
        
        //
        //$companyInfo = $dataService->getCompanyInfo();
        //dd($companyInfo); exit;

        //
        //$address = "QBO API call Successful!! Response Company name: " . $companyInfo->CompanyName . " Company Address: " . $companyInfo->CompanyAddr->Line1 . " " . $companyInfo->CompanyAddr->City . " " . $companyInfo->CompanyAddr->PostalCode;
        


        // 
        $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
        $pageSize = isset($queryParams['pageSize']) ? max(1, (int)$queryParams['pageSize']) : 10;
    
        /*
            By default, only 100 customers will be returned.
            You can set parameter "MAXRESULTS" to get more records. For paginate, you can set "STARTPOSITION".
            Select * From companycurrency ORDERBY Id STARTPOSITION 0 MAXRESULTS 200


            DOCS CUSTOMER OBJECT
            https://developer.intuit.com/app/developer/qbo/docs/api/accounting/most-commonly-used/customer

        */
        $offset = ($page - 1) * $pageSize;
        //$query = "SELECT * FROM Customer MAXRESULTS $pageSize STARTPOSITION $offset";
        $query = "SELECT * FROM Customer";
        //echo $query; exit;
        $customers = $dataService->Query($query);
        //dd($customers); exit;



    
        // Construir un array con los resultados
        $arr_insert_customers = [];
        if ($customers && is_array($customers)){
            //echo count($customers); exit;
            foreach ($customers as $customer) {
                //dd($customer); exit;
    
                //                
                $app_id = 11;
                $customer_type_id = 2;
                $quickbooks_customer_id = $customer->Id;
                $company_name = $customer->CompanyName;
                //
                $name = isset($customer->GivenName) ? $customer->GivenName : null;
                //
                $email = isset($customer->PrimaryEmailAddr) ? $customer->PrimaryEmailAddr->Address : null;
                $phone_country_id = 467;
                $phone_cc = 1;
                $phone_number = isset($customer->PrimaryPhone) ? getOnlyNumbers($customer->PrimaryPhone->FreeFormNumber) : null;
                $allow_credit = 1;
                $active = 1;
                //echo $phone_number; exit;


                // DEBUG CIERTOS CLIENTES
                if ($quickbooks_customer_id==6){
                    //dd($customer); exit;
                }


                $arr_res = [
                    "app_id" => $app_id,
                    "customer_type_id" => $customer_type_id,
                    "quickbooks_customer_id" => $quickbooks_customer_id,
                    "company_name" => $company_name,
                    "name" => $name,
                    "email" => $email,
                    "phone_country_id" => $phone_country_id,
                    "phone_cc" => $phone_cc,
                    "phone_number" => $phone_number,
                    "allow_credit" => $allow_credit,
                    "active" => $active,
                ];
                                
                
                //
                $cust_found = Query::Single("Select count(*) as cant from v_customers where quickbooks_customer_id = ?", [$quickbooks_customer_id]);
                //dd($cust_found); exit;
                if (isset($cust_found['cant']) && $cust_found['cant'] > 0 ){
                
                    //
                    $res = [];
                    //
                    $res['cust_info'] = $arr_res;
                    $res['info'] = "Customer Quickbooks Id: {$quickbooks_customer_id} already & updated";
                    array_push($arr_insert_customers, $res);

                } else {
                    
                    //
                    $insert_results = Query::DoTask([
                        "task" => "add",
                        "debug" => true,
                        "stmt" => "
                            Insert Into customers
                            ( quickbooks_customer_id, customer_type_id, company_name, name, email, phone_country_id, phone_cc, phone_number, notes, allow_credit, active, app_id, last_sync_datetime, datetime_created )
                            Values
                            ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE() )
                            ;SELECT SCOPE_IDENTITY()   
                            ",
                        "params" => [
                            $quickbooks_customer_id,
                            $customer_type_id,
                            $company_name,
                            $name,
                            $email,
                            $phone_country_id,
                            $phone_cc,
                            $phone_number,
                            null,
                            $allow_credit,
                            $active,
                            $app_id,
                        ],
                        "parse" => function($insert_id, &$query_results){
                            $query_results['id'] = (int)$insert_id;
                        }
                    ]);
                    // 
                    $insert_results['cust_info'] = $arr_res;
                    array_push($arr_insert_customers, $insert_results);
                }
                
            }
        }
        //dd($arr_customers); exit;
        


        
        //
        return $response->withJson($arr_insert_customers, 200);
    }










    //
    public function postSyncSales($request, $response, $args) {


        
        //
        $queryParams = $request->getQueryParams();
        

        //
        $token_id = $queryParams['token_id'];



        //
        $qbo_res = QboHelper::getTokenInfo($token_id);
        //dd($qbo_res); exit;
        //
        if ( isset($qbo_res['error']) && $qbo_res['error'] ){
            return $response->withJson($qbo_res, 400);
        }
        //
        $realm_id = $qbo_res['realm_id'];
        $access_token = $qbo_res['access_token'];
        $refresh_token = $qbo_res['refresh_token'];
        
        

        //
        $config = QboHelper::getQbOApiCallsConfig($realm_id, $access_token, $refresh_token);
        //dd($config); exit;
        $dataService = DataService::Configure($config);
        //dd($dataService); exit;





        $quickbooks_customer_id = 3;
        $sale_id = 99;



         /*
        $salesReceiptData = [
            "CustomerRef" => [
                "value" => $quickbooks_customer_id
            ],
            "Line" => [[
                 "Id" => "1",
                 "LineNum" => 1,
                 "Description" => "Pest Control Services",
                 "Amount" => 35.0,
                 "DetailType" => "SalesItemLineDetail",
                 "SalesItemLineDetail" => [
                     "ItemRef" => [
                         "value" => "1",
                         "name" => "Pest Control"
                     ],
                     "UnitPrice" => 35,
                     "Qty" => 1,
                     "TaxCodeRef" => [
                         "value" => "NON"
                     ]
                 ]
             ]]
        ];       

        //
        $salesReceipt = SalesReceipt::create($salesReceiptData);
        $salesReceiptResponse = $dataService->Add($salesReceipt);
        //return $salesReceiptResponse->Id;
        //dd($salesReceiptResponse);
        */

        /**
         * 
         * 
         * 
         * https://github.com/intuit/QuickBooks-V3-PHP-SDK/blob/master/src/_Samples/InvoiceCreate.php
         * 
         * 
         */
        function createInvoice($dataService, $quickbooks_customer_id){
            $invoiceData = [
                "CustomerRef" => [
                    "value" => $quickbooks_customer_id
                ],
                "Line" => [[
                    "DetailType" => "SalesItemLineDetail",
                    "Amount" => 100.00, // Monto del costo
                    "SalesItemLineDetail" => [
                        "ItemRef" => [
                            "value" => "1", // ID de un ítem genérico, puede que necesites un ID válido de tu QuickBooks
                            "name" => "Trip Charge"
                        ],
                        "UnitPrice" => 100.00,
                        "Qty" => 1,
                        "TaxCodeRef" => [
                            "value" => "NON"
                        ]
                    ],
                    "Description" => "Origen: Pasco, Destino: Huntington Park, Pasajero: Juan Pérez"
                ]],
                "TotalAmt" => 100
            ];
            //    
            $invoice = Invoice::create($invoiceData);
            $invoiceResponse = $dataService->Add($invoice);
            //dd($invoiceResponse); exit;
        
    
            $error = $dataService->getLastError();
            //dd($error); exit;
    
    
            if ($error) {
                echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
                echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
                echo "The Response message is: " . $error->getResponseBody() . "\n";
            } else {
                # code...
                // Echo some formatted output
                $doc_number = $invoiceResponse->DocNumber;
                $invoice_id = $invoiceResponse->Id;
                echo " $doc_number $invoice_id "; exit;
                //echo "Created Sales Id={$salesReceiptResponse->Id}. Reconstructed response body:\n\n";
                //$xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($salesReceiptResponse, $urlResource);
                //echo $xmlBody . "\n";
            }

        }


        
        function updateInvoice($dataService, $invoiceId){
            $invoice = $dataService->FindById('Invoice', $invoiceId);
            if (!$invoice) {
                throw new \Exception("Invoice no encontrado.");
            }
            //dd($invoice); exit;




            $lineItem = new IPPLine();
            $lineItem->DetailType = 'SalesItemLineDetail';
            $lineItem->Amount = 100.00;
            $itemRef = new IPPReferenceType();
            $itemRef->value = '1'; // Reemplaza con el ID válido del artículo en QuickBooks
            $itemRef->name = 'Trip Charge';
            $lineItem->SalesItemLineDetail = new IPPSalesItemLineDetail();
            $lineItem->SalesItemLineDetail->ItemRef = $itemRef;
            $lineItem->SalesItemLineDetail->UnitPrice = 100.00;
            $lineItem->SalesItemLineDetail->Qty = 1;
            $lineItem->Description = 'Origen: Pasco, Destino: Huntington Park, Pasajero: Juan Pérez';



            // Agregar la nueva línea al Invoice
            $invoice->Line[] = $lineItem;



            // Agregar el nuevo ítem a las líneas existentes
            //array_push($invoice->Line, $newItem);
            //$invoice->Line[] = $newItem;
            

            // Actualizar el Invoice en QuickBooks
            $updatedInvoice = $dataService->Update($invoice);
            dd($updatedInvoice); exit;

            $error = $dataService->getLastError();
            dd($error); exit;
        }




        //createInvoice($dataService, $quickbooks_customer_id);
        $invoiceId = '164';
        updateInvoice($dataService, $invoiceId);
        

        
        





        echo "done"; exit;






        //
        $arr_updated_sales = [];        
        //
        return $response->withJson($arr_updated_sales, 200);
    }


















    //
    public function UpdateRecord($request, $response, $args) {


        //
        $ses_data = $request->getAttribute("ses_data");
        //var_dump($ses_data); exit;
        $account_id = $ses_data['account_id'];



        //
        $results = array();


        //
        $prod_client_id = Helper::safeVar($request->getParsedBody(), 'prod_client_id');
        $prod_client_secret = Helper::safeVar($request->getParsedBody(), 'prod_client_secret');
        $prod_redirect_url = Helper::safeVar($request->getParsedBody(), 'prod_redirect_url');
        //
        $dev_client_id = Helper::safeVar($request->getParsedBody(), 'dev_client_id');
        $dev_client_secret = Helper::safeVar($request->getParsedBody(), 'dev_client_secret');
        $dev_redirect_url = Helper::safeVar($request->getParsedBody(), 'dev_redirect_url');
        //
        $is_prod = Helper::safeVar($request->getParsedBody(), 'is_prod');
        $active = Helper::safeVar($request->getParsedBody(), 'active') ? 1 : 0;

        //
        if ( !$prod_client_id ){
            $results['error'] = "proporciona el client id";
            return $response->withJson($results, 200);
        }
        if ( !$prod_client_secret ){
            $results['error'] = "proporciona el client secret";
            return $response->withJson($results, 200);
        }
        if ( !$prod_redirect_url ){
            $results['error'] = "proporciona el redirect url";
            return $response->withJson($results, 200);
        }
        //
        if ( !$dev_client_id ){
            $results['error'] = "proporciona el client id de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$dev_client_secret ){
            $results['error'] = "proporciona el client secret de pruebas";
            return $response->withJson($results, 200);
        }
        if ( !$dev_redirect_url ){
            $results['error'] = "proporciona el redirect url de pruebas";
            return $response->withJson($results, 200);
        }
        //echo $prod_redirect_url; exit;



        //
        $param_record_id = 0;
        //
        $sp_res = Query::StoredProcedure([
            "stmt" => function(){
                return "{call usp_UpsertConfigQuickBooks(?,?,?,?,?,?,?,?,?,?)}";
            },
            "debug" => true,
            "exeptions_msgs" => [
                "default" => "Server Error, unable to do operation"
            ],
            "params" => function() use($prod_client_id, $prod_client_secret, $prod_redirect_url, $dev_client_id, $dev_client_secret, $dev_redirect_url, $is_prod, $active, $account_id, &$param_record_id){
                return [
                    //
                    array($prod_client_id, SQLSRV_PARAM_IN),
                    array($prod_client_secret, SQLSRV_PARAM_IN),
                    array($prod_redirect_url, SQLSRV_PARAM_IN),
                    //
                    array($dev_client_id, SQLSRV_PARAM_IN),
                    array($dev_client_secret, SQLSRV_PARAM_IN),
                    array($dev_redirect_url, SQLSRV_PARAM_IN),
                    //
                    array($is_prod, SQLSRV_PARAM_IN),
                    array($active, SQLSRV_PARAM_IN),
                    //
                    array($account_id, SQLSRV_PARAM_IN),
                    array(&$param_record_id, SQLSRV_PARAM_OUT),
                ];
            }
        ]);
        //
        if (isset($sp_res['error']) && $sp_res['error']){
            $results['error'] = $sp_res['error'];
            return $response->withJson($results, 200);
        }
        //
        $results['id'] = $param_record_id;
        //
        return $response->withJson($results, 200);
    }













}