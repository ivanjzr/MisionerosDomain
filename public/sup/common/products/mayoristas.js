define(function(){
    function moduleReady(section_data){
        //console.log(section_data);



        //
        var mayoristas_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/mayoristas";


        //
        function setMode2(precio_id, precio_mayoreo){
            //
            $("#mayoreo_cant_minima").focus();

            // Edit Mode
            if (precio_id && precio_mayoreo){
                //
                $("#precio_mayoreo").val(precio_mayoreo);
                $("#btnAddUpdate2").text("Guardar");
                $("#btnCancel2").show();
                //
                mayoristas_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/mayoristas" + precio_id;
            }

            // Add Mode
            else {
                //
                $("#precio_mayoreo").val("");
                $("#btnAddUpdate2").text("Agregar");
                $("#btnCancel2").hide();
                //
                mayoristas_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/mayoristas";
            }
        }


        //
        app.createSection({
            gridId: "#grid_product_precios_mayoristas",
            section_title: "Vinos",
            data: section_data,
            section_title_singular: "Vino",
            scripts_path: "/app/vinos/vinos",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/mayoristas",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"visible": false, "name" : "product_id", "data" : "product_id"},
                    {"name" : "cantidad_minima", "data" : "cantidad_minima"},
                    {"name" : "cantidad_maxima", "data" : "cantidad_maxima"},
                    {"name": "precio", "data" : function(obj){ return fmtAmount(obj.precio); }},
                    {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date, true); }},
                    {"visible": false, "data": function(obj){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencillt'></i></button> ";
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-info='"+data_info+"'><i class='fas fa-trash'></i></button>";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                columnDefs: [
                    { "targets": [0, 5],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onAddEditReady: function(){

            },
            onGridReady: function(opts){

                // def focus
                $('#mayoreo_cant_minima').focus();
            },
            onSectionReady: function(opts){



                //
                $('#form_mayoristas').validate();
                //
                $('#form_mayoristas').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_mayoristas').valid() ) {
                        //
                        $('#form_mayoristas').ajaxSubmit({
                            url: mayoristas_add_edit_url,
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
                                    setMode2();
                                    //
                                    $("#grid_product_precios_mayoristas").DataTable().ajax.reload();
                                    //
                                    $("#mayoreo_cant_minima").val("");
                                    $("#mayoreo_cant_maxima").val("");
                                    $("#precio_mayoreo").val("");
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
                $('#btnCancel2').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    setMode2();
                });




                //
                $('#btnReloadPricesMayoristas').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_product_precios_mayoristas").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});