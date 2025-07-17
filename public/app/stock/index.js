(function ($) {
    'use strict';



    /*
    *
    * SECCION STOCK
    *
    * */
    app.createSection({
        section_title: "Stock",
        section_title_singular: "Stock",
        scripts_path: "/app/stock",
        endpoint_url: app.admin_url + "/stock",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "nombre", "data" : "nombre"},
                {"name": "cant_stock", "data" : "cant_stock"},
                {"data" : function(obj){
                        //
                        var str_proveedor = (obj.proveedor) ? obj.proveedor : "--ajuste";
                        return str_proveedor + " / " +  safeNulValue(obj.entrada_cantidad, "-");
                }},
                {"data" : function(obj){
                        //
                        return safeNulValue(obj.tipo_salida, "-") + " / " + safeNulValue(obj.salida_cantidad, "-");
                    }},
                {"name": "aplica_stock", "data" : function(obj){ return fmtActive(obj.aplica_stock, true); }},
                {"name": "cant_min_notify", "data" : "cant_min_notify"},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-view' data-info='"+data_info+"'><i class='fas fa-folder'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            hdrBtnsSearch: true,
            deferLoading: true,
            columnDefs: [
                { "targets": [0, 4, 8],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
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
            $('#cant_min_notify').val(section_data.cant_min_notify);
            //
            if (section_data.aplica_stock){
                $('#aplica_stock').attr("checked", true);
            } else {
                $('#aplica_stock').attr("checked", false);
            }

        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){

        },
        onSectionReady: function(opts){


            //
            function filterGrid(){
                //
                var filter_tipo_ps_id = $("#filter_tipo_ps_id").val();
                //
                $("#grid_section").DataTable().ajax.url(app.admin_url + "/stock?filter_tipo_ps_id=" + filter_tipo_ps_id);
                $("#grid_section").DataTable().ajax.reload();
            }



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
                    filterGrid()
                },
                onReady: function(){
                    filterGrid()
                }
            });


        }
    });




})(jQuery);