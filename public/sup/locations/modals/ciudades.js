define(function(){
    function moduleReady(modal, section_data){
        //console.log(modal, data_info);



        //
        $('#registro_folio').text(section_data.id);
        //
        if (section_data.datetime_created && section_data.datetime_created.date){
            $('#registro_fecha_creacion').text(fmtDateSpanish(section_data.datetime_created.date, true));
        }
        //
        $('.edit-mode-only').show();



        // modal title
        $('#modal-title').text("Ciudades de " + section_data.nombre);
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Agregar");



        //
        $("#btnAddUpdate").text("Agregar");

        //
        var add_edit_url = app.supadmin_url + "/locations/" + section_data.id + "/ciudades";
        var str_msg = "";

        //
        function setMode(ciudad_id, nombre, abreviado){
            //
            $("#nombre").focus();

            // Edit Mode
            if (ciudad_id){
                //
                $("#nombre").val(nombre);
                $("#abreviado").val(abreviado);
                //
                $("#btnAddUpdate").text("Guardar");
                $("#btnCancel").show();
                //
                str_msg = "Registro Editado Exitosamente";
                add_edit_url = app.supadmin_url + "/locations/" + section_data.id + "/ciudades/" + ciudad_id;
            }

            // Add Mode
            else {
                //
                $("#nombre").val("");
                $("#abreviado").val("");
                //
                $("#btnAddUpdate").text("Agregar");
                $("#btnCancel").hide();
                //
                str_msg = "Registro Agregado Exitosamente";
                add_edit_url = app.supadmin_url + "/locations/" + section_data.id + "/ciudades";
            }
        }


        //
        setMode();



        //
        $('#btnCancel').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //
            setMode();
        });





        //
        dataGrid({
            gridId: "#grid_estado_ciudades",
            url: app.supadmin_url + "/locations/" + section_data.id + "/ciudades",
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id", "title": "Folio"},
                {"name" : "nombre", "data" : "nombre", "title": "Ciudad"},
                {"name" : "abreviado", "data" : "abreviado", "title": "Abreviado"},
                {"data" : function(obj){
                        //
                        var section_data = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-editar-ciudad' data-info='"+section_data+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar-ciudad' data-info='"+section_data+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 3],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]],
            gridReady: function(){


                //
                $('.btn-editar-ciudad').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    var subsection_data = $(this).data("info");
                    disable_btns();

                    //
                    $.ajax({
                        type:'GET',
                        url: app.supadmin_url + "/locations/" + section_data.id + "/ciudades/" + subsection_data.id,
                        success:function(record_data){

                            //
                            enable_btns();

                            //
                            if ( record_data && record_data.id ){
                                //
                                setMode(record_data.id, record_data.nombre, record_data.abreviado);
                            }
                            //
                            else if (record_data.error){
                                app.Toast.fire({ icon: 'success', title: record_data.error });
                            }
                            //
                            else {
                                app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                            }
                        },
                        error: function(){
                            //
                            enable_btns();
                            //
                            app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                        }
                    });

                });






                //
                $('.btn-eliminar-ciudad').click(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    var subsection_data = $(this).data("info");

                    //
                    if (confirm("Eliminar registro con folio " + subsection_data.id + "?")){
                        //
                        $.ajax({
                            type:'POST',
                            url: app.supadmin_url + "/locations/" + section_data.id + "/ciudades/del",
                            data: $.param({
                                id: subsection_data.id
                            }),
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            success:function(response){
                                //console.log(response.data);
                                if (response.id){
                                    //
                                    $("#grid_section").DataTable().ajax.reload();
                                    $("#grid_estado_ciudades").DataTable().ajax.reload();
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Registro eliminado correctamente" });
                                    //
                                    setMode();
                                }
                                //
                                else if (response.error){
                                    app.Toast.fire({ icon: 'error', title: response.error});
                                }
                                //
                                else {
                                    app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                                }
                            },
                            error: function(){
                                app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
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
                    success: function(response){
                        //
                        enable_btns();
                        preload(".section-preloader")
                        //
                        if (response && response.id){
                            //
                            $("#grid_section").DataTable().ajax.reload();
                            $("#grid_estado_ciudades").DataTable().ajax.reload();
                            //
                            app.Toast.fire({ icon: 'success', title: str_msg });
                            //
                            setMode();
                        }
                        //
                        else if (response.error){
                            app.Toast.fire({ icon: 'error', title: response.error });
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                        }
                    },
                    error: function(response){
                        enable_btns();
                        preload(".section-preloader")
                        //
                        app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                    }
                });

            }
        });






    }
    return {init: moduleReady}
});






