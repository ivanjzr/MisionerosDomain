(function ($) {
    'use strict';



    //
    function loadCiudades(estado_id, ciudad_id){
        //
        if (estado_id){
            //
            loadSelectAjax({
                id: "#city_id",
                url: app.public_url + "/estados/" + estado_id + "/ciudades/list",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                default_value: ciudad_id,
                emptyOptionText: "--select",
                enable: true
            });
        }
    }




    /*
    *
    * SECCION PROVEEDORES
    *
    * */
    app.createSection({
        section_title: "Suppliers",
        section_title_singular: "Supplier",
        scripts_path: "/app/suppliers",
        endpoint_url: app.admin_url + "/cat/suppliers",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){
                        //
                        if (obj.thumb_img_url){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_info = "<div class='text-center'>";
                            str_info += "<div class='rowImage' style='width:200px; height:100px; background-image:url("+obj.thumb_img_url + dynurl()+")'>&nbsp;</div>";
                            str_info += "</div>";
                            str_info += " <br /><button type='button' class='btn btn-sm btn-flat btn-default btn-view-download-file' data-info='"+data_info+"'><i class='fa fa-eye'></i> Ver </button>";
                            return str_info;
                        } else {
                            return "";
                        }
                }},
                {"name": "nombre", "data" : "nombre"},
                {"data" : function(obj){ return safeNulValue(obj.phone_cc) + " " + safeNulValue(obj.phone); }},
                {"data" : function(obj){
                        return obj.address + ", " + obj.ciudad + ", " + obj.estado;
                    }},
                {"data" : function(obj){ return safeNulValue(obj.notes); }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
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
            columnDefs: [
                {
                    "targets": [0, 2, 5, 6, 8],
                    "orderable": false
                },
                {
                    "targets": "_all",
                    "searchable": false
                }
            ],
            hdrBtnsSearch: true,
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
            $('#nombre').val(section_data.nombre);
            $('#address').val(section_data.address);
            $('#notes').val(section_data.notes);
            $('#phone_cc').val(section_data.phone_cc);
            $('#phone').val(section_data.phone);
            //
            if (section_data.active){
                $('#active').attr("checked", true);
            } else {
                $('#active').attr("checked", false);
            }


            //
            if ( section_data.thumb_img_url ){
                //
                $('#img_section_url').attr("src", section_data.thumb_img_url + dynurl());
                $('#img_section_url').attr("data-id", section_data.id);
                $('#img_section_url').css({
                    "width":200,
                    "height":150
                });
                $('#img_section_container').show();
            } else {
                $('#img_section_url').attr("src", null);
                $('#img_section_container').hide();
            }
        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){


            //
            var estado_id = null;
            var city_id = null;
            if (section_data && section_data.estado_id  && section_data.city_id){
                estado_id = section_data.estado_id;
                city_id = section_data.city_id;
            }

            //
            loadSelectAjax({
                id: "#estado_id",
                url: app.public_url + "/estados/list",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                default_value: estado_id,
                enable: true,
                onChange: function(){
                    //
                    var estado_id = $("#estado_id").val();
                    loadCiudades(estado_id);
                }
            });
            //
            if (estado_id && city_id){
                loadCiudades(estado_id, city_id);
            }



            // def focus
            $("#nombre").focus();

        },
        onGridReady: function(opts){

            //
            $(".btn-view-download-file").click(function(e){
                e.preventDefault();
                //
                var data_info = $(this).data("info");
                //
                loadModalV2({
                    id: "modal-view-download-image",
                    modal_size: "lg",
                    html_tmpl_url: "/app/common/preview-img.html?v=3",
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();
                        //
                        $('#modal-title').text("Imagen " + data_info.nombre);
                        $("#preview_img_id").attr("src", data_info.orig_img_url);
                    }
                });

            });

        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);