$(document).ready(function(){





    //
    loadSelectAjax({
        id: "#sucursal_id",
        url: app.admin_url + "/user/sucursales",
        parseFields: function(item){
            return item.name + " - " + item.address + ", " + item.ciudad;
        },
        prependEmptyOption: true,
        emptyOptionText: "--select",
        enable: true
    });




    var form_name = '#form_select_sucursal';


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
                url: app.admin_url + "/user/update-sucursal",
                beforeSubmit: function(arr){
                    //
                    disable_btns();
                },
                success: function(response){

                    //
                    enable_btns();

                    //
                    if ( response && response.id ){
                        location.href = "/admin/home";
                    }
                    //
                    else if (response.error){
                        $('#username').focus();
                        $(form_name)[0].reset();
                        //
                        app.Toast.fire({ icon: 'error', title: response.error });
                    }
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