define(function(){
    function moduleReady(section_data){
        console.log("----data for cfdi: ", section_data);




        //
        app.createSection({
            gridId: "#grid_sucursal_sellos",
            section_title: "Sellos",
            data: section_data,
            section_title_singular: "Sello",
            scripts_path: "/app/sucursales",
            endpoint_url: app.admin_url + "/sucursales/" + section_data.id + "/sellos",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"data": function(obj){
                            //
                            var data_info = JSON.stringify({
                                id: obj.id,
                                customer_id: obj.customer_id,
                                nombre: obj.person_name,
                                relative_id: obj.relative_id,
                                relative_type: obj.relative_type
                            });
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                deferLoading: true,
                columnDefs: [
                    { "targets": [0, 1, 2],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onEditReady: function(record_data){
                //console.log(record_data);
            },
            onGridReady: function(opts){
                //                
            },
            onSectionReady: function(opts){


                //
                $('#btnReloadSellos').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_sucursal_sellos").DataTable().ajax.reload();
                });


            }
        });




        //
        $('#btnAddSello').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            //
            loadModalV2({
                id: "modal-add-sellos",
                modal_size: "md",
                data: section_data,
                /*onHide: function(){},*/
                html_tmpl_url: "/app/sucursales/modals/add-sellos.html?v=" + dynurl(),
                js_handler_url: "/app/sucursales/modals/add-sellos.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){
                    //
                    enable_btns();
                }
            });

        });




    //
    function filterGrid(){
        $("#grid_sucursal_sellos").DataTable().ajax.url(app.admin_url + "/sucursales/" + section_data.id + "/sellos");
        $("#grid_sucursal_sellos").DataTable().ajax.reload();
    }
    //
    //filterGrid();







    //
    $('#form_sucursal_cfdi').validate();
    //
    $('#form_sucursal_cfdi').submit(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        //
        if ( $('#form_sucursal_cfdi').valid() ) {
            //
            $('#form_sucursal_cfdi').ajaxSubmit({
                url: app.admin_url + "/sucursales/" + section_data.id + "/update-cfdi-info",
                beforeSubmit: function(arr){
                    disable_btns();
                },
                success: function(response){
                    //
                    enable_btns();
                    //
                    if (response && response.id){
                        //
                        app.Toast.fire({ icon: 'success', title: "Datos actualizados correctamente" });
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





    }
    return {init: moduleReady}
});