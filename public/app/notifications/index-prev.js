(function ($) {
    'use strict';






    /*
    *
    * SECCION VENTAS
    *
    * */
    app.createSection({
        section_title: "Notificaciones",
        section_title_singular: "Notificacion",
        scripts_path: "/app/notifications",
        endpoint_url: app.admin_url + "/notifications",
        gridOptions:{
            btnExpandir: true,
            btnColapsar: true,
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){
                    var str_info = "";
                        if ( parseInt(obj.sale_type_id) === app.PROD_TYPE_STORE_ID ){
                            str_info += "<div style='font-weight: bold;'>" + obj.company_name + "</div>";
                            str_info += "<strong>" + obj.customer_name + "</strong><br />" + obj.email + " (+"+obj.phone_number + ")";
                        } else if ( parseInt(obj.sale_type_id) === app.PROD_TYPE_CUSTOMER_ID ){
                            str_info += "<h4 style='font-weight: bold;'>" + obj.customer_name + "</h4>" + obj.email + " (+"+obj.phone_number + ")";
                        }
                        //
                        return str_info;
                    }},
                {"width": "300px","data" : function(obj){
                        //
                        var str_vals = "";
                        //
                        $.each(obj.sale_items, function(idx, item){
                            //
                            var str_periodicidad = "";
                            if (item.periodicidad_id){
                                str_periodicidad = "&nbsp; <span style='color:green;'>(" + item.periodicidad + ")</span><br />";
                            }
                            str_vals += "<div style=''>• <strong>(x" + parseInt(item.qty) + ")</strong> " + item.item_info + str_periodicidad + "</div>";
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
                            str_info += "<li><strong style='color:green;'>"+item.payment_type+"</strong> "+item.amount + " <small style='color:gray;'>" + item.tipo_moneda + "</small> " + item.payment_status +"</li>";
                        });
                        //
                        str_info += "</ul>";
                        return str_info;
                    }},
                {"data" : function(obj){ return fmtDateEng(obj.datetime_created.date); }},
                {"data" : function(obj){
                        //
                        var str_notes = "";
                        //
                        if (obj.status_notes){
                            str_notes = "<br /><span style='font-size:14px;color:orangered;font-weight: bold;'>" + obj.status_notes + "</span>";
                        }
                        return obj.status_title + str_notes;
                    }},
                {"width": "200px", "data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        var print_url = app.admin_url + "/sales/" + obj.id + "/preview";
                        //
                        str_btns += " <a href='"+print_url+"' target='_blank' class='btn btn-sm btn-primary' data-info='"+data_info+"'><i class='fas fa-print'></i></a> ";
                        str_btns += " <a href='#!' class='btn btn-sm btn-success btn-update-status' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i> Status <strong>(" + obj.cant_status + ")</strong></a> ";
                        str_btns += " <a href='#!' class='btn btn-sm btn-info btn-notifications' data-info='"+data_info+"'><i class='fas fa-comment'></i> Notific <strong>(" + obj.cant_msgs + ")</strong></a> ";
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
            //
            $('#active').attr("checked", true);
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
            $(".btn-update-status").click(function(e) {
                e.preventDefault();


                //var record_info = grid_table.row( getTrRespElem(this) ).data();
                var record_info = $(this).data("info");
                //console.log(record_info); return;

                //
                preload(".section-preloader, .overlay", true);
                disable_btns();

                //
                loadModalV2({
                    id: "modal-update-status",
                    modal_size: "lg",
                    data: record_info,
                    html_tmpl_url: "/app/common/update-status/index.html",
                    js_handler_url: "/app/common/update-status/index.js",
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });

            });




            //
            $(".btn-update-kitchen").click(function(e) {
                e.preventDefault();


                //var record_info = grid_table.row( getTrRespElem(this) ).data();
                var record_info = $(this).data("info");
                //console.log(record_info); return;

                //
                preload(".section-preloader, .overlay", true);
                disable_btns();

                //
                loadModalV2({
                    id: "modal-update-status",
                    modal_size: "lg",
                    data: record_info,
                    html_tmpl_url: "/app/common/update-kitchen/index.html",
                    js_handler_url: "/app/common/update-kitchen/index.js",
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });

            });



            //
            $(".btn-notifications").click(function(e) {
                e.preventDefault();


                //var record_info = grid_table.row( getTrRespElem(this) ).data();
                var record_info = $(this).data("info");
                //console.log(record_info); return;

                //
                preload(".section-preloader, .overlay", true);
                disable_btns();

                //
                loadModalV2({
                    id: "modal-notifications",
                    modal_size: "lg",
                    data: record_info,
                    html_tmpl_url: "/app/common/notifications/index.html",
                    js_handler_url: "/app/common/notifications/index.js",
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });

            });




        },
        onSectionReady: function(opts){






            //
            $("#event_date").datePickerTask({
                storeId: "event_date",
                opts:{
                    daysOfWeekDisabled: [0, 2, 3, 4, 5, 6],
                    autoclose: true,
                    defaultDate: moment(),
                    ignoreReadonly: false,
                    format: 'MMMM-DD-YYYY'
                },
                onChange: function(date){
                    filterGrid();
                }
            });


            //
            loadSelectAjax({
                id: "#filter_status_id",
                url: app.admin_url + "/sale-status/list",
                parseFields: function(item){
                    return item.status_title;
                },
                saveValue: true,
                prependEmptyOption: true,
                emptyOptionText: "--all",
                enable: true,
                onChange: function(){
                    filterGrid();
                }
            });



            //
            loadSelectAjax({
                id: "#filter_sale_type_id",
                url: app.admin_url + "/sys/tipos-productos-servicios/list",
                parseFields: function(item){
                    return item.tipo;
                },
                saveValue: true,
                prependEmptyOption: true,
                emptyOptionText: "--all",
                enable: true,
                onChange: function(){
                    filterGrid();
                },
                onReady: function(){
                    setTimeout(function(){
                        filterGrid();
                    }, 1000);
                }
            });

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
    function filterGrid(){
        //
        var filter_status_id = $("#filter_status_id").val();
        var filter_sale_type_id = $("#filter_sale_type_id").val();
        //
        var this_date = $('#event_date').datetimepicker('date');
        var filter_week_date = moment(this_date).format("YYYY-MM-DD")
        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/notifications?filter_status_id=" + filter_status_id + "&filter_week_date=" + filter_week_date + "&filter_sale_type_id=" + filter_sale_type_id);
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
        var this_date = $('#event_date').datetimepicker('date');
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





})(jQuery);