define(function(){
    function moduleReady(section_data){
        //console.log(section_data);



        //
        var precios_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/precios";


        //
        function setMode(precio_id, product_precio){
            //
            $("#product_precio").focus();

            // Edit Mode
            if (precio_id && product_precio){
                //
                $("#product_precio").val(product_precio);
                $("#btnAddUpdate").text("Guardar");
                $("#btnCancel").show();
                //
                precios_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/precios/" + precio_id;
            }

            // Add Mode
            else {
                //
                $("#product_precio").val("");
                $("#btnAddUpdate").text("Agregar");
                $("#btnCancel").hide();
                //
                precios_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/precios/";
            }
        }


        //
        app.createSection({
            gridId: "#grid_product_precios",
            section_title: "Vinos",
            data: section_data,
            section_title_singular: "Vino",
            scripts_path: "/app/vinos/vinos",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/precios",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"visible": false, "name" : "product_id", "data" : "product_id"},
                    {"name": "precio", "data" : function(obj){ return fmtAmount(obj.precio); }},
                    {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date, true); }},
                    {"visible": false, "data": function(obj){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
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
                // def focus
                $('#product_precio').focus();
            },
            onGridReady: function(opts){
                //
            },
            onSectionReady: function(opts){



                //
                $('#form_precios').validate();
                //
                $('#form_precios').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_precios').valid() ) {
                        //
                        $('#form_precios').ajaxSubmit({
                            url: precios_add_edit_url,
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
                                    setMode();
                                    //
                                    $("#grid_product_precios").DataTable().ajax.reload();
                                    $("#product_precio").val("");
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
                $('#btnCancel').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    setMode();
                });


                //
                $('#btnReloadPrices').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_product_precios").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});