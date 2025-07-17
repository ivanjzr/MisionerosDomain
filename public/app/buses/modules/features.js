define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_bus_features",
            section_title: "Features",
            data: section_data,
            section_title_singular: "Feature",
            scripts_path: "/app/buses/features",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/features",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"visible": false, "name" : "feature_id", "data" : "feature_id"},
                    {"name": "feature_name", "data" : "feature_name"},
                    {"name": "feature_color", "data" : function(obj){ 
                        return "<div style='background-color:"+obj.feature_color+";padding:5px;color:blue;'>"+obj.feature_color+"</div>";
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
                    id: "#feature_id",
                    url: app.admin_url + "/buses/features/list",
                    parseFields: function(item){
                        return item.nombre;
                    },
                    prependEmptyOption: true,
                    emptyOptionText: "--select",
                    enable: true
                });


                //
                $('#form_features').validate();
                //
                $('#form_features').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_features').valid() ) {
                        //
                        $('#form_features').ajaxSubmit({
                            url: section_data.opts.endpoint_url + "/" + section_data.id + "/features",
                            beforeSubmit: function(arr){
                                disable_btns();
                            },
                            success: function(response){
                                //
                                enable_btns();
                                //
                                if (response && response.id){
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Record Added" });
                                    //
                                    $("#grid_bus_features").DataTable().ajax.reload();
                                    $("#feature_id").val("");
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
                $('#btnReloadFeatures').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_bus_features").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});