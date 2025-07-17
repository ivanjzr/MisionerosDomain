app.createSection = function(opts){



    //
    var grid_id = (opts.gridId) ? opts.gridId : "#grid_section";
    var modal_add_id = (opts.modalAddId) ? opts.modalAddId : "modal-section";
    var form_name = (opts.formName) ? opts.formName : "#form_section";
    var btn_add_record = (opts.btnAddRecord) ? opts.btnAddRecord : ".btnAddRecord";
    var btn_reload = (opts.btnReloadGrid) ? opts.btnReloadGrid : ".btnReload";
    var modalSize = (opts.modalSize) ? opts.modalSize : "lg";
    //console.log(btn_add_record);
    //
    var modalAddHtmlName = (opts.modalAddHtmlName) ? opts.modalAddHtmlName : "add-edit-record.html";
    var modalEditHtmlName = (opts.modalEditHtmlName) ? opts.modalEditHtmlName : "add-edit-record.html";



    function loadModalAddEdit(){

        //
        loadModalV2({
            id: modal_add_id,
            modal_size: modalSize,
            /*data: {id_caja: 1234}, // pasa objeto de datos cuando llama a un modulo js*/
            /*onHide: function(){},*/
            html_tmpl_url: opts.scripts_path + "/modals/" + modalAddHtmlName + "?v=" + dynurl(),
            onBeforeLoad: function(){
                disable_btns();
            },
            onInit: function(){
                //
                enable_btns();
                //
                if ($.isFunction(opts.onAddReady)){opts.onAddReady(opts);}

                // modal title
                $('#modal-title').text("Crear " + opts.section_title_singular);
                $('.btnAdd2').html("<i class='fa fa-plus'></i> Crear");


                //
                if ($.isFunction(opts.onAddEditReady)){opts.onAddEditReady(null, opts);}
                //
                if (!opts.preventBindForm){
                    opts.bindAddUpdate();
                }

            }
        });
    }



    //
    $(btn_add_record).click(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        loadModalAddEdit();
    });

    //
    if ( opts.task && opts.task === "add" ){
        //
        loadModalAddEdit();
    }



    /*
     * Eliminar Multiples registros
     * */
    $('.btnDelete').click(function(e){
        e.preventDefault();
        e.stopImmediatePropagation();

        // todo -
        alert("implementar"); return;

        //
        var del_ids = $(grid_id).getGridParam("selarrrow");
        if ( !($.isArray(del_ids) && del_ids.length > 0) ){
            app.Toast.fire({ icon: 'info', title: "Marque los registros a eliminar" });
            return;
        }

        //
        if (confirm("Eliminar registros con folios " + del_ids.join(", ") + "?")){

            //------------------------------------------------------------- Deletion
            var del_deferreds = [];
            var deleted_ids = '';

            /*
             * Add Tasks
             * */
            $.each(del_ids, function(i, id){

                //
                del_deferreds.push(

                    //
                    $.ajax({
                        type: "POST",
                        url: opts.endpoint_url + "/del",
                        data: $.param({
                            id: id
                        }),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        success: function (response) {
                            //console.log(response.data);

                            //
                            if (response.id) {

                                //
                                console.log("registro con folio " + id + " eliminado correctamente");
                                $('#grid_section').DataTable().ajax.reload();


                                deleted_ids += id + ', ';
                            }

                            //
                            else if (response.error) {

                                //
                                console.log(response.error);
                            }

                        },
                        error: function () {

                            //
                            console.log("No se pudo completar la operación. Verifica tu conexión o contacta al administrador al intentar eliminar el registro con id " + id);

                        }
                    })

                )


            });


            //
            console.log("Eliminando registros");
            $.when.apply(null, del_deferreds)
                .done(function(a) {

                    //
                    console.log('Registro(s) con id(s) ' + deleted_ids + ' eliminado(s) correctamente');

                });

        }

    });


    //
    $(btn_reload).click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        //$(grid_id).find("tbody").remove();
        $(grid_id).DataTable().ajax.reload();
        //
        $.isFunction(opts.onReload) ? opts.onReload() : null;
    });




    //
    opts.setEditCommonInfo = function(section_data){
        //
        $('#registro_folio').text(section_data.id);
        //
        if (section_data.datetime_created && section_data.datetime_created.date){
            $('#registro_fecha_creacion').text(fmtDateSpanish(section_data.datetime_created.date, true));
        }
        //
        $('.edit-mode-only').show();

        //
        var editFieldName = opts.editFieldName || "nombre";
        var field_val = section_data[editFieldName] || section_data["id"] || "";
        //
        $('#modal-title').text("Editar " + opts.section_title_singular + " - " + field_val);
        $('.btnAdd2').html("<i class='fa fa-save'></i> Guardar ");
    }


    //
    opts.bindAddUpdate = function(append_url){
        console.log("bindaddupdate: ", append_url)

        //
        var self = this;
        //
        append_url = (append_url) ? "/" + append_url : "";


        //
        if (opts.preventPost){
            return true;
        }

        //
        else if (opts.usePost){
            //
            $(opts.btnSave).click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();


                //
                var post_data = ( $.isFunction(opts.setPostData) ? opts.setPostData() : null);
                preload(".section-preloader, .overlay", true);

                //
                post({
                    url: opts.endpoint_url + append_url,
                    data: post_data,
                    success: function(send_response){
                        //
                        preload(".section-preloader, .overlay");
                        //
                        if (send_response && send_response.id){

                            //
                            var str_msg = (append_url) ? "Registro Editado Exitosamente" : "Registro Agregado Exitosamente";
                            app.Toast.fire({ icon: 'success', title: str_msg });
                            //
                            $("#modal-section").find('.modal').modal("hide");
                            $("#grid_section").DataTable().ajax.reload();
                        }
                        //
                        else if (send_response.error){
                            app.Toast.fire({ icon: 'error', title: send_response.error });
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                        }
                    },
                    error: function(){
                        //
                        preload(".section-preloader, .overlay");
                        app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                    }
                });


            });
        }
        //
        else {


            /*
            * Fix SummerNote con jquery validate para contenido oculto
            * */
            $.validator.setDefaults({
                ignore: ":hidden:not(textarea), [contenteditable='true']:not([name])"
            });


            //
            $(form_name).validate();
            //
            $(form_name).submit(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                //
                if ( $(form_name).valid() ) {
                        //
                        $(form_name).ajaxSubmit({
                            url: opts.endpoint_url + append_url,
                            beforeSubmit: function(arr, $form, options){
                                //
                                disable_btns();
                                preload(".section-preloader, .overlay", true);
                                //
                                if (opts.beforeSubmit && $.isFunction(opts.beforeSubmit)){
                                    opts.beforeSubmit(arr);
                                }
                                //
                                if (opts.authToken && opts.Utype){
                                    options.headers = {
                                        'Authorization': opts.authToken,
                                        'Utype': opts.Utype
                                    }
                                }
                            },
                            success: function(send_response){
                                //
                                if (send_response && send_response.id){

                                    //
                                    var ret_val = true;
                                    //
                                    if (opts.onAddEditSuccess && $.isFunction(opts.onAddEditSuccess)){
                                        ret_val = opts.onAddEditSuccess(opts, send_response);
                                    }

                                    //
                                    if (ret_val){
                                        /* Si es reloadDataOnSave entonces re-cargamos los datos */
                                        if (opts.reloadDataOnSave){
                                            //
                                            app.Toast.fire({ icon: 'success', title: "Datos actualizados correctamente" });
                                            //
                                            enable_btns();
                                            preload(".section-preloader, .overlay");
                                            opts.loadData();
                                        }
                                        /* Si es reloadPageOnSave entonces recargamos la pagina */
                                        else if (opts.reloadPageOnSave){
                                            //
                                            app.Toast.fire({ icon: 'success', title: "Datos actualizados correctamente" });
                                            //
                                            setTimeout(function(){
                                                location.reload();
                                            }, 500);
                                        }
                                        /* Si es redirectAfterAdd entonces redireccionamos a pagina */
                                        else if (opts.redirectAfterAdd){
                                            //
                                            app.Toast.fire({ icon: 'success', title: "Datos actualizados correctamente" });
                                            //
                                            setTimeout(function(){
                                                location.href = opts.endpoint_url + "/" + send_response.id + "/edit";
                                            }, 500);
                                        }
                                        /* Si es otro entonces cerramos el modal y volvemos a cargar los datos */
                                        else {
                                            //
                                            app.Toast.fire({ icon: 'success', title: "Datos actualizados correctamente" });
                                            $("#"+modal_add_id).find('.modal').modal("hide");
                                            $(grid_id).DataTable().ajax.reload();
                                            //
                                            setTimeout(function(){
                                                enable_btns();
                                                preload(".section-preloader, .overlay");
                                            }, 500);
                                        }
                                    }

                                } else {
                                    //
                                    enable_btns();
                                    preload(".section-preloader, .overlay");
                                    //
                                    const err = (send_response.error) ? send_response.error : "No se pudo completar la operación. contacta al administrador.";
                                    app.Toast.fire({icon: 'error', title: err});
                                }
                            },
                            error: function(response_error){
                                enable_btns();
                                preload(".section-preloader, .overlay");
                                //
                                app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                            }
                        });
                }
            });

        }
    }




    //
    if ($.isFunction(opts.onSectionReady)){
        opts.onSectionReady(opts);
    }




    //
    if (opts.gridOptions){
        app.onGridReady = function(){


            /*
            * Aqui se pasan dos referencias de ubicacion de cada boton: 1 para vista regular y 2 para vista responsiva
            * */
            if ( $(grid_id+" .btn-edit, "+grid_id+" .dtr-data.btn-edit").length ){

                $(grid_id+" .btn-edit, "+grid_id+" .dtr-data.btn-edit").click(function(e) {
                    e.preventDefault();


                    //var record_info = grid_table.row( getTrRespElem(this) ).data();
                    var record_info = $(this).data("info");
                    //console.log(record_info); return;

                    //
                    preload(".section-preloader, .overlay", true);
                    disable_btns();




                    //
                    $.ajax({
                        type:'GET',
                        url: opts.endpoint_url + "/" + record_info.id,
                        beforeSend: function (xhr) {
                            if (opts.authToken){xhr.setRequestHeader ("Authorization", opts.authToken);}
                            if (opts.Utype){xhr.setRequestHeader ("Utype", opts.Utype);}
                        },
                        success:function(section_data){

                            //
                            enable_btns();
                            preload(".section-preloader, .overlay");

                            //
                            if ( section_data && section_data.id ){

                                //
                                loadModalV2({
                                    id: modal_add_id,
                                    modal_size: modalSize,
                                    /*data: {id_caja: 1234}, // pasa objeto de datos cuando llama a un modulo js*/
                                    /*onHide: function(){},*/
                                    html_tmpl_url: opts.scripts_path + "/modals/" + modalEditHtmlName + "?v=" + dynurl(),
                                    onBeforeLoad: function(){
                                        disable_btns();
                                    },
                                    onInit: function(){
                                        //
                                        enable_btns();

                                        //
                                        if ($.isFunction(opts.onEditReady)){opts.onEditReady(section_data, opts);}

                                        //
                                        opts.setEditCommonInfo(section_data);

                                        //
                                        if ($.isFunction(opts.onAddEditReady)){opts.onAddEditReady(section_data, opts);}
                                        //
                                        if (!opts.preventBindForm){
                                            opts.bindAddUpdate(section_data.id);
                                        }

                                    }
                                });

                            }
                            //
                            else {
                                let err = (section_data && section_data.error) ? section_data.error : "Error al llamar al recurso"
                                app.Toast.fire({ icon: 'error', title: err });
                            }
                        },
                        error: function(){
                            //
                            enable_btns();
                            preload(".section-preloader, .overlay");
                            //
                            app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                        }
                    });

                });

            }


            //
            if ( $(grid_id+" .btn-eliminar").length ){

                //
                $(grid_id+" .btn-eliminar").click(function(e){
                    e.preventDefault();


                    //var record_info = grid_table.row( getTrRespElem(this) ).data();
                    var del_record_id = $(this).data("id");
                    //console.log(record_info); return;
                    if (!del_record_id){
                        console.error("no record id found, unable to delete"); return;
                    }

                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "Eliminar registro con folio " + del_record_id + "?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            
                            //alert("Ok!");


                            //
                        var delete_url = (opts.delUrl) ? opts.delUrl : opts.endpoint_url + "/del";
                        //
                        $.ajax({
                            type:'POST',
                            url: delete_url,
                            data: $.param({
                                id: del_record_id
                            }),
                            beforeSend: function (xhr) {
                                //
                                if (opts.authToken){xhr.setRequestHeader ("Authorization", opts.authToken);}
                                if (opts.Utype){xhr.setRequestHeader("Utype", opts.Utype);}
                                //
                                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            },
                            success:function(response){
                                //console.log(response.data);
                                if (response.id){
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Registro eliminado correctamente" });
                                    $(grid_id).DataTable().ajax.reload();
                                }
                                //
                                else if (response.error){
                                    //
                                    app.Toast.fire({ icon: 'error', title: response.error});
                                }
                                //
                                else {
                                    app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                                }
                            },
                            error: function(){
                                app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                            }
                        });

                        }
                    });
                
                
                });

            }



            // allow user to call onGridReady as well
            if ($.isFunction(opts.onGridReady)){
                opts.onGridReady(opts);
            }
        }




        //
        var extGridOptions = {
            gridId: grid_id,
            url: opts.endpoint_url,
            dataReady: (($.isFunction(opts.onDataReady)) ? opts.onDataReady : null),
            gridReady: app.onGridReady
        }
        //
        if (opts.authToken && opts.Utype){
            extGridOptions.authToken = opts.authToken;
            extGridOptions.Utype = opts.Utype;
        }
        //
        return dataGrid($.extend(extGridOptions, opts.gridOptions));
    }





    //
    opts.loadData = function(){
        //
        disable_btns();
        preload(".section-preloader, .overlay", true);

        //
        var endpointUrl = opts.endpoint_url;
        //
        if (opts.getUrl){
            endpointUrl = opts.getUrl;
        }
        //
        else if (opts.record_id){
            endpointUrl = opts.endpoint_url + "/" + opts.record_id;
        }

        //
        $.ajax({
            type:'GET',
            url: endpointUrl,
            success:function(response){
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                //
                if ( response && response.id ){
                    //
                    opts.onEditReady(response, opts);
                    //
                    opts.setEditCommonInfo(response);


                    //
                    if ($.isFunction(opts.onAddEditReady)){opts.onAddEditReady(response, opts);}

                    /*
                    * RECUERDA QUE CADA QUE SE MANDA LLAMAR A LOADDATA SE VUELVEN A CARGAR LOS MODULES
                    * */
                    if ($.isFunction(opts.loadModules)){opts.loadModules(response, opts);}

                }
                //
                else if (response && response.error){
                    app.Toast.fire({ icon: 'error', title: response.error});
                }
                //
                if (!opts.preventBindForm){
                    //
                    if ( opts.preventPassId ){
                        opts.bindAddUpdate();
                    }
                    //
                    else {
                        var response_id = (response && response.id ? response.id : "");
                        opts.bindAddUpdate(response_id);
                    }
                }


            },
            error: function(){
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                //
                app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
            }
        });
    }



    //
    if ( opts.record_id && !opts.preventTrigger ){
        opts.loadData();
    }



}

