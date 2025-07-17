define(function(){
    function moduleReady(modal, section_data){
        //console.log("------add customer: ", section_data);



        //
        loadSelectAjax({
            id: "#phone_country_id",
            url: app.public_url + "/paises/list",
            parseFields: function(item){
                return "+" + item.phone_cc + " (" + item.abreviado + ")";
            },
            prependEmptyOption: true,
            emptyOptionText: "--select",
            default_value: app.ID_PAIS_EU,
            enable: true
        });

        // def focus
        $("#name").focus();




        //
        $('#form_section').validate();
        //
        $('#form_section').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_section').valid() ) {
                //
                $('#form_section').ajaxSubmit({
                    url: app.admin_url + "/customers/",
                    beforeSubmit: function(arr){
                        disable_btns();
                    },
                    success: function(response){
                        //
                        enable_btns();
                        //
                        if (response && response.id){
                            //
                            app.Toast.fire({ icon: 'success', title: "Record added succesfully" });
                            //
                            $("#modal-add-customer").find('.modal').modal("hide");
                            //
                        }
                        //
                        else if (response.error){
                            app.Toast.fire({ icon: 'error', title: response.error});
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
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


    }
    return {init: moduleReady}
});