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

        

        


        app.onWorkingHoursReady = function(data){
            

            //
            $("#name").val(data.name);
            $("#week_day").val(data.week_day);

            //
            $('#hora_inicio').datetimepicker('date', data.hora_inicio.date);
            $('#hora_fin').datetimepicker('date', data.hora_fin.date);
            
            //
            if (data.activo){$("#activo").prop("checked", true);}

            //
            $("#name").focus();
        }





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
                    url: app.admin_url + "/working-hours/" + record_id + "/items/" + section_data.id,
                    beforeSubmit: function(arr){
                        disable_btns();
                    },
                    success: function(response){
                        //
                        enable_btns();
                        //
                        if (response && response.id){
                            //
                            app.Toast.fire({ icon: 'success', title: "Registro Editado Correctamente" });
                            //
                            $("#grid_working_hours_list").DataTable().ajax.reload();
                            $("#modal-edit-record").find('.modal').modal("hide");
                            
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




      



         // Cargar los horarios
        app.loadWorkingHoursItemsData = function(){
            $.ajax({
                type: 'GET',
                url: app.admin_url + "/working-hours/" + record_id + "/items/" + section_data.id,
                beforeSend: function (xhr) {
                    disable_btns();
                },
                success: function(data){
                    enable_btns();
                    //
                    if (data && data.id){
                        app.onWorkingHoursReady(data);
                    }
                },
                error: function(){
                    enable_btns();
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });
        };



        app.loadWorkingHoursItemsData();



        //
        $("#hora_inicio").prop("disabled", true);
        $("#hora_fin").prop("disabled", true);
        


    }
    return {init: moduleReady}
});