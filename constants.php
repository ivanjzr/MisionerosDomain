<?php


$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['SERVER_NAME'];
//echo $domain . " --- " .$protocol.$domain; exit;

//
define("DOMAIN_NAME", $domain);
define("FULL_DOMAIN", $protocol.$domain);

//
define("STRP_CARD", "stripe_card");
define("STRP_OXXO", "stripe_oxxo");
define("ANET_CARD", "authorizenet_card");
define("SQR_CARD", "square_card");
define("CASH", "cash");
define("CREDIT", "credit");
//
define("SUCCESS", "success");
define("PENDING", "pending");
//
define("ACCT_ID_PLABUZ", 5);
define("ACCT_ID_MISSIONEXPRESS", 12);
define("ACCT_ID_T4B", 14);
//
define("APP_ID_PLABUZ", 13);
define("APP_ID_MISSIONEXPRESS", 11);
define("APP_ID_T4B", 16);

define("SES_POS_REGISTER_ID", "pos_register_id");
define("SES_POS_USER_ID", "pos_user_id");
define("SES_POS_LOGIN_DT_EXPIRE_AT", "pos_login_datetime_limit");


// TIPOS DE PERMISOS
define("TIPO_PERMISO_ID_TODOS", 1);
define("TIPO_PERMISO_ID_ESPECIFICOS", 2);

//
define("CITA_STATUS_CONFIRMED", 1);
define("CITA_STATUS_PENDING", 2);
define("CITA_STATUS_CANCELLED", 3);
define("CITA_STATUS_IN_PROGRESS", 4);


// login path
define("SITE_LOGIN", "/login");
define("ADMIN_LOGIN", "/admin/login");
define("SUPADMIN_LOGIN", "/adm27/login");
//
define("APP_TYPE_SITE", "site");
define("APP_TYPE_ADMIN", "admin");
define("APP_TYPE_SUPADMIN", "adm27");

//
$GLOBALS['admin_title'] = 'Admin';
define("SUPADMIN_TITLE", "ADMIN");
//
define("ADMIN_URL", "admin");
define("SUPADMIN_URL", "adm27");

define("APP_ID_DENTABLIX", 13);
define("APP_ID_BARBERDESK", 18);


// Website
define("PATH_VIEWS_SITES", PATH_VIEWS.DS."sites");



// VIEWS PATH
define("PATH_VIEWS_ADMIN", PATH_VIEWS.DS."admin");
define("PATH_VIEWS_SUPADMIN", PATH_VIEWS.DS."supadmin");
define("PATH_VIEWS_SITE", PATH_VIEWS.DS."supadmin");


//
define("PATH_TWIG_DOCS", PATH_VIEWS.DS."twig_docs");


// Paises
define("ID_PAIS_MEXICO", 379);
define("ID_PAIS_EU", 467);

//
define("PROD_TYPE_CUSTOMER_ID", 12);
define("PROD_TYPE_STORE_ID", 13);

// Inventario tipos de salidas
define("TIPO_SALIDA_ESHOP", 1);
define("TIPO_SALIDA_PDV", 2);
define("TIPO_SALIDA_AJUSTE", 3);


// Tipo de Venta
define("SALE_TYPE_ESHOP_USER_NOT_REG", 1);
define("SALE_TYPE_ESHOP_USER_REG", 2);
define("SALE_TYPE_POS_ADMIN", 3);


// Status de sales
define("SALE_CREATED", 1);
define("SALE_MODIFIED", 2);
define("SALE_READY_TO_SEND", 3);
define("SALE_ON_THE_WAY", 4);
define("SALE_DELIVERED", 5);
define("SALE_CANCELLED", 6);







// GET MAQUETAS
define("MAQUETA_ID_CUST_REGISTRO", 3);
define("MAQUETA_ID_CUST_RECUP_CTA", 5);
define("MAQUETA_ID_CUST_CTA_RECUP", 6);
//
define("MAQUETA_ID_NEW_SALE", 7);
define("MAQUETA_ID_SALE_UPDTD", 9);
define("MAQUETA_ID_SALE_CANCEL", 11);
define("MAQUETA_ID_CLINICAL_RECORD", 13);
define("MAQUETA_ID_ENVIO_TICKET", 17);
define("MAQUETA_ID_ENVIO_INVOICE", 18);




// GET DOCUMENTOS
define("DOCUMENTO_ID_INVOICE_POR_COBRAR", 1);
define("DOCUMENTO_ID_TICKET", 2);

// INVOICES STATUS_ID
define("INVOICE_STATUS_ID_OPENED", 1);
define("INVOICE_STATUS_ID_POR_COBRAR", 2);
define("INVOICE_STATUS_ID_PAGADO", 3);
define("INVOICE_STATUS_ID_CANCELADO", 2);


// UNIDADES DE MEDIDA
define("UM_ID_LT", 1);
define("UM_ID_ML", 2);
define("UM_ID_KG", 3);
define("UM_ID_G", 4);
define("UM_ID_MG", 5);
define("UM_ID_PZ", 7);
define("UM_ID_CAJ", 8);
define("UM_ID_SRV", 9);


define("PAYMENT_METHOD_ID_EFECTIVO", 2);
define("PAYMENT_METHOD_ID_TARJETA", 9);
define("PAYMENT_METHOD_ID_DOLARES", 10);
