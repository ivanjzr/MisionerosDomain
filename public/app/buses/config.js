(function ($) {
    'use strict';



    /*
    *
    * SECCION PRODUCTS CONFIG
    *
    * */
    app.createSection({
        section_title: "Products Config",
        section_title_singular: "Products Config",
        scripts_path: "/app/productos",
        endpoint_url: app.admin_url + "/products/config",
        preventPassId: true,
        onEditReady: function(section_data, opts){
            //
            $("#meal_plans_discount").val(section_data.meal_plans_discount);
            $("#subscriptions_discount").val(section_data.subscriptions_discount);
        },
        onSectionReady: function(opts){

            //
            $("#btnReloadConfig").click(function(){
                //
                opts.loadData();
            });
            //
            opts.loadData();

        }
    });













    //
    app.createSection({
        gridId: "#grid_taxes",
        section_title: "Taxes",
        section_title_singular: "Tax",
        scripts_path: "/app/productos",
        endpoint_url: app.admin_url + "/products/config/taxes",
        gridOptions:{
            columns: [
                {"visible": false, "name" : "id", "data" : "id"},
                {"name" : "nombre", "data" : "nombre"},
                {"visible": true, width:"15%", "data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        return "<input type='text' value='"+safeNulValue(obj.tax_percent, "0")+"' data-info='"+data_info+"' class='form-control update-value' />"
                    }}
            ],
            columnDefs: [
                { "targets": "_all","orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]],
        },
        onAddEditReady: function(){
            //
        },
        onGridReady: function(opts){

            //
            $("#grid_taxes .update-value").focus(function(){
                $(this).select();
            });

            //
            $("#grid_taxes .update-value").keyup(function(e) {
                if(e.keyCode == 13){

                    //
                    var data_info = $(this).data("info");

                    //
                    $.ajax({
                        type:'POST',
                        url: app.admin_url + "/products/config/taxes/" + data_info.id,
                        data: $.param({
                            tax_percent: $(this).val()
                        }),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        success:function(response){
                            //console.log(response.data);
                            if (response.id){
                                //
                                app.Toast.fire({ icon: 'success', title: "Tax Updated" });
                                $("#grid_taxes").DataTable().ajax.reload();
                            }
                            //
                            else if (response.error){
                                //
                                app.Toast.fire({ icon: 'error', title: response.error});
                            }
                            //
                            else {
                                app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                            }
                        },
                        error: function(){
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }
                    });

                }
            });

        },
        onSectionReady: function(opts){

            //
            $('#btnReloadTaxes').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                $("#grid_taxes").DataTable().ajax.reload();
            });

        }
    });







})(jQuery);