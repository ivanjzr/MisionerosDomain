define(function(){
    function moduleReady(product_info){
        console.log(product_info);





        //
        $('#active').attr("checked", true);


        // modal title
        $('#modal-title').text("Inventario - " + product_info.nombre + ": Agregar");
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Agregar");




        //
        loadSelectAjax({
            id: "#proveedor_id",
            url: api_url + "/proveedores/list",
            parseFields: function(item){
                return item.nombre + " - " + item.address + ", " + item.ciudad;
            },
            prependEmptyOption: true,
            emptyOptionText: "--ajuste",
            enable: true
        });



        //
        $('#form_section').validate();
        //
        $('#form_section').submit(function(e) {
            e.preventDefault();


            //
            if ( $('#form_section').valid() ) {


                //
                if (!confirm("Agregar Entrada")){
                    return;
                }

                //
                $('#form_section').ajaxSubmit({
                    url: api_url + "/stock/" + product_info.id + "/stock/add",
                    beforeSubmit: function(arr){
                        disable_btns();
                        preload(true);
                    },
                    success: function(response){
                        //
                        enable_btns();
                        preload(false);
                        //
                        if (response && response.id){
                            $("#modal-section").find('.modal').modal("hide");
                            $('#grid_stock').trigger("reloadGrid");
                        }
                        //
                        else if (response.error){
                            smartalert("Error", "<i class='fa fa-clock-o'></i> <i>"+response.error+"</i>", "#C46A69", "fa fa-times fa-2x fadeInRight animated");
                        }
                        //
                        else {
                            smartalert("Error", "<i class='fa fa-clock-o'></i> <i>No se pudo completar la operación. Verifica tu conexión o contacta al administrador al intentar guardar el registro</i>", "#C46A69", "fa fa-times fa-2x fadeInRight animated");
                        }
                    },
                    error: function(response){
                        enable_btns();
                        preload(false);
                        //
                        smartalert("Error", "<i class='fa fa-clock-o'></i> <i>No se pudo completar la operación. Verifica tu conexión o contacta al administrador al intentar guardar el registro</i>", "#C46A69", "fa fa-times fa-2x fadeInRight animated");
                    }
                });

            }
        });




        // def focus
        $('#new_cantidad').focus();

    }
    return {init: moduleReady}
});






