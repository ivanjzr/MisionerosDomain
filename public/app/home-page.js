$(document).ready(function(){


    


       



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
                {"data" : function(obj){
                    //
                    var str_info = "";
                    //                        
                    if (obj.company_name){
                        str_info += "<div style='font-weight: bold;'> <small>(" + obj.company_name + ")</small> " + obj.company_name + "</div>";
                    }
                    //
                    str_info += "<strong>" + obj.customer_name + "</strong><br />" + obj.email + " (+"+obj.phone_number + ")";                        
                    //
                    return str_info;
                    }},
                {"width": "300px","data" : function(obj){
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
                            str_vals += "<h4 style='padding:0;margin:0;'> " + item.passanger_name + " Lugar #" + item.num_asiento + "</h4>";
                            str_vals += "<div style=''>• " + item.origen_info + " - " +  momentFormat(item.fecha_hora_salida.date, "DD MMM YY h:mm A") + "</div>";
                            str_vals += "<div style=''>• " + item.destino_info + " - " + momentFormat(item.fecha_hora_llegada.date, "DD MMM YY h:mm A") + "</div>";

                            //
                            if (parseFloat(item.costo_ext_salida) || parseFloat(item.costo_ext_llegada)){
                                //
                                str_vals += "<div style=''>• precio: " + item.costo_origen_destino + "</div>";
                                //
                                if (item.costo_ext_salida){
                                    str_vals += "<div style=''>• precio extension salida: " + item.costo_ext_salida + "</div>";
                                }
                                //
                                if (item.costo_ext_llegada){
                                    str_vals += "<div style=''>• precio extension llegada: " + item.costo_ext_llegada + "</div>";
                                }
                                //
                                str_vals += "<div style=''>• Total: " + item.total + "</div>";
                            } else {
                                str_vals += "<div style=''>• Total: " + item.total + "</div>";
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
                            

                            str_vals += " <a href='#!' class='btn btn-sm btn-info btn-view-ocupacion' data-info='"+data_info+"'><i class='fas fa-route'></i> Viaje Info </a> ";

                            str_vals += "<hr />";
                        });
                        //
                        str_vals += "";
                        return str_vals
                }},
                //
                {"data" : function(obj){ 
                        //
                        var str_info = "<ul>";
                        //
                        str_info += "<li><strong>Subtotal: </strong> " + obj.sub_total +"</li>";
                        //
                        if ( obj.discount_percent > 0 || obj.tax_amount > 0  ){
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
                        str_info += "<li><strong>Total: </strong> " + obj.grand_total +"</li>";
                        //
                        str_info += "</ul>";
                        return str_info;
                    }},
                {"data" : function(obj){
                        //
                        var str_info = "<ul>";
                        //
                        $.each(obj.sale_payments, function(idx, item){
                            str_info += "<li><strong style='color:green;'>"+item.payment_type+"</strong> "+item.amount + " <small style='color:gray;'>" + item.tipo_moneda + "</small> " + item.payment_status + " <small style='color:gray;'>(" + item.transaction_id + ")</small></li>";
                        });
                        //
                        str_info += "</ul>";
                        return str_info;
                    }},
                {"data" : function(obj){ return fmtDateEng(obj.datetime_created.date); }},
                {"data" : function(obj){
                    var str_info = "";
                    if (obj.a_credito){

                        str_info = " A Credito <br />";

                        if (obj.seller_accepted){
                            str_info += "<div style='color:green;'><i class='fas fa-check' style='color:green;'></i> accepted </div>";
                        } else {
                            str_info += "<div style='color:orangered;'> sin-aceptar </div>";
                        }
                    }
                    return str_info;
                }},
                {"name": "sale_paid", "data" : function(obj){ return fmtActive3(obj.sale_paid, true); }},
                {"width": "200px", "data" : function(obj){
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        //var print_url = app.admin_url + "/sales/" + obj.id + "/preview";

                        //
                        var data_info = JSON.stringify({
                            id: obj.id,
                            customer_id: obj.customer_id,
                            customer_name: obj.customer_name,
                            email: obj.email,
                            phone_number: obj.phone_number,
                            grand_total: obj.grand_total,
                        });
                        console.log(obj, data_info);
                        //
                        //str_btns += " <a href='"+print_url+"' target='_blank' class='btn btn-sm btn-primary' data-info='"+data_info+"'><i class='fas fa-print'></i></a> ";
                        //str_btns += " <a href='#!' class='btn btn-sm btn-success btn-update-status' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i> Status <strong>(" + obj.cant_status + ")</strong></a> ";

                        /* Si es a credito y el cliente no ha aceptado */
                        if ( obj.a_credito ){                            
                            if ( !obj.seller_accepted ){
                                str_btns += " <button type='button' class='btn btn-sm btn-success btn-send-confirmation-link' data-info='"+data_info+"'><i class='fas fa-envelope'></i> Send Link </button>";
                            }
                        } 
                        /* Si no es a credito y el cliente no ha pagado */
                        else {
                            if ( !obj.sale_paid ){
                                str_btns += " <button type='button' class='btn btn-sm btn-success btn-pay-square' data-info='"+data_info+"'><i class='fas fa-credit-card'></i> Pay Square </button>";
                            }
                        }
                        
                        //str_btns += " <button type='button' class='btn btn-sm btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
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
            hdrBtnsSearch: true,
            deferLoading: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){

        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){


            //


        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){





        },
        onGridReady: function(opts){



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





            
            $(".btnAddSale").show();



        }
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
        vent.showSaleModal("hp");
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
                url: app.admin_url + "/ventas/clear",
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
        var filter_status_id = $("#filter_status_id").val();
        var filter_sale_type_id = $("#filter_sale_type_id").val();
        //
        var this_date = $('#month_date').datetimepicker('date');
        var fsd = moment(this_date);
        var fed = fsd.clone().endOf('month');

        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/sales?filter_status_id=" + filter_status_id + "&fsd=" + fsd.format("YYYY-MM-DD")  + "&fed=" + fed.format("YYYY-MM-DD") + "&filter_sale_type_id=" + filter_sale_type_id);
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
    console.log(square_app_id, square_loc_id, square_mode);  








})