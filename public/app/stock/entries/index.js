(function ($) {
    'use strict';



    //
    app.loadGrid = function(){
        //
        var filter_producto_id = $("#filter_producto_id").val();
        $(".btnReload, .btnAddRecord").hide();
        //
        if (filter_producto_id){
            //
            $.ajax({
                type:'GET',
                url: app.admin_url + "/stock/" + filter_producto_id + "/entries/" + filter_producto_id,
                success:function(section_data){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    if ( section_data && section_data.id ){
                        //
                        $("#grid_section").DataTable().ajax.url(app.admin_url + "/stock/" + filter_producto_id + "/entries");
                        $("#grid_section").DataTable().ajax.reload();
                        //
                        $(".btnReload, .btnAddRecord").show();
                        //
                        $("#cant_stock").html("Cantidad: <strong>" + section_data.cant_stock + "</strong>");
                    }
                    //
                    else if (section_data.error){
                        app.Toast.fire({ icon: 'error', title: section_data.error });
                    }
                    //
                    else {
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                },
                error: function(){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });
        }
    }
    //
    function LoadProductos(filter_tipo_ps_id){
        //
        if (filter_tipo_ps_id){
            //
            loadSelectAjax({
                id: "#filter_producto_id",
                url: app.admin_url + "/products/" + filter_tipo_ps_id,
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                saveValue: true,
                enable: true,
                onChange: function(){
                    //
                    app.loadGrid();
                },
                onReady: function(){
                    //
                    app.loadGrid();
                }
            });
        }
    }



    /*
    *
    * SECCION STOCK ENTRADAS
    *
    * */
    app.createSection({
        section_title: "Entradas",
        section_title_singular: "Entrada",
        scripts_path: "/app/stock/entries",
        endpoint_url: app.admin_url + "/stock",
        preventBindForm: true,
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"visible": false, "name" : "id", "data" : "id"},
                {"visible": false,"name": "product_id", "data" : "product_id"},
                {"data" : function(obj){ return safeNulValue(obj.cant_ajuste) }},
                {"data" : function(obj){
                        if (!obj.tipo_salida_id){
                            return safeNulValue(obj.proveedor, "ajuste/otro");
                        }
                        return "";
                    }},
                {"name": "proveedor_monto", "data" : function(obj){ return fmtAmount(obj.proveedor_monto); }},
                {"name": "notes", "data" : "notes"},
                {"data" : function(obj){return fmtDateSpanish(obj.datetime_created.date, true);}},
            ],
            deferLoading: true,
            columnDefs: [
                { "targets": [0, 3, 4, 7],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(opts){



            //
            loadSelectAjax({
                id: "#proveedor_id",
                url: app.admin_url + "/suppliers/list",
                parseFields: function(item){
                    return item.nombre + " - " + item.address + ", " + item.ciudad;
                },
                prependEmptyOption: true,
                emptyOptionText: "--ajuste",
                enable: true
            });


            //
            $('#new_cantidad').focus();


            //
            var filter_producto_id = $("#filter_producto_id").val();
            opts.bindAddUpdate(filter_producto_id + "/entries");

        },
        onAddEditSuccess: function(opts, response){
            //
            app.loadGrid();
        },
        onSectionReady: function(opts){

            //
            loadSelectAjax({
                id: "#filter_tipo_ps_id",
                url: app.admin_url + "/sys/tipos-productos-servicios/list",
                parseFields: function(item){
                    return item.tipo;
                },
                prependEmptyOption: true,
                emptyOptionText: "--todos",
                saveValue: true,
                enable: true,
                onChange: function(){
                    //
                    var filter_tipo_ps_id = $("#filter_tipo_ps_id").val();
                    LoadProductos(filter_tipo_ps_id);
                },
                onReady: function(){
                    //
                    var filter_tipo_ps_id = $("#filter_tipo_ps_id").val();
                    LoadProductos(filter_tipo_ps_id);
                }
            });


        }
    });




})(jQuery);