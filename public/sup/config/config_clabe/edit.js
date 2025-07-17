(function ($) {
    'use strict';



    function onDataReady(data){

        //
        $("#nombre_cuenta").val(data.nombre_cuenta);
        $("#clabe_interbancaria").val(data.clabe_interbancaria);
        $("#notas").val(data.notas);
        //
        if (data.active){
            $("#active").attr("checked", "checked");
        } else {
            $("#active").removeAttr("checked", "checked");
        }
    }


    //
    disable_btns();
    preload(".section-preloader, .overlay", true);
    //
    $.ajax({
        type:'GET',
        url: app.supadmin_url + "/config/clabe",
        success:function(response){
            //
            enable_btns();
            preload(".section-preloader, .overlay");
            //
            if ( response && response.id ){
                onDataReady(response);
            }
            //
            else if (response.error){
                app.Toast.fire({ icon: 'error', title: response.error});
            }
        },
        error: function(){
            //
            enable_btns();
            preload(".section-preloader, .overlay");
            //
            app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
        }
    });






    //
    $("#form_section").validate();
    //
    $("#form_section").submit(function(e) {
        e.preventDefault();
        //
        if ( $("#form_section").valid() ) {

            //
            $("#form_section").ajaxSubmit({
                url: app.supadmin_url + "/config/clabe",
                beforeSubmit: function(arr){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay", true);
                },
                success: function(send_response){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    if (send_response && send_response.id){

                        //
                        app.Toast.fire({ icon: 'success', title: "Registro Editado Exitosamente" });
                    }
                    //
                    else if (send_response.error){
                        app.Toast.fire({ icon: 'error', title: send_response.error });
                    }
                    //
                    else {
                        app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                    }
                },
                error: function(response_error){
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                }
            });

        }
    });





})(jQuery);