define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_usuario_sucursales",
            section_title: "Usuario Sucursales",
            data: section_data,
            section_title_singular: "Usuario Sucursal",
            scripts_path: "/app/users/modules",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/sucursales",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"visible": false, "name" : "id", "data" : "id"},
                    {"visible": false, "name" : "user_id", "data" : "user_id"},
                    {"name": "sucursal", "data" : "sucursal"},
                    {"name": "address", "data" : "address"},
                    {"name": "tipos_permisos", "data" : function(obj){

                        // Todos los permisos
                        if ( obj.tipos_permisos === 1 ){
                            return "<span class='badge bg-success'>Admin</span>";
                        }

                        // Permisos específicos
                        else if ( obj.tipos_permisos === 2 && obj.permisos && obj.permisos.length ){
                            var str_sucursales = "";
                            
                            $.each(obj.permisos, function(idx, item){
                                
                                // Permiso padre
                                if (item.parent_id && item.parent_clave){
                                    str_sucursales += "<small class='badge bg-info me-1 mb-1'><i class='fas "+ item.fa_icon +"'></i>&nbsp;" + item.nombre + "&nbsp;( " + item.parent_clave + " )</small>";
                                } else {
                                    str_sucursales += "<span class='badge bg-primary me-1 mb-1'><i class='fas "+ item.fa_icon +"'></i>&nbsp;" + item.nombre + "</span>";
                                }                                
                            });
                            
                            return str_sucursales;
                        }

                        // Sin permisos
                        return "<span class='badge bg-warning text-dark'>Sin Permisos</span>";
                    }},
                    {"data": function(obj){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-permisos' data-info='"+data_info+"'><i class='fas fa-key'></i> Permisos </button> ";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                columnDefs: [
                    { "targets": [0, 1, 2, 4, 5, 6],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onGridReady: function(opts){




                //
                $(opts.gridId+" .btn-permisos").click(function(e) {
                    e.preventDefault();

                    //
                    var record_info = $(this).data("info");


                    // pass enpoint url to record info
                    record_info.endpoint_url = section_data.opts.endpoint_url + "/" + section_data.id + "/sucursales";

                    //
                    loadModalV2({
                        id: "modal-permisos",
                        modal_size: "lg",
                        data: record_info,
                        html_tmpl_url: opts.scripts_path + "/modals/permisos.html?v=" + dynurl(),
                        js_handler_url: opts.scripts_path + "/modals/permisos.js?v=" + dynurl(),
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
                                    app.Toast.fire({ icon: 'success', title: "Record added succesfully" });
                                    //
                                    $("#grid_usuario_sucursales").DataTable().ajax.reload();
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
                $('#btnReloadSucursales').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_usuario_sucursales").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});