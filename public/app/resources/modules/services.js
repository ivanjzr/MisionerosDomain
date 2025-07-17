define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_resource_services",
            section_title: "Servicios",
            data: section_data,
            section_title_singular: "Servicio",
            scripts_path: "/app/resorces",
            endpoint_url: app.admin_url + "/appointments/resources/" + section_data.id + "/services",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"name": "service_category", "data" : "service_category"},
                    {"name": "service_name", "data" : "service_name"},
                    {"name": "servicio_duracion_minutos", "data" : "servicio_duracion_minutos"},
                    {"data": function(obj){
                            //
                            //var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                columnDefs: [
                    { "targets": [0, 1, 2],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onGridReady: function(opts){
                //
            },
            onSectionReady: function(opts){


                //
                loadSelectAjax({
                    id: "#related_service_id",
                    url: app.admin_url + "/productos-servicios/available-services",
                    parseFields: function(item){
                        return item.prod_code + " - " + item.nombre + " (" + item.servicio_duracion_minutos + " mins) ";
                    },
                    prependEmptyOption: true,
                    emptyOptionText: "--select",
                    enable: true
                });


                //
                $('#form_services').validate();
                //
                $('#form_services').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_services').valid() ) {
                        //
                        $('#form_services').ajaxSubmit({
                            url: app.admin_url + "/appointments/resources/" + section_data.id + "/services",
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
                                    //
                                    $("#grid_resource_services").DataTable().ajax.reload();
                                    $("#related_service_id").val("");
                                    //
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



                //
                $('#btnReloadResourceServices').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_resource_services").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});