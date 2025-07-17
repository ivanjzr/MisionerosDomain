(function ($) {
    'use strict';



    /*
    *
    * SECCION CATALOGO ESTADOS
    *
    * */
    app.createSection({
        section_title: "Estados",
        section_title_singular: "Estado",
        scripts_path: "/sup/locations",
        endpoint_url: app.supadmin_url + "/locations",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {visible: false, "name": "pais_id", "data" : "pais_id"},
                {"data" : function(obj){
                    return obj.nombre + " (" + obj.abreviado + ")";
                }},
                {"data" : function(obj){
                        //
                        var str_ciudades = "<div>";
                        //
                        if (obj.ciudades && obj.ciudades.length){
                            $.each(obj.ciudades, function(idx, item){
                                //console.log(idx, item.value);
                                str_ciudades += "<small>" + item.nombre + " (" + item.abreviado + ")</small>, ";
                            });
                        }
                        //
                        str_ciudades +="</div>";
                        //
                        return str_ciudades;
                    }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-ciudades' data-info='"+data_info+"'><i class='fas fa-list-ul'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-info='"+data_info+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 3, 4],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            hdrSearch: true,
            deferLoading: true, /* PREVENT FIRST TRIGGER */
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){

            //
            var filter_pais_id = $("#filter_pais_id").val();
            //
            loadSelectAjax({
                id: "#pais_id",
                url: app.public_url + "/paises/list",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                default_value: filter_pais_id,
                emptyOptionText: "--select",
                enable: true
            });

            $(".display_pais_info").show();
        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){

            //
            $('#nombre').val(section_data.nombre);
            $('#abreviado').val(section_data.abreviado);

            //
            loadSelectAjax({
                id: "#pais_id",
                url: app.public_url + "/paises/list",
                parseFields: function(item){
                    return item.tipo;
                },
                prependEmptyOption: true,
                default_value: section_data.pais_id,
                emptyOptionText: "--select",
                enable: true
            });

            $(".display_pais_info").hide();
        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){
            // def focus
            $("#nombre").focus();
        },
        onGridReady: function(opts){


            //
            $('.btn-ciudades').click(function(e) {
                e.preventDefault();

                //
                var data_info = $(this).data("info");
                //console.log(data_info);

                disable_btns();

                //
                loadModalV2({
                    id: "modal-features",
                    modal_size: "lg",
                    data: data_info,
                    html_tmpl_url: opts.scripts_path + "/modals/ciudades.html?v="+dynurl(),
                    js_handler_url: opts.scripts_path + "/modals/ciudades.js?v="+dynurl(),
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
            function filterGrid(){
                //
                var filter_pais_id = $("#filter_pais_id").val();
                //
                $("#grid_section").DataTable().ajax.url(app.supadmin_url + "/locations?filter_pais_id=" + filter_pais_id);
                $("#grid_section").DataTable().ajax.reload();
            }


            //
            loadSelectAjax({
                id: "#filter_pais_id",
                url: app.public_url + "/paises/list",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
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