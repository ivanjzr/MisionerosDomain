(function ($) {
    'use strict';



    function determineDisplayPermisos(is_admin){
        if (is_admin){
            $(".permisos_info").hide();
        } else {
            $(".permisos_info").show();
        }
    }



    //
    function onEditReady(section_data, opts){
        
        //
        $(".user-badges").html("");
        $('#name').val(section_data.name);
        $('.user-nombre').text(section_data.name);
        $('.user-titulo').text(section_data.titulo);
        $('.user-notes').text(section_data.notes);
        $('#phone_cc').val(section_data.phone_cc);
        $('#notes').val(section_data.notes);

        //
        $('#is_pos_user').attr("checked", (section_data.is_pos_user && section_data.is_pos_user==1) ? true : false);
        $('#pos_pin').val(section_data.pos_pin);
        $('#login_to_pos').attr("checked", (section_data.login_to_pos && section_data.login_to_pos==1) ? true : false);

        //
        setTimeout(function(){
            //
            $('#password')
                .removeAttr("readonly")
            //
            $('#password_confirm')
                .removeAttr("readonly")
            //
            $('#email')
                .val(section_data.email)
                .removeAttr("readonly")
            //
            $('#phone_number')
                .val(section_data.phone_number)
                .removeAttr("readonly")
        }, 500);


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
                        disable_btns();
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
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }
                    },
                    error: function(response_error){
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
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



        //
        loadSelectAjax({
            id: "#phone_country_id",
            url: app.public_url + "/paises/list",
            parseFields: function(item){
                return "+" + item.phone_cc + " (" + item.abreviado + ")";
            },
            prependEmptyOption: true,
            emptyOptionText: "--select",
            default_value: ((section_data && section_data.phone_country_id) ? section_data.phone_country_id : app.ID_PAIS_EU),
            enable: true
        });
        
        //
        loadSelectAjax({
            id: "#sys_title_id",
            url: app.admin_url + "/sys/titulos/list",
            parseFields: function(item){
                return item.titulo;
            },
            prependEmptyOption: true,
            emptyOptionText: "--select",
            default_value: section_data.sys_title_id,
            enable: true
        });


        //
        if (section_data.active){
            $('#active').attr("checked", true);
            $(".user-badges").append("<span class='badge badge-success'>Activo</span>&nbsp;")
        } else {
            $('#active').attr("checked", false);
            $(".user-badges").append("<span class='badge badge-danger'>Inactivo</span>&nbsp;")
        }
        //
        $('.section-title').text(section_data.nombre);

        //
        if ( section_data.is_admin ){
            determineDisplayPermisos(true);
            $('#is_admin').attr("checked", true);
            $(".user-badges").append("<span class='badge badge-success'>Is Admin</span>")
        }
        //
        else {
            determineDisplayPermisos(false);
            $('#is_admin').attr("checked", false);
        }

        //
        $("#is_admin").click(function(){
            //
            var is_admin = $(this).is(":checked");
            determineDisplayPermisos(is_admin);
        });


        // def focus
        $("#name").focus();
    }




    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;
        section_data.tipo_producto_servicio_id = 123;

        //
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/users/modules/users-sucursales.js",
            onInit: function(){
                enable_btns();
            }
        });

    }




    /*
    *
    * SECCION EDIT USERS
    *
    * */
    app.createSection({
        section_title: "Users",
        section_title_singular: "User",
        scripts_path: "/app/users",
        endpoint_url: app.admin_url + "/users",
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





    //
    $('#form_pos').validate();
    //
    $('#form_pos').submit(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        //
        if ( $('#form_pos').valid() ) {
            //
            $('#form_pos').ajaxSubmit({
                url: app.admin_url + "/users/" + record_id + "/update-pos",
                beforeSubmit: function(arr){
                    disable_btns();
                },
                success: function(response){
                    //
                    enable_btns();
                    //
                    if (response && response.id){
                        //
                        app.Toast.fire({ icon: 'success', title: "Registro actualizado correctamente" });
                        section_data.opts.loadData();
                    }
                    //
                    else if (response.error){
                        app.Toast.fire({ icon: 'error', title: response.error});
                    }
                    //
                    else {
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                },
                error: function(response){
                    enable_btns();
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });

        }
    });





})(jQuery);