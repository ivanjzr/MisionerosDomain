define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        var section_validator = $('#form_section').validate();


        //
        $('#nombre').val(section_data.nombre);
        $('#clave').val(section_data.clave);
        $('#monto').val(section_data.monto);


        //
        $('#registro_folio').text(section_data.id);
        //$('#registro_fecha_creacion').text(section_data.datetime_created);
        //
        $('.edit-mode-only').removeClass("hidden");

        //
        $('#modal-title').text("Editar Tipo de Cambio - " + section_data.nombre);
        $('.btnAdd2').html("<i class='fa fa-save'></i> Guardar Cambios ");




        //
        $('#form_section').submit(function(e) {
            e.preventDefault();

            //
            if ( section_validator.valid() ) {


                //
                $('#form_section').ajaxSubmit({
                    url: api_url + "/tipos-cambio/" + section_data.id,
                    beforeSubmit: function(arr){
                        disable_btns();
                        preload(true);
                    },
                    success: function(response){

                        //
                        enable_btns();
                        preload(false);
                        //
                        if ( response && response.id ){
                            //
                            $("#modal-section").find('.modal').modal("hide");
                            $('#grid_section').trigger("reloadGrid");
                            //Validator.resetForm();
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
        $('#nombre').focus();

    }
    return {init: moduleReady}
});






