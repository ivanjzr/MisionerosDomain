(function ($) {
    'use strict';






    //
    function onEditReady(section_data, opts){
        
        //
        $(".contact-badges").html("");
        $('#name').val(section_data.name);
        $('.contact-nombre').text(section_data.name);
        $('.contact-titulo').text(section_data.titulo);
        $('.contact-notes').text(section_data.notes);
        $('#phone_cc').val(section_data.phone_cc);
        $('#notes').val(section_data.notes);

        //
        $('#is_archived').attr("checked", (section_data.is_archived && section_data.is_archived==1) ? true : false);
        $('#commission_rate').val(section_data.commission_rate);

        //
        setTimeout(function(){
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
            default_value: section_data.phone_country_id,
            enable: true
        });

        //
        $('.section-title').text(section_data.name);

        

        // def focus
        $("#name").focus();
    }




    //
    function loadModules(section_data, opts){
        //
        section_data.opts = opts;
    }




    //
    app.createSection({
        section_title: "Empleados",
        section_title_singular: "Empleado",
        scripts_path: "/app/contacts",
        endpoint_url: app.admin_url + "/contacts",
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


        }
    });




    


})(jQuery);