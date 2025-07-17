define(function(){
    function moduleReady(section_data){
        //console.log(section_data);

        //
        app.createSection({
            gridId: "#grid_caja_ventas",
            section_title: "Ventas",
            data: section_data,
            section_title_singular: "Venta",
            scripts_path: "/app/pos/registers",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/sales",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id", "title": "ID"},
                    {"name" : "customer_id", "data" : function(obj){ 
                        if (obj.customer_id){
                            return obj.customer_name + " - " + obj.email;
                        }
                        return "--";
                        
                    }, "title": "Cliente"},
                    {"name" : "grand_total", "data" : function(obj){ 
                        var html = '$' + parseFloat(obj.sub_total || 0).toFixed(2);
                        
                        if (obj.promo_id && obj.promo_info) {
                            html += '<br><small class="text-muted">' + obj.promo_info + '</small>';
                            html += '<br><small class="text-success">Total: $' + parseFloat(obj.grand_total || 0).toFixed(2) + '</small>';
                        }
                        
                        return html;                       
                    }, "title": "Sub Total"},
                    {"name" : "grand_total", "data" : function(obj){ 
                        return '$' + parseFloat(obj.grand_total || 0).toFixed(2); 
                    }, "title": "Total"},

                    {"name" : "total_paid_efectivo", "data" : function(obj){ 
                        return '$' + parseFloat(obj.total_paid_efectivo || 0).toFixed(2); 
                    }, "title": "Efectivo"},
                    {"name" : "total_paid_tarjeta", "data" : function(obj){ 
                        return '$' + parseFloat(obj.total_paid_tarjeta || 0).toFixed(2); 
                    }, "title": "Tarjetas"},
                    {"name" : "total_paid_usd_amount", "data" : function(obj){ 
                        return '$' + parseFloat(obj.total_paid_usd_amount || 0).toFixed(2) + ' USD'; 
                    }, "title": "Dólares"},
                    {"name" : "change_amount", "data" : function(obj){ 
                        var change = parseFloat(obj.change_amount || 0);
                        var changeClass = change > 0 ? 'text-warning' : '';
                        return '<span class="' + changeClass + '">$' + change.toFixed(2) + '</span>'; 
                    }, "title": "Cambio"},                    
                    {"name" : "created_user_name", "data" : function(obj){ 
                        return obj.created_user_name;
                    }, "title": "Usuario"},                    
                    {"name": "datetime_created", "data" : function(obj){ 
                        return moment(obj.datetime_created.date).format('DD/MM/YY HH:mm'); 
                    }, "title": "Fecha"},
                    {"name" : "notes", "data" : "notes", "title": "Notas"},
                ],
                deferLoading: true,
                columnDefs: [
                    { "targets": [0], "orderable": false },
                    { "targets": [3,4,5,6,7], "className": "text-end" }, // Alinear montos a la derecha
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]]
            },
            onAddEditReady: function(){
                // def focus
                $('#product_precio').focus();
            },
            onGridReady: function(opts){
                //
            },
            onSectionReady: function(opts){

                //
                $('#btnReloadSales').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_caja_ventas").DataTable().ajax.reload();
                });

            }
        });



        //
        app.templateResults = function(item){
            if (item.loading) {
                return item.text;
            }
            var str_email = item.created_user_email ? item.created_user_email + " - " : "";
            return item.id + " - " + str_email + item.created_user_name;
        }
        //
        app.templateSelection = function(item){
            if (item.id){
                var str_email = item.created_user_email ? item.created_user_email + " - " : "";
                return item.id + " - " + str_email + item.created_user_name;
            }
            return item.text;
        }
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
                url: app.admin_url + "/pos/registers/" + section_data.id + "/users/search",
                dataType: 'json',
                delay: 250,
                processResults: function (response, page) {
                    return {
                        results: response
                    };
                },
                cache: false,
                templateResult: app.templateResults,
                templateSelection: app.templateSelection,
            },
            onChanged: function(sel_id, data){
                //console.log('sel item:', sel_id, data);
                filterGrid();
            },
            onClearing: function(){
                filterGrid();
            },
        });


        // 
        function filterGrid(){
            //
            var filter_uid = $("#created_user_id").val();
            console.log(filter_uid);
            const qs_uid = (filter_uid) ? "?uid=" +filter_uid : "";
            //
            $("#grid_caja_ventas").DataTable().ajax.url(section_data.opts.endpoint_url + "/" + section_data.id + "/sales" + qs_uid);
            $("#grid_caja_ventas").DataTable().ajax.reload();
        }



        //
        filterGrid();


    }
    return {init: moduleReady}
});