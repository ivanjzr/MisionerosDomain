(function ($) {
    'use strict';








    //
    app.createSection({
        section_title: "Ciudades",
        section_title_singular: "ciudad",
        scripts_path: "/app/ciudades",
        modalAddHtmlName: "add-record.html",
        modalEditHtmlName: "edit-record.html",
        editFieldName: "ciudad_nombre",
        endpoint_url: app.admin_url + "/ciudades",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){
                    return obj.ciudad_nombre + " - <strong>" + obj.estado_abreviacion + "</strong>";
                }},
                {"name" : "loc_identifier", "data" : "loc_identifier"},
                {"name" : "loc_notes", "data" : "loc_notes"},
                {"name" : "ciudad_address", "data" : "ciudad_address"},
                {"data" : function(obj){
                        return obj.ciudad_lat + " / " + obj.ciudad_lng;
                    }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-edit' data-info='"+data_info+"'><i class='fas fa-edit'></i></button>";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0,2,3,4],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            deferLoading: true,
            hdrBtnsSearch: true,
            order: [[ 1, "desc" ]]
        },
        //
        onAddEditReady: function(){

            //
            $(document).on('selectLocationCiudad', function(ev, place_location) {
                ev.stopPropagation();
                console.log("---ciudad: ", place_location);

                //
                $("#ciudad_lat").val(place_location.lat);
                $("#ciudad_lng").val(place_location.lng);
                $("#ciudad_address").val(place_location.address);

            });


            //
            $(".btnSelectLocation").click(function (e) {
                e.preventDefault();

                //
                loadModalV2({
                    id: "modal-address",
                    modal_size: "md",
                    data: {
                        bindFuncName: "selectLocationCiudad"
                    },
                    html_tmpl_url: "/app/common/modal-address/index.html?v=" + dynurl(),
                    js_handler_url: "/app/common/modal-address/index.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();
                    }
                });
            });


        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){



            //
            loadSelectAjax({
                id: "#estado_id",
                url: app.admin_url + "/estados/list",
                parseFields: function(item){
                    return item.nombre;
                },
                saveValue: true,
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true
            });



            // def focus
            $("#nombre").focus();
        },
        onEditReady: function(data, opts){
            //console.log(data, opts);

            //
            $("#ciudad_address").val(data.ciudad_address);
            $("#loc_identifier").val(data.loc_identifier);
            $("#loc_notes").val(data.loc_notes);
            $("#ciudad_lat").val(data.ciudad_lat);
            $("#ciudad_lng").val(data.ciudad_lng);

        },
        onGridReady: function(opts){






        },
        onSectionReady: function(opts){



            //
            loadSelectAjax({
                id: "#filter_estado_id",
                url: app.admin_url + "/estados/list",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--Todos",
                saveValue: true,
                enable: true,
                onChange: function(){
                    filterGrid()
                },
                onReady: function(){
                    filterGrid();
                }
            });




            //
            $(".sucursal_name").text($("#sucursal_name").val());
        }
    });






    //
    function filterGrid(){
        //
        var filter_estado_id = $("#filter_estado_id").val();
        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/ciudades?eid=" + filter_estado_id);
        $("#grid_section").DataTable().ajax.reload();
    }









})(jQuery);