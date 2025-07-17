define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_empleado_sucursales",
            section_title: "Empleado Sucursales",
            data: section_data,
            section_title_singular: "Empleado Sucursal",
            scripts_path: "/app/empleados/modules",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/sucursales",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"visible": false, "name" : "id", "data" : "id"},
                    {"visible": false, "name" : "empleado_id", "data" : "empleado_id"},
                    {"name": "sucursal", "data" : "sucursal"},
                    {"name": "active", "data" : function(obj){
                            if (obj.active){
                                return "<span class='badge badge-success'>Habilitado</span>";
                            }
                            return "";
                    }},
                    {"data" : function(obj){
                            return "--";
                        }},
                    {"data": function(obj){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            if (obj.active){
                                str_btns += " <button type='button' class='btn btn-sm btn-flat btn-outline-warning btn-actualizar-estado' data-info='"+data_info+"'><i class='fas fa-arrow-down'></i> Inhabilitar </button> ";
                            } else {
                                str_btns += " <button type='button' class='btn btn-sm btn-flat btn-outline-success btn-actualizar-estado' data-info='"+data_info+"'><i class='fas fa-check'></i> Habilitar </button> ";
                            }

                            //
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-permisos' data-info='"+data_info+"'><i class='fas fa-key'></i> Permisos </button> ";

                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                columnDefs: [
                    { "targets": [0, 2, 5],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onGridReady: function(opts){

                //
                $(opts.gridId+" .btn-actualizar-estado").click(function(e){
                    e.preventDefault();

                    var record_info = $(this).data("info");
                    //console.log(record_info); return;

                    //
                    var str_msg = "";
                    var new_state = 0;
                    //
                    if (record_info.active){
                        str_msg = "Inhabilitar sucursal " + record_info.sucursal + "?";
                        new_state = 0;
                    } else {
                        str_msg = "Habilitar sucursal " + record_info.sucursal + "?";
                        new_state = 1;
                    }




                    //
                    if (confirm(str_msg)){
                        //
                        $.ajax({
                            type:'POST',
                            url: section_data.opts.endpoint_url + "/" + section_data.id + "/sucursales/update-state",
                            data: $.param({
                                id: record_info.id,
                                new_state: new_state
                            }),
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            success:function(response){
                                //console.log(response.data);
                                if (response.id){
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Registro eliminado correctamente" });
                                    $(opts.gridId).DataTable().ajax.reload();
                                }
                                //
                                else if (response.error){
                                    //
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


            },
            onSectionReady: function(opts){


                //
                loadSelectAjax({
                    id: "#sucursal_id",
                    url: app.supadmin_url + "/sucursales/list",
                    parseFields: function(item){
                        return item.name + " - " + item.address;
                    },
                    prependEmptyOption: true,
                    emptyOptionText: "--select",
                    enable: true
                });


                //
                $('#form_sucursales').validate();
                //
                $('#form_sucursales').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_sucursales').valid() ) {
                        //
                        $('#form_sucursales').ajaxSubmit({
                            url: section_data.opts.endpoint_url + "/" + section_data.id + "/sucursales",
                            beforeSubmit: function(arr){
                                disable_btns();
                            },
                            success: function(response){
                                //
                                enable_btns();
                                //
                                if (response && response.id){
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Registro Agregado Correctamente" });
                                    //
                                    $("#grid_empleado_sucursales").DataTable().ajax.reload();
                                    $("#sucursal_id").val("");
                                    //
                                    section_data.opts.loadData();
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
                            error: function(response){
                                enable_btns();
                                //
                                app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                            }
                        });

                    }
                });



                //
                $('#btnReloadSucursales').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_empleado_sucursales").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});