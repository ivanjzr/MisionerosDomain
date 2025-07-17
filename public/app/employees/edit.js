(function ($) {
    'use strict';






    //
    function onEditReady(section_data, opts){
        
        //
        $(".employee-badges").html("");
        $('#name').val(section_data.name);
        $('.employee-nombre').text(section_data.name);
        $('.employee-titulo').text(section_data.titulo);
        $('.employee-notes').text(section_data.notes);
        $('#phone_cc').val(section_data.phone_cc);
        $('#notes').val(section_data.notes);

        //
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
            id: "#job_title_id",
            url: app.admin_url + "/job-titles/list",
            parseFields: function(item){
                return item.name;
            },
            prependEmptyOption: true,
            default_value: section_data.job_title_id,
            emptyOptionText: "--select",
            enable: true
        });

        //
        loadSelectAjax({
            id: "#departamento_id",
            url: app.admin_url + "/departments/list",
            parseFields: function(item){
                return item.departamento;
            },
            prependEmptyOption: true,
            default_value: section_data.departamento_id,
            emptyOptionText: "--select",
            enable: true
        });



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

        

        if (section_data.contact_id){
            $('#contact_id').text(section_data.contact_id);
            $('#contact_name').text(section_data.contact_name);
            $('#contact_info_container').show();
        }

        //
        $('#active').attr("checked", (section_data.active && section_data.active==1) ? true : false);
        
        //
        $('.section-title').text(section_data.nombre);

        

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
        scripts_path: "/app/employees",
        endpoint_url: app.admin_url + "/employees",
        record_id: record_id,
        onEditReady: onEditReady,
        reloadDataOnSave: true,
        loadModules: loadModules,
        onSectionReady: function(opts){

            

             //
            $('#btnCreateUser').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
            });


            //
            $('#btnReloadDetails').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                opts.loadData();
            });


        }
    });




    //
    $('#form_commissions').validate();
    //
    $('#form_commissions').submit(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        //
        if ( $('#form_commissions').valid() ) {
            // 
            $('#form_commissions').ajaxSubmit({
                url: app.admin_url + "/employees/" + record_id + "/commissions",
                beforeSubmit: function(arr){
                    disable_btns();
                },
                success: function(response){
                    //
                    enable_btns();
                    //
                    if (response && response.id){
                        //
                        app.Toast.fire({ icon: 'success', title: "Record added succesfully" });
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