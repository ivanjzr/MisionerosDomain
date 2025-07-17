(function ($) {
    'use strict';





   



    //
    vent.updateCounter2 = function(item_insert_id, item_is_expired, horaInicial) {

        //
        var update_elem_id = "item-"+item_insert_id;

        //
        var horaActual = moment();
        var diferenciaMilisegundos = horaActual.diff(horaInicial);

        //
        var minutosTranscurridos = Math.floor(diferenciaMilisegundos / (60 * 1000));
        var segundosTranscurridos = Math.floor((diferenciaMilisegundos % (60 * 1000)) / 1000);


        //
        var field_color = "green";
        var str_input_expired = "";
        var str_is_expired = (item_is_expired) ? " Expiro " : "";
        //
        if ( minutosTranscurridos >= vent.mins_limite ){            
            //
            field_color = "orangered";
            // 
            str_input_expired = "<input type='hidden' class='is_expired' value='1' />";
        }
        
        //
        var field_res = "<small style='color:"+field_color+"'>(" + str_is_expired + " Hace " + minutosTranscurridos + " minutos " + segundosTranscurridos + " segundos)</small>" + str_input_expired;
        
        //console.log(moment().format("DD MMM YY h:mm A"), field_res);
        $("#" + update_elem_id).html(field_res);
    }


    
    function getDAtaInfo(obj){
        //
        return JSON.stringify({
            id: obj.id,
            customer_id: obj.customer_id,
            customer_name: obj.customer_name,
            email: obj.email,
            phone_number: obj.phone_number,
            grand_total: obj.grand_total,
        });
    }

    function getComisiones(item){
        if ( parseFloat(item.comisiones) > 0 ){
            return "<br /><small style='color:blue;'>comision: " + item.comisiones + " </small>";
        }
        return "";
    }
    function getComisionesCbx(item){
        if ( parseFloat(item.comisiones_cbx) > 0 ){
            return "<br /><small style='color:blue;'>comision cbx: " + item.comisiones_cbx + " </small>";
        }
        return "";
    }
    function getSaleType(item){
        var str_sale_type = '';
        if (item.customer_id){
            str_sale_type = (parseInt(item.customer_type_id)===2) ? "Vendedor" : "Cliente Regular";
        } else {
            str_sale_type = "General";
        }
        return str_sale_type;
    }
    function getTextResultType(result_type){
        /**
         * SI ES MISSIONEXPRESS EL TEXTO ES NORMAL
         */
        if (app_id === app.APP_ID_MSNEXPR){
            if (result_type==="pagado"){
                return "<div style='color:green;'>*pagado</div>";
            }
            else if (result_type==="por_pagar"){
                return  "<div style='color:orangered;'>--por-pagar</div>";
            }
            else if (result_type==="cobrado"){
                return "<div style='color:green;'>*cobrado</div>";
            }
            else if (result_type==="por_cobrar"){
                return  "<div style='color:orangered;'>--por-cobrar</div>";
            }
        } 
        /**
         * SI ES OTRO DIFERENTE A MSNEXPR EL TEXTO ES INVERTIDO
         */
        else {
            if (result_type==="pagado"){
                return "<div style='color:green;'>*cobrado</div>";
            }
            else if (result_type==="por_pagar"){
                return  "<div style='color:orangered;'>--por-cobrar</div>";
            }
            else if (result_type==="cobrado"){
                return "<div style='color:green;'>*pagado</div>";
            }
            else if (result_type==="por_cobrar"){
                return  "<div style='color:orangered;'>--por-pagar</div>";
            }
        }
    }

    /*
    *
    * SECCION VENTAS
    *
    * */
    app.createSection({
        section_title: "Ventas",
        section_title_singular: "Venta",
        scripts_path: "/app/sales",
        endpoint_url: app.admin_url + "/sales",
        gridOptions:{
            btnExpandir: true,
            btnColapsar: true,
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "sale_acct_app_name", "data" : function(obj){
                    var str_info = "<small style='font-weight:bold;'>" + getSaleType(obj) + "</small>";
                    str_info += "<br /><small>*" + obj.sale_acct_app_name +  "</small>";
                    return str_info;
                }},
                {"data" : function(obj){
                    //
                    var str_info = "";

                    //
                    var str_customer_id = (obj.customer_id) ? "<strong>#" + obj.customer_id + "</strong>" : "";
                    var str_company_name = (obj.customer_id && obj.company_name) ? " - " + obj.company_name : "";

                    // 
                    str_info += "<div>" + str_customer_id + str_company_name + " <strong>" + obj.customer_name + "</strong></div>";
                    str_info += "<div>" + obj.email + " ("+obj.phone_number + ") </div>";
                    if (obj.customer_acct_app_name){
                        str_info += "<small> reg on: " + obj.customer_acct_app_name + "</small>";
                    }                    
                    //
                    str_info += "<div>Fecha: " + fmtDateEng(obj.datetime_created.date) + "</div>";
                    //
                    return str_info;
                }},
                {width:"300", "data" : function(obj){
                        //
                        var str_vals = "";
                        //
                        $.each(obj.salidas_ocupacion, function(idx, item){
                            //                            
                            var data_info = JSON.stringify({
                                salida_id: item.salida_id,
                                fecha_hora_salida: item.fecha_hora_salida,
                                fecha_hora_llegada: item.fecha_hora_llegada
                            });

                            //
                            str_vals += "<h4 style='padding:0;margin:0;'> " + item.id + " - " + item.passanger_name + " Lugar #" + item.num_asiento + "</h4>";
                            str_vals += "<div style=''>• " + item.origen_info + " - " +  momentFormat(item.fecha_hora_salida.date, "DD MMM YY h:mm A") + "</div>";
                            str_vals += "<div style=''>• " + item.destino_info + " - " + momentFormat(item.fecha_hora_llegada.date, "DD MMM YY h:mm A") + "</div>";

                            //
                            if (parseFloat(item.costo_ext_salida) || parseFloat(item.costo_ext_llegada)){
                                //
                                str_vals += "<div style=''>• costo salida: " + item.costo_origen_destino + "</div>";
                                //
                                if (item.costo_ext_salida){
                                    str_vals += "<div style=''>• costo extension salida: " + item.costo_ext_salida + "</div>";
                                }
                                //
                                if (item.costo_ext_llegada){
                                    str_vals += "<div style=''>• costo extension llegada: " + item.costo_ext_llegada + "</div>";
                                }
                                //
                                str_vals += "<div style=''>• total Pasaje: <strong>" + item.total + getComisiones(item) + getComisionesCbx(item) + "</strong></div>";
                            } else {
                                str_vals += "<div style=''>• total Pasaje: <strong>" + item.total + getComisiones(item) + getComisionesCbx(item) +"</strong></div>";
                            }
                            

                            //
                            if (item.is_temp_salida){
                                //
                                var item_id = item.id;
                                var counter_item_id = "cntr-"+item_id;
                                var update_elem_id = "item-"+item_id;
                                var item_datetime_created = moment(item.datetime_created.date, "YYYY-MM-DD HH:mm");
                                //console.log(item.datetime_created, item_datetime_created);
                                var item_is_expired = (item.is_expired) ? 1 : 0;
                                //
                                vent.counters2[counter_item_id] = setInterval(function() {
                                    vent.updateCounter2(item_id, item_is_expired, item_datetime_created);
                                }, 1000);
                                str_vals += "<div style='background-color:#ffffd8;'>" + item_datetime_created.format("DD MMM YY h:mm A") + " <span id='"+update_elem_id+"'></span></div>";
                            }
                            
                            //
                            str_vals += " <a href='#!' class='btn btn-sm btn-info btn-view-ocupacion' data-info='"+data_info+"'><i class='fas fa-route'></i> Viaje Info </a> ";
                            str_vals += " <a href='"+item.ticket_url+"?pdf=1' target='_blank' class='btn btn-sm btn-primary' data-info='"+data_info+"'><i class='fas fa-print'></i></a> ";
                            str_vals += "<hr />";
                        });
                        //
                        return str_vals
                }},
                //
                {"data" : function(obj){ 
                        //
                        var str_info = "<ul>";                        
                        //
                        if ( obj.discount_percent > 0 || obj.tax_amount > 0  ){
                            //
                            str_info += "<li><strong>Subtotal: </strong> " + obj.sub_total +"</li>";
                            //
                            if ( obj.discount_percent > 0 ){
                                str_info += "<li><strong>Discount %</strong>" + obj.discount_percent +": -" + obj.discount_amount + " </li>";
                            } else if ( obj.discount_amount > 0 ){
                                str_info += "<li><strong>Discount -</strong>" + obj.discount_amount + " + </li>";
                            }
                            //
                            if ( obj.tax_amount > 0 ){
                                str_info += "<li><strong>Tax %</strong>" + obj.tax_percent +": " + obj.tax_amount + " </li>";
                            }
                        }
                        //
                        str_info += "<li><strong>Total Venta: </strong> " + obj.grand_total +"</li>";
                        str_info += "</ul>";
                        

                        //
                        if (obj.sale_payments && obj.sale_payments.length){
                            str_info += "<hr />";
                            str_info += "<ul>";
                            $.each(obj.sale_payments, function(idx, item){
                                var str_transaction_id = (obj.transaction_id) ? " <small style='color:gray;'>(" + item.transaction_id + ")</small>" : "";
                                str_info += "<li><strong style='color:green;'>"+item.payment_type+"</strong> "+item.amount + " <small style='color:gray;'>" + item.tipo_moneda + "</small> " + item.payment_status + str_transaction_id + "</li>";
                            });
                            str_info += "</ul>";
                        }

                        //
                        return str_info;
                    }},                
                {"data" : function(obj){
                    //
                    var str_info = "";
                    //
                    if (obj.a_credito){
                        //
                        str_info = "<div>A Credito:</div>";
                        //
                        if ( obj.cant_salidas_ocupacion == 0 && obj.cant_temp_salidas_ocupacion == 0 ){
                            str_info += "<div style='color:orangered;'> **No acepto venta </div>";
                        } else {
                            //
                            if (obj.seller_accepted){
                                str_info += "<div style='color:green;'><i class='fas fa-check' style='color:green;'></i></div>";
                            } else {
                                str_info += "<div style='color:orangered;'>sin-aceptar</div>";
                            }
                        }
                    } else {
                        // 
                        str_info = "<div>Pago:</div>";
                        //
                        if (obj.sale_paid){
                            str_info += "<div style='color:green;'><i class='fas fa-check' style='color:green;'></i></div>";
                        } else {
                            str_info += "<div style='color:orangered;'>--por-pagar</div>";
                        }
                    }
                    return str_info;
                }},

                /*------------------------------------------- COMISIONES REGULARES -------------------------------------------*/

                {"name": "id", "data" : function(obj){                        
                    //
                    if (obj.aplica_comision){
                        //
                        var str_info = "<hr style='padding:0;margin:0' />";
                        if (obj.is_comision_pagada_a_cte){
                            str_info += getTextResultType("pagado");
                        } else {
                            str_info += getTextResultType("por_pagar");                            
                        }
                        //
                        return "<div><strong>" + parseFloat(obj.comisiones) + "</strong></div>" + str_info;
                    }
                    return 0;                    
                }},         
                {"name": "id", "data" : function(obj){
                    //
                    var data_info = getDAtaInfo(obj);                
                    //
                    var str_info = "";
                    if (obj.aplica_comision){
                        str_info = "<hr style='padding:0;margin:0' />";
                        if (obj.is_venta_cobrada_a_cte){
                            var str_invoice_id = (obj.invoice_id) ? " <div style='color:green;font-weight:bold;'>Invoice #" + obj.invoice_id + "</div>" : "";
                            str_info += getTextResultType("cobrado") + str_invoice_id;
                        } else {
                            str_info += getTextResultType("por_cobrar");
                            if (obj.invoice_id){
                                str_info += " <div style='color:green;font-weight:bold;'> Invoice #" + obj.invoice_id + "</div>";
                            } else {
                                str_info += " <a href='#!' class='btn btn-sm btn-success btn-add-to-invoice' data-info='"+data_info+"'><i class='fas fa-plus'></i> Agregar a Factura </a> ";
                            }
                            
                        }
                    }
                    //
                    return "<div><strong>" + obj.new_total + "</strong></div>" + str_info;                    
                }},

                /*------------------------------------------- COMISIONES CBX -------------------------------------------*/

                {"name": "id", "data" : function(obj){   
                    //
                    if (obj.aplica_comision_cbx){
                        //
                        var str_info = "<hr style='padding:0;margin:0' />";
                        if (obj.is_comision_cbx_pagada){
                            str_info += getTextResultType("pagado");
                        } else {
                            str_info += getTextResultType("por_pagar");
                        }
                        //
                        return "<div><strong>" + parseFloat(obj.comisiones_cbx) + "</strong></div>" + str_info;
                    }
                    return 0;  
                }},               
                {"name": "id", "data" : function(obj){
                    var str_info = "";
                    //                    
                    if (obj.aplica_comision_cbx){
                        // 
                        str_info = "<hr style='padding:0;margin:0' />";
                        if (obj.is_venta_cbx_cobrada){
                            str_info += getTextResultType("cobrado");
                        } else {
                            str_info += getTextResultType("por_cobrar");
                        }                        
                    }
                    //
                    return "<div><strong>" + obj.new_total2 + "</strong></div>" + str_info;
                }},  

                /*------------------------------------------- END COMISIONES -------------------------------------------*/
                {"data": function(obj){
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        var data_info = getDAtaInfo(obj);
                        //console.log(obj, data_info);

                        //
                        var str_sned_ticket_btn = " <a href='#!' class='btn btn-sm btn-success btn-send-ticket' data-info='"+data_info+"'><i class='fas fa-send'></i> Send Ticket </a> ";


                        /* Si no es a credito y el cliente no ha aceptado */
                        if ( obj.a_credito ){
                            //
                            if ( obj.seller_accepted ){
                                //
                                str_btns += str_sned_ticket_btn;
                            } else {
                                str_btns += " <button type='button' class='btn btn-sm btn-success btn-send-confirmation-link' data-info='"+data_info+"'><i class='fas fa-envelope'></i> Send Link </button>";
                            }
                        } 

                        /* Si no es a credito y el cliente no ha pagado */
                        else {
                            //
                            if ( obj.sale_paid ){
                                //
                                str_btns += str_sned_ticket_btn;
                            } else {
                                str_btns += " <button type='button' class='btn btn-sm btn-success btn-pay-square' data-info='"+data_info+"'><i class='fas fa-credit-card'></i> Pay Square </button>";
                            }
                        }

                        
                        //str_btns += " <button type='button' class='btn btn-sm btn-danger btn-cancelar' data-info='"+data_info+"'><i class='fas fa-times'></i> Cancel </button>";


                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                {
                    "targets": [0, 3, 4, 6],
                    "orderable": false
                },
                {
                    "targets": "_all",
                    "searchable": false
                }
            ],
            hdrBtnsSearch: false,
            deferLoading: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onDataReady: function(data){
            //console.log("***onDataReady data: ", data);
            //
            $('#total_records').text(data.recordsTotal);
            $('#total_venta').text(data.sum_grand_total);
            $('#total_comisiones').text(data.sum_comisiones);
            $('#total_venta_sin_comisiones').text(data.sum_new_total);
            $('#total_comisiones_cbx').text(data.sum_comisiones_cbx);
            $('#total_venta_sin_comisiones_cbx').text(data.sum_new_total2);
        },
        onGridReady: function(opts){
            //console.log(opts);


            
            //
            $(".btn-add-to-invoice").click(function(e) {
                e.preventDefault();
                //
                var record_info = $(this).data("info");
                console.log(record_info);
                if (confirm("Agregar venta #" + record_info.id + " a factura de cliente")){
                    //
                    $.ajax({
                        type: 'POST',
                        url: app.admin_url + "/ventas/" + record_info.id + "/add-to-invoice",
                        success: function(response){
                            //
                            if (response && response.id){
                                alert("Added Ok");
                                $("#grid_section").DataTable().ajax.reload();
                            } else {
                                var err = (response.error) ? response.error : "error al agregar a factura";
                                alert(err);
                            }
                        },
                        error: function(err){
                            var err = (err) ? err : "error al agregar a factura";
                            alert(err);
                        }
                    });
                }                
            });

            //
            $(".btn-send-ticket").click(function(e) {
                e.preventDefault();
                //
                var record_info = $(this).data("info");
                console.log(record_info);
                if (confirm("enviar boleto a " + record_info.customer_name)){
                    //
                    $.ajax({
                        type: 'POST',
                        url: app.admin_url + "/ventas/" + record_info.id + "/send-ticket",
                        success: function(response){
                            //
                            if (response.main_msg && response.main_msg.success){
                                alert("Mensaje enviado Ok");
                            } else {
                                var err = (response.error) ? response.error : "error al enviar confirmacion";
                                alert(err);
                            }
                        },
                        error: function(err){
                            var err = (err) ? err : "error al enviar confirmacion";
                            alert(err);
                        }
                    });
                }                
            });



            //
            $(".btn-send-confirmation-link").click(function(e) {
                e.preventDefault();
                //
                var record_info = $(this).data("info");
                console.log(record_info);
                if (confirm("enviar confirmacion a " + record_info.customer_name)){
                    //
                    $.ajax({
                        type: 'POST',
                        url: app.admin_url + "/ventas/" + record_info.id + "/send-confirmation-link",
                        success: function(response){
                            //
                            if (response.main_msg && response.main_msg.success){
                                alert("Mensaje enviado Ok");
                                $("#grid_section").DataTable().ajax.reload();
                            } else {
                                var err = (response.error) ? response.error : "error al enviar confirmacion";
                                alert(err);
                            }
                        },
                        error: function(err){
                            var err = (err) ? err : "error al enviar confirmacion";
                            alert(err);
                        }
                    });
                }                
            });



            //
            $(".btn-pay-square").click(function(e) {
                e.preventDefault();
                
                //
                var record_info = $(this).data("info");
                console.log(record_info);

                //
                loadModalV2({
                    id: "modal-checkout",
                    modal_size: "lg",
                    data: record_info,
                    html_tmpl_url: "/app/sales/modals/checkout-2.html?v=" + dynurl(),
                    js_handler_url: "/app/sales/modals/checkout-2.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){

                        //
                        enable_btns();

                        //
                        $('#modal-title-2').html("Checkout");
                        $('.btnAdd2').html("<i class='fa fa-send'></i> Enviar");

                    }
                });


            });

            /*
            //
            $(".btn-sale-paid").click(function(e) {
                e.preventDefault();
                //
                var record_info = $(this).data("info");
                console.log(record_info);
                //
                $.ajax({
                    type: 'POST',
                    url: app.admin_url + "/ventas/" + record_info.id + "/pay",
                    success: function(response){
                        //
                        $("#grid_section").DataTable().ajax.reload();
                    },
                    error: function(){
                        //
                    }
                });
            });
            */


            //
            $(".btn-view-ocupacion").click(function(e) {
                e.preventDefault();

                //
                var record_info = $(this).data("info");
                //console.log(record_info); return;


                // 
                loadModalV2({
                    id: "modal-bulk-update",
                    modal_size: "xl",
                    data: {
                        salida_id: record_info.salida_id,
                        fecha_hora_salida: record_info.fecha_hora_salida,
                        fecha_hora_llegada: record_info.fecha_hora_llegada
                    },
                    html_tmpl_url: "/app/sales/modals/view-ocupacion.html"+dynurl(),
                    js_handler_url: "/app/sales/modals/view-ocupacion.js"+dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });

            });



        },
        onReload: function(){
            
            // On reload reset counters
            vent.clearCounters2();

        },
        onSectionReady: function(opts){






            //
            $("#month_date").datePickerTask({
                storeId: "month_date",
                opts:{
                    autoclose: true,
                    defaultDate: moment(),
                    viewMode: "months",
                    ignoreReadonly: false,
                    format: 'MMMM-YYYY'
                },
                onChange: function(date){
                    filterGrid();
                }
            });

            
            
            //
            $(".btnClear").click(function(){
                $("#filter_comision_venta").val("");
                $("#filter_venta").val("");
                $("#filter_comision_cbx").val("");
                $("#filter_venta_sin_cbx").val("");
                $("#filter_dominio_id").val("");
                $("#filter_status_venta").val("");
                filterGrid();
            });


            
            //
            block("#comisiones_container", "");
            block("#comisiones_cbx_container", "");

            //
            $("#aplica_comisiones").click(function(e) {
                //
                var this_val = $(this).is(":checked");
                //
                if (this_val){
                    $("#comisiones_container").unblock();
                } else {
                    block("#comisiones_container", "");
                }
                //
                filterGrid();
            });


            //
            $("#aplica_comisiones_cbx").click(function(e) {
                //
                var this_val = $(this).is(":checked");
                //
                if (this_val){
                    $("#comisiones_cbx_container").unblock();
                } else {
                    block("#comisiones_cbx_container", "");
                }
                //
                filterGrid();
            });


            //
            $("#filter_dominio_id, #filter_status_venta, #filter_comision_venta, #filter_venta, #filter_comision_cbx, #filter_venta_sin_cbx").change(function(){
                filterGrid();
            });



            //
            $.S2Ext({
                S2ContainerId: "filter_customer_id",
                placeholder: "...buscar cliente",
                //language: "es",
                language:{
                    noResults:function(){return""},
                    searching:function(){return""}
                },
                allowClear: true,
                minimumInputLength: 2,
                minimumResultsForSearch: "-1",
                remote: {
                    qs: function(){
                        return {};
                    },
                    url: app.admin_url + "/customers/search",
                    dataType: 'json',
                    delay: 250,
                    processResults: function (response, page) {
                        return {
                            results: response
                        };
                    },
                    cache: false,
                    templateResult: function(item){
                        if (item.loading) {
                            return item.text;
                        }
                        var str_email = item.email ? item.email + " - " : "";
                        var str_company_name = (item.id && item.company_name) ? item.company_name + " - " : "";
                        return "(" + item.id + ") " + str_email + str_company_name + item.name;
                    },
                    templateSelection: function(item){
                        if (item.id){
                            var str_email = item.email ? item.email + " - " : "";
                            var str_company_name = (item.id && item.company_name) ? item.company_name + " - " : "";
                            return "(" + item.id + ") " + str_email + str_company_name + item.name;
                        }
                        return item.text;
                    }
                },
                onChanged: function(sel_id, data){
                    console.log(sel_id, data);
                    //
                    if (data && data.id){
                        filterGrid();
                    }
                },
                onClose: function(){
                    //
                    filterGrid();
                }
            });


            
            

            setTimeout(function(){
                filterGrid();
            }, 1000);

        }
    });









    /*
    $(".btnReport").click(function(e) {
        e.preventDefault();




        //
        var from_date = $("#from_date").datepicker("getDate"),
            from_date_val = moment(from_date).format("YYYY-MM-DD");
        //
        var to_date = $("#to_date").datepicker("getDate"),
            to_date_val = moment(to_date).format("YYYY-MM-DD");


        var filter_status_id = $("#filter_status_id").val();
        var filter_pickup_delivery = $("#filter_pickup_delivery").val();




        //
        var rpt_url = app.admin_url + "/sales/report?filter_status_id="+filter_status_id+"&filter_pickup_delivery="+filter_pickup_delivery+query_string_pickup_store;
        console.log(rpt_url);

        //
        disable_btns();
        //block("#sales_report", "generating file...");

        //
        $.ajax({
            type: 'GET',
            url: rpt_url,
            success: function(response){
                //
                enable_btns();
                //unblock("#sales_report");
                //
                if ( response && response.download_file_path){

                    //
                    $.fileDownload(response.download_file_path, {
                        successCallback: function (url) {
                            //
                            enable_btns();
                            //unblock("#sales_report");
                        },
                        failCallback: function (html, url) {
                            //
                            enable_btns();
                            //unblock("#sales_report");
                        }
                    });
                }
                //
                else if (response.error){
                    // xx
                }
            },
            error: function(){
                //
                enable_btns();
                //
                // xxx
            }
        });
    });
     */



    //
    $(".btnUpdateStatus").click(function(e) {
        e.preventDefault();

        //
        loadModalV2({
            id: "modal-bulk-update",
            modal_size: "lg",
            data: {},
            html_tmpl_url: "/app/common/bulk-update-status/index.html",
            js_handler_url: "/app/common/bulk-update-status/index.js",
            onBeforeLoad: function(){
                disable_btns();
            },
            onInit: function(){
                enable_btns();
            }
        });

    });





    //
    app.onResultsReady = "onResultsReady";
    app.onSelectedItem = "onSelectedItem";






    //
    app.loadBusInfo = function(selected_item){
        //
        loadModalV2({
            id: "modal-display-bus",
            modal_size: "lg",
            data: selected_item,
            html_tmpl_url: "/app/sales/modals/s3-bus-seats.html?v="+dynurl(),
            js_handler_url: "/app/sales/modals/s3-bus-seats.js?v="+dynurl(),
            onBeforeLoad: function(){
                disable_btns();
            },
            onInit: function(){
                enable_btns();
            }
        });

    }




    //
    app.selected_item = null;




    //
    $(document).on(app.onSelectedItem, function(evt, selected_item) {
        evt.stopPropagation();
        console.log("---selected_item: ", selected_item);
        //
        app.selected_item = selected_item;
        //
        app.loadBusInfo(selected_item);
    });




    // 
    $(document).on(app.onResultsReady, function(evt, results) {
        evt.stopPropagation();
        //
        loadModalV2({
            id: "modal-display-results",
            modal_size: "lg",
            data: {
                results: results,
                bindFuncName: app.onSelectedItem
            },
            html_tmpl_url: "/app/sales/modals/s2-display-results.html?v="+dynurl(),
            js_handler_url: "/app/sales/modals/s2-display-results.js?v="+dynurl(),
            onBeforeLoad: function(){
                disable_btns();
            },
            onInit: function(){
                enable_btns();
            }
        });
    });




    //
    vent.updateShowBtn();


    //
    $(".btnAddSale").click(function(e) {
        e.preventDefault();

        //
        loadModalV2({
            id: "modal-search-origenes-destinos",
            modal_size: "lg",
            data: {
                bindFuncName: app.onResultsReady
            },
            html_tmpl_url: "/app/sales/modals/s1-select-origen-destino.html?v="+dynurl(),
            js_handler_url: "/app/sales/modals/s1-select-origen-destino.js?v="+dynurl(),
            onBeforeLoad: function(){
                disable_btns();
            },
            onInit: function(){
                enable_btns();
            }
        });

    });



    //
    $(".btnShowSale").click(function(e) {
        e.preventDefault();
        //
        vent.showSaleModal();
    });


    

    //
    $(".btnRemoveSale").click(function(e) {
        e.preventDefault();

        //
        if (confirm("Eliminar venta?")){

            //
            var arr_sale_items = getLSItem("arr_sale_items");
            arr_sale_items = (arr_sale_items && arr_sale_items.length) ? arr_sale_items : [];

            //
            $.ajax({
                type:'POST',
                url: app.public_url + "/utils/clear",
                dataType: "json",
                data: JSON.stringify({
                    arr_sale_items: arr_sale_items,
                    visitor_id: app.visitor_id,
                    temp_sale_id: app.temp_sale_id
                }),
                beforeSend: function( xhr ) {
                    //xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
                    disable_btns();
                    preload(true);
                },
                contentType: "application/json",
                success:function(response){
                    //console.log(response); return;
                    //
                    enable_btns();

                    //
                    if (response && response.success){

                        //
                        app.Toast.fire({ icon: 'success', title: "Venta eliminada correctamente" });
                        localStorage.removeItem("arr_sale_items");
                        //
                        vent.updateShowBtn();

                        //
                        app.temp_sale_id = vent.generateTempSaleId();
                        console.log("new temp_sale_id: ", app.temp_sale_id);


                    }
                    //
                    else if (response.error){
                        app.Toast.fire({ icon: 'error', title: response.error});
                    }
                    //
                    else {
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }

                },
                error: function(){
                    enable_btns();
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });

                }
            });


        }

    });









    //
    function filterGrid(){

        //
        var query_string = "?sd=" + moment($('#month_date').datetimepicker('date')).format("YYYY-MM-DD") +
            "&a=" + $("#filter_comision_venta").val() +
            "&b=" + $("#filter_venta").val() +
            "&c=" + $("#filter_comision_cbx").val() +
            "&d=" + $("#filter_venta_sin_cbx").val() +
            "&e=" + $("#filter_dominio_id").val() +
            "&f=" + $("#filter_status_venta").val() +
            "&acom=" + ($("#aplica_comisiones:checked").val() ? 1 : 0) +
            "&acomcbx=" + ($("#aplica_comisiones_cbx:checked").val() ? 1 : 0) +
            "&cid=" + $("#filter_customer_id").val();

        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/sales" + query_string);
        $("#grid_section").DataTable().ajax.reload();
        
    }



    //
    function bindSelectOptions(elem_id){
        //
        $(elem_id).change(function(){
            //
            var elem_val = $(this).val();
            console.log(elem_id + "_ses val changed:", elem_val);
            localStorage.setItem(elem_id + "_ses", elem_val);
            //
            filterGrid();
        });
        //
        var ses_elem_val = localStorage.getItem(elem_id + "_ses");
        console.log(elem_id + "_ses val:", ses_elem_val);
        if (ses_elem_val){
            $(elem_id).val(ses_elem_val);
        }
    }


    //
    bindSelectOptions("#filter_sale_type_id");
    bindSelectOptions("#filter_status_id");



    //
    $(".btnDownloadReport").click(function(e) {
        e.preventDefault();


        //
        var filter_status_id = $("#filter_status_id").val();
        var filter_sale_type_id = $("#filter_sale_type_id").val();
        var filter_product_type = $("#filter_product_type").val();


        //
        var this_date = $('#month_date').datetimepicker('date');
        var filter_week_date = moment(this_date).format("YYYY-MM-DD")
        //
        var rpt_url = app.admin_url + "/sales/report?filter_status_id=" + filter_status_id + "&filter_week_date=" + filter_week_date + "&filter_sale_type_id=" + filter_sale_type_id;
        console.log(rpt_url);

        //
        disable_btns();
        //block("#sales_report", "generating file...");

        //
        $.ajax({
            type: 'GET',
            url: rpt_url,
            success: function(response){
                //
                enable_btns();
                //unblock("#sales_report");
                //
                if ( response && response.download_file_path){

                    //
                    $.fileDownload(response.download_file_path, {
                        successCallback: function (url) {
                            //
                            enable_btns();
                            //unblock("#sales_report");
                        },
                        failCallback: function (html, url) {
                            //
                            enable_btns();
                            //unblock("#sales_report");
                        }
                    });
                }
                //
                else if (response.error){
                    // xx
                }
            },
            error: function(){
                //
                enable_btns();
                //
                // xxx
            }
        });


    });




    // 
    app.visitor_id = null;
    //
    FingerprintJS.load().then(fp => {
        fp.get().then(result => {
            app.visitor_id = result.visitorId;
            console.log("visitorId: ", app.visitor_id);
        });
    });


    
    
    //
    app.temp_sale_id = getLSItem("temp_sale_id");
    //
    if (!app.temp_sale_id){
        app.temp_sale_id = vent.generateTempSaleId();
    }
    console.log("temp_sale_id: ", app.temp_sale_id);
    



    
    //
    //console.log(square_app_id, square_loc_id, square_mode);  





})(jQuery);