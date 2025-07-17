(function ($) {
    'use strict';





    //
    function onEditReady(section_data, opts){

        //
        $('#nombre').val(section_data.nombre);
        $('.empleado-nombre').text(section_data.nombre);
        $('.empleado-notes').text(section_data.notes);
        $('#email').val(section_data.email);
        $('#phone_cc').val(section_data.phone_cc);
        $('#phone_number').val(section_data.phone_number);
        $('#notes').val(section_data.notes);


        //
        if (section_data.active){
            $('#active').attr("checked", true);
            $(".empleado-badges").append("<span class='badge badge-success'>Activo</span>&nbsp;")
        } else {
            $('#active').attr("checked", false);
            $(".empleado-badges").append("<span class='badge badge-danger'>Inactivo</span>&nbsp;")
        }
        //
        $('.section-title').text(section_data.nombre);






        //
        $("#img_section").change(function (event){

            //
            const name = event.target.files[0].name;
            const lastDot = name.lastIndexOf('.');
            //
            const fileName = name.substring(0, lastDot);
            const fileExt = name.substring(lastDot + 1);
            //console.log(fileName, fileExt);
            //
            var validImageTypes = ["gif", "jpeg", "jpg", "png"];
            if ($.inArray(fileExt, validImageTypes) < 0) {
                //
                app.Toast.fire({ icon: 'warning', title: "Proporcione un archivo de imagen valido" });
                $("#img_section").val("");
            }
            //
            else {

                //
                $("#form_foto").ajaxSubmit({
                    url: opts.endpoint_url + "/" + section_data.id + "/upload-img",
                    beforeSubmit: function(arr){
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay", true);
                    },
                    success: function(send_response){
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        if (send_response && send_response.id){
                            //
                            app.Toast.fire({ icon: 'success', title: "Imagen subida exitosamente" });
                            //
                            setTimeout(function(){
                                location.reload();
                            }, 1000);
                        }
                        //
                        else if (send_response.error){
                            app.Toast.fire({ icon: 'error', title: send_response.error });
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                        }
                    },
                    error: function(response_error){
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                    }
                });

            }
        });


        //
        if ( section_data.orig_img_url ){
            //
            $('#img_section_url').attr("src", section_data.orig_img_url + dynurl());
            $('#img_section_url').attr("data-id", section_data.id);
            $('#img_section_url').css({
                "width":128,
                "height":128
            });
            $('#img_section_container').show();
        } else {
            $('#img_section_url').attr("src", null);
            $('#img_section_container').hide();
        }




        // def focus
        $("#nombre").focus();
    }




    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;
        section_data.tipo_producto_servicio_id = TPS_ID_ACCESORIOS;

        // //
        // loadModule({
        //     data: section_data,
        //     onBeforeLoad: function(){
        //         disable_btns();
        //     },
        //     js_url: "/app/empleados/modules/empleados-sucursales.js",
        //     onInit: function(){
        //         enable_btns();
        //     }
        // });

    }




    /*
    *
    * SECCION EDIT ADMINISTRATORS
    *
    * */
    app.createSection({
        section_title: "Administradores",
        section_title_singular: "Administrador",
        scripts_path: "/sup/administrators",
        endpoint_url: app.supadmin_url + "/administrators",
        record_id: record_id,
        onEditReady: onEditReady,
        reloadDataOnSave: true,
        loadModules: loadModules,
        onSectionReady: function(opts){

            //
            $('#btnReloadDetails').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                opts.loadData();
            });


            //
            $('#btnOpenPrices').click(function(e) {
                e.preventDefault();
                //
                var tab_name = "precios";
                //
                $('.nav-tabs a[href="#' + tab_name + '"]').tab('show');
            });

        }
    });




})(jQuery);