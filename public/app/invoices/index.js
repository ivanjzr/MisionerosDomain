(function ($) {
    'use strict';



    //
    function filterGrid(){
        //
        var search_customer_id = $("#search_customer_id").val();
        //
        var str_qs = "";

        if (search_customer_id){
            str_qs += "&cid="+search_customer_id;
        }
        console.log(str_qs);

        
        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/invoices?a=1" + str_qs);
        $("#grid_section").DataTable().ajax.reload();
    }



    function setCustomerData(data){
        //
        $(".customer_id")
            .val(data.id)
            .text(data.tipo + " ID: " + data.id);
        // 
        $("#company_name").val(data.company_name);
        $("#customer_name").val(data.name);
        $("#email").val(data.email);
        $("#phone_country_id").val(data.phone_country_id);
        $("#phone_number").val(data.phone_number);
        //
        $("#por_cobrar_container").show();
        //
        if (data.por_cobrar){            
            $("#por_cobrar_info").html("$" + data.por_cobrar);
        } else {
            $("#por_cobrar_info").html("cliente no tiene ventas pendientes");
        }
        
    }


    function resetCustomerData(){
        //
        $("#customer_id").empty();
        $("#customer_id").trigger("change");
        //
        $(".customer_id")
            .val("")
            .text("");
        $("#company_name").val("");
        $("#customer_name").val("");
        $("#email").val("");
        $("#phone_country_id").val("");
        $("#phone_number").val("");
        //
        $("#por_cobrar_container").hide();
        $("#por_cobrar_info").html("");
    }




    //
    app.sendInvoice = function(invoice_id, callback){
        //
        disable_btns();
        //
        $.ajax({
            type: 'POST',
            url: app.admin_url + "/invoices/" + invoice_id + "/send",
            success: function(response){                
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                //
                if (response && response.id){
                    callback();                    
                } else {
                    var err = (response.error) ? response.error : "error al enviar mensaje";
                    app.Toast.fire({ icon: 'error', title: err });
                }
            },
            error: function(err){
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                //
                var err = (err) ? err : "error al enviar confirmacion";
                app.Toast.fire({ icon: 'error', title: err });
            }
        });
    }

    //
    app.createSection({
        section_title: "Invoices",
        section_title_singular: "Invoice",
        modalAddHtmlName: "add-record.html",
        modalEditHtmlName: "edit-record.html",
        scripts_path: "/app/invoices",
        endpoint_url: app.admin_url + "/invoices",
        editFieldName: "name",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){ 
                    var str_company_name = (obj.company_name) ? "<strong>" + obj.company_name + "</strong> - " : "";
                    return str_company_name + obj.customer_name + " <small>( " + obj.email + " / " + obj.phone_number + " )</small>"; 
                }},
                {width:300, "data" : function(obj){ 
                    //
                    var str_info = "";
                    //
                    str_info += "<table class='table table-sm table-stripped'>";
                    str_info += "<tr>";
                    str_info += "<th>Boleto</th>";
                    str_info += "<th>Pasajero</th>";
                    str_info += "<th>Origen</th>";
                    str_info += "<th>Destino</th>";
                    str_info += "<th>Autobús</th>";
                    str_info += "<th>Costo Salida</th>";
                    str_info += "<th>Comisiones</th>";
                    str_info += "<th>Total</th>";
                    str_info += "</tr>";
                    //
                    if (obj.arr_sales && obj.arr_sales.length){                        

                        //                        
                        $.each(obj.arr_sales, function(idx, item){

                            //
                            var sale_id = item.id;
                            /*
                            $arr_items .= "<td> seat #{$} <br /> {$} ({$})  </td>";
                            $arr_items .= "<td> {$} - {$} </td>";
                            $arr_items .= "<td> {$} - {$} </td>";
                            $arr_items .= "<td> {$} </td>";
                            //
                            $arr_items .= "<td><div> {$str_costo_ext_salida}{$str_costo_ext_llegada}{$} <br /> {$costo_origen_destino} {$} </div></td>";
                            $arr_items .= "<td><div> {$comisiones} </div></td>";
                            $arr_items .= "<td><div> {$new_total} </div></td>";
                            */
                            //                        
                            if (item.salidas_ocupacion && item.salidas_ocupacion.length){
                                $.each(item.salidas_ocupacion, function(idx2, item2){                                    
                                    //
                                    var str_costo_ext_salida = (item2['costo_ext_salida'] > 0) ? "Ext Salida: " + item2['costo_ext_salida'] + "<br />" : '';
                                    var str_costo_ext_llegada = (item2['costo_ext_llegada'] > 0) ? "Ext Llegada: " + item2['costo_ext_llegada'] + "<br />" : ''; 
                                    //
                                    str_info += "<tr>";
                                    str_info += "<td> #" + sale_id + "-" + item2.id + "</td>";
                                    str_info += "<td>" + item2.num_asiento + " - " + item2.passanger_name + " - " +  item2.passanger_age + "</td>";
                                    str_info += "<td>" + item2.origen_info + " - " + moment(item2.fecha_hora_salida.date).format("DD MMM YYYY") + "</td>";
                                    str_info += "<td>" + item2.destino_info + " - " + moment(item2.fecha_hora_llegada.date).format("DD MMM YYYY") + "</td>";
                                    str_info += "<td>" + item2.autobus_clave + "</td>";
                                    str_info += "<td>" + str_costo_ext_salida + str_costo_ext_llegada + item2.total + "<br />" + item2.calc_info + "</td>";
                                    str_info += "<td>" + item2.comisiones + "</td>";
                                    str_info += "<td>" + item2.new_total + "</td>";
                                    str_info += "</tr>";                                    
                                });
                            }
                        });
                        
                        //
                        str_info += "</table>";
                    }

                    //
                    return str_info;
                }},
                {"name": "sum_venta", "data" : "sum_venta"},
                {"name": "sum_comisiones", "data" : "sum_comisiones"},
                {"name": "sum_por_cobrar", "data" : "sum_por_cobrar"},                
                {"name": "status", "data" : function(obj){ 
                    //
                    return "<strong style='color:green;'>" + obj.invoice_status + "</strong>";
                }},
                {"name": "status_datetime", "data" : function(obj){ 
                    if ( obj.status_datetime && obj.status_datetime.date ){
                        return fmtDateEng(obj.status_datetime.date); 
                    }
                    return null;                    
                }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";

                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-success btn-cambiar-status' data-info='"+data_info+"'><i class='fas fa-list'></i> Status </button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-send-invoice' data-info='"+data_info+"'><i class='fas fa-rocket'></i> Enviar </button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-edit' data-info='"+data_info+"'><i class='fas fa-comments'></i></button> ";
                        str_btns += " <a href='"+obj.invoice_url+"?pdf=1' target='_blank' class='btn btn-sm btn-info' data-info='"+data_info+"'><i class='fas fa-print'></i></a> ";
                        //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i> </button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                {
                    "targets": [0, 2, 3, 4, 5, 6, 7],
                    "orderable": false
                },
                {
                    "targets": "_all",
                    "searchable": false
                }
            ],
            deferLoading: true,
            hdrBtnsSearch: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){


            //
            $.S2Ext({
                S2ContainerId: "customer_id",
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
                    qs: null,
                    url: app.admin_url + "/customers/search-seller",
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
                        return item.id + " - " + str_email + str_company_name + item.name;
                    },
                    templateSelection: function(item){
                        if (item.id){
                            var str_email = item.email ? item.email + " - " : "";
                            var str_company_name = (item.id  && item.company_name) ? item.company_name + " - " : "";
                            return item.id + " - " + str_email + str_company_name + item.name;
                        }
                        return item.text;
                    }
                },
                onChanged: function(sel_id, data){
                    //
                    console.log(sel_id, data);
                    //
                    if (data && data.id){
                        setCustomerData(data);                    
                    } else {
                        resetCustomerData();  
                    }
                },
                onClose: function(){
                    //
                    var customer_id = $("#customer_id").val();
                    console.log(customer_id);
                    //
                    if (!customer_id){
                        resetCustomerData();  
                    }
                }
            });


            
            //
            $("#modal-title-2").text("Crear Invoice");
            
        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){


            //
            $('#notes').val(section_data.notes);

            //
            $("#modal-title-2").text("Notas Invoice #" + section_data.id);
            
        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){



            

            //
            loadSelectAjax({
                id: "#phone_country_id",
                url: app.public_url + "/paises/list",
                parseFields: function(item){
                    return "+" + item.phone_cc + " (" + item.abreviado + ")";
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                default_value: ((section_data && section_data.phone_country_id) ? section_data.phone_country_id : false),
                enable: true
            });

            
            
            // def focus
            $("#company_name").focus();

        },
        onGridReady: function(opts){



            //
            $(".btn-cambiar-status").click(function(e){
                e.preventDefault();
                //
                var data_info = $(this).data("info");
                //
                loadModalV2({
                    id: "modal-cambiar-status",
                    modal_size: "md",
                    data: data_info,
                    html_tmpl_url: "/app/invoices/modals/cambiar-status.html" + dynurl(),
                    js_handler_url: "/app/invoices/modals/cambiar-status.js" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();                        
                    }
                });

            });


            

            //
            $(".btn-send-invoice").click(function(e) {
                e.preventDefault();
                //
                var record_info = $(this).data("info");
                console.log(record_info);
                if (confirm("Enviar Invoice #" + record_info.id + " a " + record_info.customer_name + "?")){
                    
                    //
                    app.sendInvoice(record_info.id, function(){
                        app.Toast.fire({ icon: 'success', title: "Mensaje enviado correctamente" });
                    });

                }                
            });


            

        },
        onSectionReady: function(opts){



            //
            setTimeout(function(){
                filterGrid();
            }, 1000)


        }
    });


    //
    $.S2Ext({
        S2ContainerId: "search_customer_id",
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
                return {}
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
                        return item.id + " - " + str_email + str_company_name + item.name;
            },
            templateSelection: function(item){
                if (item.id){
                    var str_email = item.email ? item.email + " - " : "";
                    var str_company_name = (item.id && item.company_name) ? item.company_name + " - " : "";
                    return item.id + " - " + str_email + str_company_name + item.name;
                }
                return item.text;
            }
        },
        onChanged: function(sel_id, data){
            //
            console.log(sel_id, data);
            filterGrid();

        },
        onClose: function(){
            //
            filterGrid();
        }
    });


    
    



})(jQuery);