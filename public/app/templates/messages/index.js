(function ($) {
    'use strict';



    /*
    *
    * SECCION MAQUETAS DE MENSAJES
    *
    * */
    app.createSection({
        section_title: "Mensajes",
        section_title_singular: "Mensaje",
        scripts_path: "/app/templates/messages",
        endpoint_url: app.admin_url + "/templates-messages",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {visible: false, "name": "company_name", "data" : "company_name"},
                {"name": "sms_msg", "data" : "sms_msg"},
                {"name": "email_subject", "data" : "email_subject"},
                {"data" : function(obj){ return fmtActive(obj.sms_active); }},
                {"data" : function(obj){ return fmtActive(obj.email_active); }},
                {"data" : function(obj){ return fmtActive(obj.in_use); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        str_btns += " <button type='button' style='margin-top: 10px;' class='btn btn-sm btn-flat btn-outline-dark btn-mensaje-copias-emails btn-block' data-info='"+data_info+"'><i class='fas fa-envelope'></i> Copias Correos </button>";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-outline-dark btn-mensaje-copias-phones btn-block' data-info='"+data_info+"'><i class='fas fa-phone'></i> Copias Telefonos </button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 3, 4, 5],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            hdrSearch: true,
            deferLoading: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(){


            //
            $('#sms_active').attr("checked", true);
            $('#email_active').attr("checked", true);
            $('#in_use').attr("checked", true);



        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){


            //
            $('#sms_msg').val(section_data.sms_msg);
            //
            if (section_data.sms_active){
                $('#sms_active').attr("checked", true);
            } else {
                $('#sms_active').attr("checked", false);
            }


            //
            $('#account_id').val(section_data.account_id);


            //
            $('#email_subject').val(section_data.email_subject);
            $('#email_msg').val(section_data.email_msg);
            //
            if (section_data.email_active){
                $('#email_active').attr("checked", true);
            } else {
                $('#email_active').attr("checked", false);
            }


            //
            if (section_data.in_use){
                $('#in_use').attr("checked", true);
            } else {
                $('#in_use').attr("checked", false);
            }

        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){

            //
            $('#email_msg').summernote({
                placeholder: '',
                height: 150
            });

            // def focus
            $("#sms_msg").focus();


            //
            var filter_maqueta_id = $("#filter_maqueta_id").val();
            //
            loadSelectAjax({
                id: "#maqueta_id",
                url: app.admin_url + "/sys/maquetas/tipos-correos/",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                default_value: (section_data && section_data.maqueta_id) ? section_data.maqueta_id : filter_maqueta_id,
                emptyOptionText: "--select",
                enable: true
            });


        },
        onGridReady: function(opts){


            //
            var filter_maqueta_id = $("#filter_maqueta_id").val();
            if (filter_maqueta_id){
                //
                $("#maqueta_info")
                    .html("")
                    .removeClass("text-danger");
                //
                $.ajax({
                    type:'GET',
                    url: app.admin_url + "/templates-messages/" + filter_maqueta_id + "/maqueta-info",
                    success:function(section_data){
                        //
                        if (section_data.error){
                            app.Toast.fire({ icon: 'error', title: section_data.error });
                        }
                        //
                        else {
                            //
                            if ( !section_data.maqueta_has_mensajes ){
                                //
                                $("#maqueta_info")
                                    .html("<i class='fas fa-info-circle'></i> La maqueta no tiene un mensaje habilitado por lo que no sera posible su envio")
                                    .addClass("text-danger");
                            }
                        }
                    },
                    error: function(){
                        //
                        enable_btns();
                        preload(false);
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });
            }




            //
            $('.btn-mensaje-copias-emails').click(function(e) {
                e.preventDefault();

                //
                var data_info = $(this).data("info");
                //console.log(data_info);

                disable_btns();

                //
                loadModalV2({
                    id: "modal-copia-emails",
                    modal_size: "lg",
                    data: data_info,
                    html_tmpl_url: opts.scripts_path + "/modals/copias-emails.html?v="+dynurl(),
                    js_handler_url: opts.scripts_path + "/modals/copias-emails.js?v="+dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });

            });



            //
            $('.btn-mensaje-copias-phones').click(function(e) {
                e.preventDefault();

                //
                var data_info = $(this).data("info");
                //console.log(data_info);

                disable_btns();

                //
                loadModalV2({
                    id: "modal-copia-phones",
                    modal_size: "lg",
                    data: data_info,
                    html_tmpl_url: opts.scripts_path + "/modals/copias-phones.html?v="+dynurl(),
                    js_handler_url: opts.scripts_path + "/modals/copias-phones.js?v="+dynurl(),
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
                var filter_maqueta_id = $("#filter_maqueta_id").val();
                //
                $("#grid_section").DataTable().ajax.url(app.admin_url + "/templates-messages?filter_maqueta_id=" + filter_maqueta_id);
                $("#grid_section").DataTable().ajax.reload();
            }


            //
            loadSelectAjax({
                id: "#filter_maqueta_id",
                url: app.admin_url + "/sys/maquetas/tipos-correos",
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