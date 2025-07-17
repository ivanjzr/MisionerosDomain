define(function(){
    function moduleReady(modal, section_data){
        //console.log(section_data);

        

        //
        $('#form_section').validate();
        //
        $('#form_section').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_section').valid() ) {

                //
                $(".overlay").show();

                //
                $('#form_section').ajaxSubmit({
                    url: app.admin_url + "/working-hours",
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
                            $("#modal-add-record").find('.modal').modal("hide");

                            setTimeout(function(){
                                //
                                var edit_url = "/admin/working-hours/" + response.id + "/edit";
                                location.href = edit_url;
                                //
                                //$("#grid_working_hours").DataTable().ajax.reload();
                            }, 1000);

                        }
                        //
                        else {
                            //
                            $(".overlay").hide();
                            //
                            let err = (response.error && response.error) ? response.error : "The operation could not be completed. Check your connection or contact the administrator.";
                            app.Toast.fire({ icon: 'error', title: err });
                        }
                    },
                    error: function(response){
                        enable_btns();
                        //
                        $(".overlay").hide();
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });

            }
        });


    

    }
    return {init: moduleReady}
});