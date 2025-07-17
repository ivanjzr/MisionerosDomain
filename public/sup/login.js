$(document).ready(function(){





    var form_name = '#form_login_auth';


    //
    $(form_name).validate({
        errorElement: "label",
        errorPlacement: function(error, element) {
            var elem = $(element);
            error.insertAfter(elem.parent());
        }
    });


    /*
     *
     * */
    $(form_name).submit(function(e){
        e.preventDefault();



        //
        if ($(form_name).valid()){

            //
            disable_btns();



            /*
             * Auth user
             * */
            $(form_name).ajaxSubmit({
                url: app.supadmin_url + "/auth/login",
                beforeSubmit: function(arr){
                },
                success: function(response){
                    //console.log(response); return;
                    //
                    enable_btns();

                    //
                    if ( response && response.id ){
                        location.href = "/adm27/home";
                    }
                    //
                    else if (response.error){
                        $('#username').focus();
                        $(form_name)[0].reset();
                        //
                        app.Toast.fire({ icon: 'error', title: response.error });
                    }
                    else { app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" }); }
                },
                error: function(response){
                    enable_btns();
                    $(form_name)[0].reset();
                    //
                    app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                }
            });


        }

    });






})