(function ($) {
    'use strict';



    function onDataReady(data){

        //
        $("#host").val(data.host);
        $("#sender_name").val(data.sender_name);
        $("#security_type").val(data.security_type);
        $("#port").val(data.port);
        //
        if (data.active){
            $("#active").attr("checked", "checked");
        } else {
            $("#active").removeAttr("checked", "checked");
        }

        setTimeout(function(){

            //
            $("#email")
                .removeAttr("readonly")
                .val(data.email);
            //
            $("#username")
                .removeAttr("readonly")
                .val(data.username);
            
            //
            $("#password")
                .removeAttr("readonly")
                .val(data.password);

        }, 1000)

    }


    //
    disable_btns();
    preload(".section-preloader, .overlay", true);
    //
    $.ajax({
        type:'GET',
        url: app.admin_url + "/mail-config",
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
            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
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
                url: app.admin_url + "/mail-config",
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
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                },
                error: function(response_error){
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
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
            modal_size: "md",
            html_tmpl_url: "/app/mail_config/modals/send-test.html?v=" + dynurl(),
            onBeforeLoad: function(){
                disable_btns();
            },
            onInit: function(){
                //
                enable_btns();


                $("#to_email").focus();


                //
                $("#form_send_test").validate();
                //
                $("#form_send_test").submit(function(e) {
                    e.preventDefault();
                    //
                    if ( $("#form_send_test").valid() ) {

                        //
                        $("#form_send_test").ajaxSubmit({
                            url: app.admin_url + "/mail-config/send-test",
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
                                if (send_response && send_response.success){

                                    //
                                    app.Toast.fire({ icon: 'success', title: "Email enviado exitosamente" });
                                    $("#to_email").val("");
                                    $("#subject").val("");
                                    $("#message").val("");
                                    $("#modal-send-test").find('.modal').modal("hide");
                                }
                                //
                                else {
                                    //
                                    const err = (send_response.error) ? send_response.error : "The operation could not be completed. Check your connection or contact the administrator.";
                                    app.Toast.fire({ icon: 'error', title: err });
                                }
                            },
                            error: function(response_error){
                                enable_btns();
                                preload(".section-preloader, .overlay");
                                //
                                app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                            }
                        });

                    }
                });



            }
        });

    });




})(jQuery);