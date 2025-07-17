define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_product_promos",
            section_title: "Promociones",
            data: section_data,
            section_title_singular: "Promocion",
            scripts_path: "/app/productos_servicios/promos",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/promociones",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"visible": false, "name" : "promocion_id", "data" : "promocion_id"},
                    {"name": "promocion", "data" : "promocion"},
                    {"name": "datetime_created", "data" : function(obj){ 
                        return fmtDateSpanish(obj.fecha_hora_inicio.date, true) + " - " + fmtDateSpanish(obj.fecha_hora_fin.date, true); 
                    }},
                    {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date, true); }},
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
                    { "targets": [0, 3, 5],"orderable": false },
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
                    id: "#promocion_id",
                    url: app.admin_url + "/promos/list-available",
                    parseFields: function(item){
                        return item.descripcion + " (" + item.clave + ")";
                    },
                    prependEmptyOption: true,
                    emptyOptionText: "--select",
                    enable: true
                });


                //
                $('#form_promos').validate();
                //
                $('#form_promos').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_promos').valid() ) {
                        //
                        $('#form_promos').ajaxSubmit({
                            url: section_data.opts.endpoint_url + "/" + section_data.id + "/promociones",
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
                                    $("#grid_product_promos").DataTable().ajax.reload();
                                    $("#promocion_id").val("");
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
                $('#btnReloadPromociones').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_product_promos").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});