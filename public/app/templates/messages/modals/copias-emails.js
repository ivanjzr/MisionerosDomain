define(function(){
    function moduleReady(modal, section_data){
        //console.log(modal, section_data);



        //
        $('#registro_folio').text(section_data.id);
        //
        if (section_data.datetime_created && section_data.datetime_created.date){
            $('#registro_fecha_creacion').text(fmtDateSpanish(section_data.datetime_created.date, true));
        }
        //
        $('.edit-mode-only').show();

        // modal title
        $('#modal-title').text("Copia de Correos Mensaje folio #" + section_data.id);
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Agregar");






        //
        var add_edit_url = "", str_msg = "";

        //
        function setAddMode(){

            //
            $("#email2").val("");
            $('#active2').attr("checked", true);

            /*---------------------- info -----------------------*/
            //
            $("#btnAddUpdate").text("Agregar");
            $("#btnCancel").hide();
            //
            str_msg = "Registro Agregado Exitosamente";
            add_edit_url = app.admin_url + "/templates-messages/" + section_data.id + "/copias-emails";
            //
            $("#email2").focus();
        }


        //
        function setEditMode(record_id, email, active){

            //
            $("#email2").val(email);
            //
            if (active){
                $('#active2').attr("checked", true);
            } else {
                $('#active2').attr("checked", false);
            }


            /*---------------------- info -----------------------*/
            //
            $("#btnAddUpdate").text("Guardar");
            $("#btnCancel").show();
            //
            str_msg = "Registro Editado Exitosamente";
            add_edit_url = app.admin_url + "/templates-messages/" + section_data.id + "/copias-emails/" + record_id;
            //
            $("#email2").focus();
        }



        // Initial Value
        setAddMode();



        //
        $('#btnCancel').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //
            setAddMode();
        });





        //
        dataGrid({
            gridId: "#grid_copias_emails",
            url: app.admin_url + "/templates-messages/" + section_data.id + "/copias-emails",
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id", "title": "Folio"},
                {"name": "mensaje_id", "data" : "mensaje_id", "title": "mensaje_id", visible: false},
                {"name": "email", "data" : "email", "title": "email"},
                {"title": "habilitado", "data" : function(obj){ return fmtActive(obj.active); }},
                {"data" : function(obj){
                        //
                        var section_data = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-editar-record' data-info='"+section_data+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar-record' data-info='"+section_data+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 4],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]],
            gridReady: function(){


                //
                $('.btn-editar-record').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    var subsection_data = $(this).data("info");
                    disable_btns();

                    //
                    $.ajax({
                        type:'GET',
                        url: app.admin_url + "/templates-messages/" + section_data.id + "/copias-emails/" + subsection_data.id,
                        success:function(response2){

                            //
                            enable_btns();

                            //
                            if ( response2 && response2.id ){
                                //
                                setEditMode(response2.id, response2.email, response2.active);
                            }
                            //
                            else if (response2.error){
                                app.Toast.fire({ icon: 'success', title: response2.error });
                            }
                            //
                            else {
                                app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                            }
                        },
                        error: function(){
                            //
                            enable_btns();
                            //
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }
                    });

                });






                //
                $('.btn-eliminar-record').click(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    var subsection_data = $(this).data("info");

                    //
                    if (confirm("Eliminar registro con folio " + subsection_data.id + "?")){
                        //
                        $.ajax({
                            type:'POST',
                            url: app.admin_url + "/templates-messages/" + section_data.id + "/copias-emails/del",
                            data: $.param({
                                id: subsection_data.id
                            }),
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            success:function(response2){
                                //console.log(response2.data);
                                if (response2.id){
                                    //
                                    $("#grid_section").DataTable().ajax.reload();
                                    $("#grid_copias_emails").DataTable().ajax.reload();
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Registro eliminado correctamente" });
                                    //
                                    setAddMode();
                                }
                                //
                                else if (response2.error){
                                    app.Toast.fire({ icon: 'error', title: response2.error});
                                }
                                //
                                else {
                                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                                }
                            },
                            error: function(){
                                app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                            }
                        });
                    }

                });

            }
        });







        //
        var subform_name = '#form_section';
        //
        $(subform_name).validate();
        //
        $(subform_name).submit(function(e) {
            e.preventDefault();
            //
            if ( $(subform_name).valid() ) {


                //
                $(subform_name).ajaxSubmit({
                    url: add_edit_url,
                    beforeSubmit: function(arr){
                        disable_btns();
                        preload(".section-preloader", true)
                    },
                    success: function(response2){
                        //
                        enable_btns();
                        preload(".section-preloader")
                        //
                        if (response2 && response2.id){
                            //
                            $("#grid_section").DataTable().ajax.reload();
                            $("#grid_copias_emails").DataTable().ajax.reload();
                            //
                            app.Toast.fire({ icon: 'success', title: str_msg });
                            //
                            setAddMode();
                        }
                        //
                        else if (response2.error){
                            app.Toast.fire({ icon: 'error', title: response2.error });
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }
                    },
                    error: function(response2){
                        enable_btns();
                        preload(".section-preloader")
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });

            }
        });


    }
    return {init: moduleReady}
});






