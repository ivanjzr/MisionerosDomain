(function ($) {
    'use strict';



    function onDataReady(data){

        //
        $("#phone_number").val(data.phone_number);
        $("#account_sid").val(data.account_sid);
        $("#auth_token").val(data.auth_token);
        //
        $("#phone_number_test").val(data.phone_number_test);
        $("#account_sid_test").val(data.account_sid_test);
        $("#auth_token_test").val(data.auth_token_test);
        //
        if (data.is_prod){
            $("#is_prod_prod").attr("checked", "checked");
        } else {
            $("#is_prod_dev").attr("checked", "checked");
        }
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
        url: app.supadmin_url + "/config/twilio",
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
                url: app.supadmin_url + "/config/twilio",
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



    //
    $('#btnTest').click(function(e){
        e.preventDefault();

        //
        loadModalV2({
            id: "modal-send-test",
            modal_size: "lg",
            html_tmpl_url: "/app/config/config_twilio/modals/send-test.html?v=" + dynurl(),
            onBeforeLoad: function(){
                disable_btns();
            },
            onInit: function(){
                //
                enable_btns();



                //
                $("#form_send_test").validate();
                //
                $("#form_send_test").submit(function(e) {
                    e.preventDefault();
                    //
                    if ( $("#form_send_test").valid() ) {

                        //
                        $("#form_send_test").ajaxSubmit({
                            url: app.supadmin_url + "/config/twilio/send-test",
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
                                    app.Toast.fire({ icon: 'success', title: "Mensaje enviado modo " + send_response.mode });
                                    $("#phone_number_send").val("");
                                    $("#message_send").val("");
                                    $("#modal-send-test").find('.modal').modal("hide");
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



            }
        });

    });




})(jQuery);