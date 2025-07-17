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
                url: app.admin_url + "/auth/login",
                beforeSubmit: function(arr){
                    //
                    disable_btns();
                },
                success: function(response){
                    enable_btns();
                    if ( response && response.id ){
                        if (response.login_to_pos){
                            location.href = "/admin/pos/index";
                        } else {
                            location.href = "/admin/home";
                        }
                    }
                    else if (response.error){
                        $('#username').focus();
                        $(form_name)[0].reset();
                        //
                        app.Toast.fire({ icon: 'error', title: response.error });
                    }
                    else { app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." }); }
                },
                error: function(response){
                    enable_btns();
                    $(form_name)[0].reset();
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });


        }

    });






})