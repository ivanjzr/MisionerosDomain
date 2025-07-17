define(function(){
    function moduleReady(modal, s_data){
        console.log(s_data);
    
        
        
        //
        $("#user-name").text("Autenticar " + s_data.user_name + " para " + s_data.pos_register_title);

        //
        $("#user_pin").focus();


        //
        let url_path = "";
        //
        if (s_data.pos_id){
            url_path = app.admin_url + "/pos/main/validate-user";
        } else if (s_data.pos_register_id){
            url_path = app.admin_url + "/pos/main/update-register-user";
        }
        

        //
        $('#form_set_pin').validate();
        //
        $('#form_set_pin').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_set_pin').valid() ) {
                //
                $('#form_set_pin').ajaxSubmit({
                    url: url_path,
                    beforeSubmit: function(arr){
                        disable_btns();
                        //
                        arr.push({
                            name: "user_id",
                            value: s_data.user_id
                        });
                        //
                        if (s_data.pos_id){
                            arr.push({
                                name: "pos_id",
                                value: s_data.pos_id
                            });
                        } else if (s_data.pos_register_id){
                            arr.push({
                                name: "pos_register_id",
                                value: s_data.pos_register_id
                            });
                        }
                    },
                    success: function(response){
                        //
                        enable_btns();
                        if (response && response.id){
                            //
                            $("#modal-set-pin").find('.modal').modal("hide");
                            //
                            if (s_data.pos_id){
                                app.openRegister(s_data);
                            } else {
                                location.reload();
                            }
                        } else {
                            //
                            let err = (response.error) ? response.error : "The operation could not be completed. Check your connection or contact the administrator."
                            app.Toast.fire({ icon: 'error', title: err });
                            //
                            $("#user_pin")
                                .focus()
                                .select();
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
        $("#user_pin").on("focus", function(){
            $(this).select();
        });



    }
    return {init: moduleReady}
});