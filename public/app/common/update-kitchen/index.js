define(function(){
    function moduleReady(modal, section_data){
        console.log(modal, section_data);

        //
        $('#modal-title').text("Update Kitchen Sale #" + section_data.id);
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Update");



        $('#current_kitchen').html("<strong>Current Kitchen</strong> - " + section_data.kitchen_store);


        //
        loadSelectAjax({
            id: "#kitchen_store_id",
            url: app.admin_url + "/branches/list-kitchens",
            parseFields: function(item){
                return item.name + " - " + item.address;
            },
            saveValue: true,
            prependEmptyOption: true,
            emptyOptionText: "--all",
            enable: true,
            focus: true,
            default_value: (section_data && section_data.kitchen_store_id) ? section_data.kitchen_store_id : null,
            onChange: function(){
                //
            }
        });






        //
        $("#form_section").validate();
        //
        $("#form_section").submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $("#form_section").valid() ) {
                //
                $("#form_section").ajaxSubmit({
                    url: app.admin_url + "/sales/" + section_data.id + "/update-kitchen",
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
                            app.Toast.fire({ icon: 'success', title: "Kitchen updated" });
                            $("#modal-update-status").find('.modal').modal("hide");
                            $("#grid_section").DataTable().ajax.reload();

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




    }
    return {init: moduleReady}
});






