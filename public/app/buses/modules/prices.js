define(function(){
    function moduleReady(section_data){
        //console.log(section_data);



        //
        var precios_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/prices";


        //
        function setMode(precio_id, product_precio){
            //
            $("#product_precio").focus();

            // Edit Mode
            if (precio_id && product_precio){
                //
                $("#product_precio").val(product_precio);
                $("#btnAddUpdate").text("Save");
                $("#btnCancel").show();
                //
                precios_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/prices/" + precio_id;
            }

            // Add Mode
            else {
                //
                $("#product_precio").val("");
                $("#btnAddUpdate").text("Add");
                $("#btnCancel").hide();
                //
                precios_add_edit_url = section_data.opts.endpoint_url + "/" + section_data.id + "/prices/";
            }
        }


        //
        app.createSection({
            gridId: "#grid_bus_prices",
            section_title: "Prices",
            data: section_data,
            section_title_singular: "Price",
            scripts_path: "/app/buses/prices",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/prices",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"visible": false, "name" : "bus_id", "data" : "bus_id"},
                    {"name": "precio", "data" : function(obj){ return fmtAmount(obj.precio); }},
                    {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date, true); }},
                    {"visible": false, "data": function(obj){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
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
                $('#form_prices').validate();
                //
                $('#form_prices').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_prices').valid() ) {
                        //
                        $('#form_prices').ajaxSubmit({
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
                                    app.Toast.fire({ icon: 'success', title: "Record added succesfully" });
                                    //
                                    setMode();
                                    //
                                    $("#grid_bus_prices").DataTable().ajax.reload();
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
                    $("#grid_bus_prices").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});