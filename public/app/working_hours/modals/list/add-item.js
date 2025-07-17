define(function(){
    function moduleReady(modal, section_data){
        console.log(section_data);



        
        //
        $('#hora_inicio').datetimepicker({
            autoclose: true,
            locale: 'es-mx',
            format: 'hh:mm A',
            stepping: 15,
            icons: { time: 'far fa-clock' }
        });
        $('#hora_fin').datetimepicker({
            autoclose: true,
            locale: 'es-mx',
            format: 'hh:mm A',
            stepping: 15,
            icons: { time: 'far fa-clock' }
        });

        

        

        //
        $('#form_working_hours').validate();
        //
        $('#form_working_hours').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_working_hours').valid() ) {
                //
                $('#form_working_hours').ajaxSubmit({
                    url: app.admin_url + "/working-hours/" + record_id + "/items",
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
                            $("#grid_working_hours_list").DataTable().ajax.reload();
                            $("#modal-add-record").find('.modal').modal("hide");
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


    

        //
        $("#name").focus();
        $("#activo").prop("checked", true);


    }
    return {init: moduleReady}
});