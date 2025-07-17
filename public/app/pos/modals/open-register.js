define(function(){
    function moduleReady(modal, s_data){
        console.log(s_data);
    
        
        
        //
        $("#open-register-title").text("Apertura de Caja #" + s_data.pos_id);
        //
        $("#opening_balance")
            .focus()
            .select();


        //
        $('#form_open_register').validate();
        //
        $('#form_open_register').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_open_register').valid() ) {
                //
                $('#form_open_register').ajaxSubmit({
                    url: app.admin_url + "/pos/main/open-register",
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
                        }
                    },
                    success: function(response){
                        //
                        enable_btns();
                        //
                        if (response && response.id){
                            //
                            $("#modal-open-register").find('.modal').modal("hide");
                            location.href = "/admin/pos/main";
                        } else {
                            let err = (response.error) ? response.error : "The operation could not be completed. Check your connection or contact the administrator."
                            app.Toast.fire({ icon: 'error', title: err });
                            //
                            $("#opening_balance")
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
        $("#opening_balance, #opening_balance_usd").on("focus", function(){
            $(this).select();
        });


    }
    return {init: moduleReady}
});