define(function(){
    function moduleReady(product_info){
        console.log(product_info);





        // modal title
        $('#modal-title').text("Actualizar - " + product_info.nombre);
        $('.btnAdd2').html("<i class='fa fa-save'></i> Guardar");



        //
        $('#cant_min_notify').val(product_info.cant_min_notify);

        //
        if (product_info.aplica_stock){
            $('#aplica_stock').attr("checked", true);
        } else {
            $('#aplica_stock').attr("checked", false);
        }




        //
        $('#form_section').validate();
        //
        $('#form_section').submit(function(e) {
            e.preventDefault();

            //
            if ( $('#form_section').valid() ) {

                //
                $('#form_section').ajaxSubmit({
                    url: api_url + "/stock/" + product_info.id + "/update",
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
                            $('#grid_section').trigger("reloadGrid");
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
        $('#cant_min_notify').focus();

    }
    return {init: moduleReady}
});






