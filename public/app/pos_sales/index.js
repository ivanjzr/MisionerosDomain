(function ($) {
    'use strict';


    //
    let defaultPosItem = null;
    let qs_pos_id = (URI().search(true).pid);
    let default_pos_id = (qs_pos_id && $.isNumeric(qs_pos_id)) ? qs_pos_id : false;


    // 
    app.createSection({
        section_title: "Ventas",
        section_title_singular: "Venta",
        scripts_path: "/app/pos_sales",
        endpoint_url: app.admin_url + "/pos/sales",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},                
                {"name" : "id", "data" : "id"},
                {"name": "datetime_created", "data" : function(obj){ 
                    return moment(obj.datetime_created.date).format('DD/MM/YY hh:mm A'); 
                }},
                {"name" : "customer_id", "data" : function(obj){ 
                    if (obj.customer_id){
                        return obj.customer_name + " - " + obj.email;
                    }
                    return "--";
                }},
                {"name" : "grand_total", "data" : function(obj){ 
                    return '$' + parseFloat(obj.grand_total || 0).toFixed(2); 
                }},
                {"name" : "total_paid_efectivo", "data" : function(obj){ 
                    return '$' + parseFloat(obj.total_paid_efectivo || 0).toFixed(2); 
                }},
                {"name" : "total_paid_tarjeta", "data" : function(obj){ 
                    return '$' + parseFloat(obj.total_paid_tarjeta || 0).toFixed(2); 
                }},
                {"name" : "total_paid_usd_amount", "data" : function(obj){ 
                    return '$' + parseFloat(obj.total_paid_usd_amount || 0).toFixed(2) + ' USD'; 
                }},
                {"name" : "change_amount", "data" : function(obj){ 
                    var change = parseFloat(obj.change_amount || 0);
                    var changeClass = change > 0 ? 'text-warning' : '';
                    return '<span class="' + changeClass + '">$' + change.toFixed(2) + '</span>'; 
                }},
                {"name" : "total_comissions", "data" : function(obj){ 
                    return '$' + parseFloat(obj.total_comissions || 0).toFixed(2) + ' MXN'; 
                }},                                
                {"name" : "created_user_name", "data" : function(obj){ 
                    return obj.created_user_name;
                }},
                {"name" : "pos_register_id", "data" : function(obj){ 
                    return obj.pos_name + " " + " #" + obj.pos_register_id; 
                }},
                {"name" : "notes", "data" : "notes"},
                {"data" : function(obj){
                    var data_info = JSON.stringify({});
                    var str_btns = "<div class='text-center'>";
                    str_btns += " <a href='#' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-eye'></i></a> ";
                    str_btns += "</div>";
                    return str_btns;
                }},
            ],
            columnDefs: [
                { "targets": [0, 2, 4, 5, 6, 7, 8, 9, 10, 11, 12], "orderable": false },
                { "targets": [4, 5, 6, 7, 8], "className": "text-end" },
                { "targets": "_all", "searchable": false }
            ],
            deferLoading: true,
            hdrBtnsSearch: true,
            order: [[ 1, "desc" ]],
            /*pageLength: 8*/
        },
        onDataReady: function(response) {
            // 
            if (response && response.header) {
                $('#total_records').text(response.header.total + ' registros');
                $('#sum_grand_total').text('$' + parseFloat(response.header.sum_grand_total || 0).toFixed(2));
                $('#sum_total_paid_efectivo').text('$' + parseFloat(response.header.sum_total_paid_efectivo || 0).toFixed(2));
                $('#sum_total_paid_tarjeta').text('$' + parseFloat(response.header.sum_total_paid_tarjeta || 0).toFixed(2));
                $('#sum_total_paid_usd_amount').text('$' + parseFloat(response.header.sum_total_paid_usd_amount || 0).toFixed(2) + ' USD');
                $('#sum_change_amount').text('$' + parseFloat(response.header.sum_change_amount || 0).toFixed(2));
                $('#sum_total_comissions').text('$' + parseFloat(response.header.sum_total_comissions || 0).toFixed(2));
            }
        },
        onSectionReady: function(opts){            
            //
            $(".sucursal_name").text($("#sucursal_name").val());
        }
    });






    //
    function filterGrid(){

        //
        var filter_pos_id = default_pos_id || $("#filter_pos_id").val();
        let filter_pos_register_id = $("#filter_pos_register_id").val();
        let created_user_id = $("#created_user_id").val();
        //
        var start_date = $('#filter_date').datetimepicker('date');
        var end_date = $('#filter_date_end').datetimepicker('date');

        //
        var qs = "?a=1";
        //
        if (filter_pos_id){
            qs += "&pid=" + filter_pos_id;
        }
        if (filter_pos_register_id){
            qs += "&rid=" + filter_pos_register_id;
        }
        if (created_user_id){
            qs += "&uid=" + created_user_id;
        }

        //
        if (start_date && end_date){

            //            
            qs += "&sd=" + start_date.format('YYYY-MM-DD');   
            qs += "&ed=" + end_date.format('YYYY-MM-DD');   
            //
            $("#grid_section").DataTable().ajax.url(app.admin_url + "/pos/sales" + qs);
            $("#grid_section").DataTable().ajax.reload();

        } else {

               $("#grid_section tbody").empty();
               
        }
        
    }



    //
    app.loadCajaUsersSearch = function(){

        //
        let pos_register_id = $("#filter_pos_register_id").val();
        //
        s2Clear("created_user_id");
        //
        $("#created_user_id")
            .val("")
            .attr("readonly", true)
            .attr("disabled", true);
        //
        const str_pos_reg_id = (pos_register_id) ? pos_register_id : "all";
        //
        $.S2Ext({
            S2ContainerId: "created_user_id",
            placeholder: "...buscar usuario",
            language: {
                noResults: function(){ return ""; },
                searching: function(){ return ""; }
            },
            //dropdownParent:   $('#sales'),
            allowClear: true,
            minimumInputLength: 2,
            minimumResultsForSearch: "-1",
            remote: {
                qs: function(){
                    return {};
                },
                url: app.admin_url + "/pos/registers/" + str_pos_reg_id + "/users/search",
                dataType: 'json',
                delay: 250,
                processResults: function (response, page) {
                    return {
                        results: response
                    };
                },
                cache: false,
                templateResult: app.templateCreatedUsersResults,
                templateSelection: app.templateCreatedUsersSelection,
            },
            onChanged: function(sel_id, data){
                //console.log('sel item:', sel_id, data);
                //
                filterGrid();
            },
            onClearing: function(){                    
                //
                filterGrid();
            },
        });

        //
        filterGrid();        
    }






    
    



    //
    app.loadPosRegistersByDate = function(str_date){
        //
        let filter_pos_id = $("#filter_pos_id").val();
        filter_pos_id = (filter_pos_id) ? filter_pos_id : "all";
        
        //
        var start_date = $('#filter_date').datetimepicker('date');
        var end_date = $('#filter_date_end').datetimepicker('date');

        //
        if (start_date && end_date){

            //
            let str_start_date = start_date.format('YYYY-MM-DD');
            let str_date_end = end_date.format('YYYY-MM-DD');
            
            //
            loadSelectAjax({
                id: "#filter_pos_register_id",
                url: app.admin_url + "/pos/list/" + filter_pos_id + "/" + str_start_date + "/" + str_date_end + "/get-registers",
                parseFields: function(item){
                    //
                    return "#" + item.id + " - " + moment(item.opened_datetime.date).format("dddd, DD [de] MMMM [de] YYYY [a las] h:mm A")  + ", por: " + item.opened_user_name;
                },
                prependEmptyOption: true,
                emptyOptionText: "--todas",
                saveValue: true,
                enable: true,
                onChange: function(){
                    app.loadCajaUsersSearch();
                },
                onReady: function(){     
                    app.loadCajaUsersSearch();
                }
            });


        }
    }



    //
    var savedDateEnd = localStorage.getItem("sales_sel_date_end");
    var defaultDateEnd = savedDateEnd ? moment(savedDateEnd, 'YYYY-MM-DD') : moment();
    //
    $('#filter_date_end').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es-mx',
        date: defaultDateEnd,
        buttons: {
            showToday: false,
            showClear: false,
            showClose: false
        },
        icons: app.tdBs5Icons,
        viewMode: 'days',
        pickTime: false
    });

    //
    $("#filter_date_end").on("change.datetimepicker", function(e) {
        if (e.date) {
            //
            var selDate = e.date.format('YYYY-MM-DD');
            //console.log(selDate);
            localStorage.setItem("sales_sel_date_end", selDate);
            //
            app.loadPosRegistersByDate();
        }
    });
    


    // debug no local storage date
    //localStorage.removeItem("sales_sel_date"); return;
    var savedDate = localStorage.getItem("sales_sel_date");
    var defaultDate = savedDate ? moment(savedDate, 'YYYY-MM-DD') : moment();

    //
    $('#filter_date').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es-mx',
        date: defaultDate,
        buttons: {
            showToday: false,
            showClear: false,
            showClose: false
        },
        icons: app.tdBs5Icons,
        viewMode: 'days',
        pickTime: false
    });

    //
    $("#filter_date").on("change.datetimepicker", function(e) {
        if (e.date) {
            //
            var selDate = e.date.format('YYYY-MM-DD');
            //console.log(selDate);
            localStorage.setItem("sales_sel_date", selDate);
            //
            $('#filter_date_end').datetimepicker('minDate', e.date);
            $('#filter_date_end').datetimepicker('date', e.date);            
        }
    });




    //
    if (!default_pos_id){
        $("#select_pdv_container").show();
        $(".view-title").html("Ventas");
    }
    
    //
    selectLoad2({
        id: "#filter_pos_id",
        url: app.admin_url + "/pos/items",
        parseFields: function(item){
            let str_active = "";
            if (!(item.active && item.active==1)){
                str_active = " (inactivo)";
            }
            return item.location_name + " - " + item.name + str_active;
        },
        prependEmptyOption: true,
        emptyOptionText: "--todos",
        saveValue: true,
        enable: true,
        default_value: default_pos_id,
        onItemIteration: function(item, $option, index, $select) {
            //console.log(`Procesando item ${index}:`, item);
            //
            if (item.id == default_pos_id) {
                defaultPosItem = item;
                //console.log('Item por defecto encontrado:', defaultPosItem);
            }
        },
        onChange: function(){            
            app.loadPosRegistersByDate();
        },
        onReady: function(){
            setTimeout(function(){
                if ( default_pos_id && defaultPosItem ) {
                    console.log("***: ", defaultPosItem);
                    $(".view-title").html("Ventas - " + defaultPosItem.name);
                }
                app.loadPosRegistersByDate();
            }, 500);
        }
    });




    // 
    $(".btnDownloadVentasReport").click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        downloadReportXls({
            report_url: app.admin_url + "/pos/sales/report-xls",
            report_title: "Reporte de Ventas",
            report_grouped_by: "Por Usuario",
        });
    });

    // 
    $(".btnDownloadComissionsReport").click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        downloadReportXls({
            report_url: app.admin_url + "/pos/sales/report-comissions-xls",
            report_title: "Reporte de Comisiones",
            report_grouped_by: "Por Empleado",
        });
    });



    

    //
    $(".btnReloadGrid").click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        filterGrid();
    });
    


    //
    function buildQueryString(){
        //
        var filter_pos_id = default_pos_id || $("#filter_pos_id").val();
        let filter_pos_register_id = $("#filter_pos_register_id").val();
        let created_user_id = $("#created_user_id").val();
        //
        var start_date = $('#filter_date').datetimepicker('date');
        var end_date = $('#filter_date_end').datetimepicker('date');
        //
        if (start_date && end_date){
            //
            var qs = "?a=1";
            //
            if (filter_pos_id){
                qs += "&pid=" + filter_pos_id;
            }
            if (filter_pos_register_id){
                qs += "&rid=" + filter_pos_register_id;
            }
            if (created_user_id){
                qs += "&uid=" + created_user_id;
            }
            //
            qs += "&sd=" + start_date.format('YYYY-MM-DD');
            qs += "&ed=" + end_date.format('YYYY-MM-DD');
            //
            return qs;
        }
        return "";
    }



    //
    function downloadReportXls(options){
        //
        loadModalV2({
            id: "modal-report",
            modal_size: "md",
            data: {},
            html_tmpl_url: "/app/pos_sales/modals/report.html?v=" + dynurl(),
            onBeforeLoad: function(){
                disable_btns();
            },
            onInit: function(){
                enable_btns();

                //
                $("#btn_submit").focus();
                //
                $("#report_title").text(options.report_title);
                $("#report_grouped_by").text(options.report_grouped_by);
                //
                $("#sale_details").click(function(){
                    //
                    var is_checked = $(this).is(":checked");
                    //
                    $("#group_sales_container").hide();
                    //
                    if (is_checked){
                        $("#group_sales_container").show();
                    }
                });
                //
                $("#group_sales_container").show();
                //
                $('#form_report').validate();
                $('#form_report').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    let qs = buildQueryString();
                    
                    //
                    const grouped = $("input[name=grouped_by_users]:checked").val();
                    if (grouped==1){
                        qs += "&g=true";
                    }
                    //
                    const sale_details = $("#sale_details").is(":checked");
                    if (sale_details){
                        qs += "&d=true";
                    }
                    //
                    if ( $('#form_report').valid() ) {
                        disable_btns();

                        //
                        setTimeout(function(){
                            $("#modal-report").find('.modal').modal("hide");
                            window.open(options.report_url + qs);
                            enable_btns();
                        }, 1000)
                        
                    }
                });
            }
        });
    }


    


})(jQuery);