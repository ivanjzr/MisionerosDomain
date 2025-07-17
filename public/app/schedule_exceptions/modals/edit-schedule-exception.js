define(function(){
    function moduleReady(modal, section_data){
        console.log(section_data);



        
        //
      $('#fecha').datetimepicker({
            autoclose: true,
            locale: 'es-mx',
            format: 'DD/MM/YYYY',
            stepping: 15,
            icons: { time: 'far fa-clock' }
        });
        $('#fecha_fin').datetimepicker({
            autoclose: true,
            locale: 'es-mx',
            format: 'DD/MM/YYYY',
            stepping: 15,
            icons: { time: 'far fa-clock' }
        });




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
      $('#recurrencia_fecha_limite').datetimepicker({
            autoclose: true,
            locale: 'es-mx',
            format: 'DD/MM/YYYY',
            stepping: 15,
            icons: { time: 'far fa-clock' }
        });




        app.setShowRecurrenciaDuracion = function(recurrencia_tipo){
            //
            $(".recurrencia_duracion").hide();
            $("#recurrencia_tipo_diaria_container").hide();
            //
            if (recurrencia_tipo){
                //
                if (recurrencia_tipo==="diaria"){                    
                    $("#recurrencia_tipo_diaria_container").show();
                }
                $(".recurrencia_duracion").show();
            }
        }

        app.setShowRecurrenciaFechaLimite = function(recurrencia_fecha_limite){
            //
            $(".recurrencia_fecha_limite").hide();
            //
            if (recurrencia_fecha_limite){
                $(".recurrencia_fecha_limite").show();
            }            
        }


        //
        $('#recurrencia_tipo').change(function(e) {
            //
            app.setShowRecurrenciaDuracion($(this).val());
        });


        

            

        //
        $("input[name=recurrencia_duracion]").click(function(){
            //
            app.setShowRecurrenciaFechaLimite(false);
            //
            var recurrencia_duracion = $('input[name=recurrencia_duracion]:checked').val()
            //
            if (recurrencia_duracion==="fecha_limite"){
                app.setShowRecurrenciaFechaLimite(true);
            }
        });



        app.onScheduleExceptionReady = function(data){
            

            //
            $("#motivo").val(data.motivo);
            $("#description").val(data.description);
            $("#recurrencia_tipo").val(data.recurrencia_tipo);
            $("#prioridad").val(data.prioridad);

            //
            if (data.activo){$("#activo").prop("checked", true);}
            //
            if (data.recurrencia_mon){$("#recur_mon").prop("checked", true);}
            if (data.recurrencia_tue){$("#recur_tue").prop("checked", true);}
            if (data.recurrencia_wed){$("#recur_wed").prop("checked", true);}
            if (data.recurrencia_thu){$("#recur_thu").prop("checked", true);}
            if (data.recurrencia_fri){$("#recur_fri").prop("checked", true);}
            if (data.recurrencia_sat){$("#recur_sat").prop("checked", true);}
            if (data.recurrencia_sun){$("#recur_sun").prop("checked", true);}


            //
            $("#recurrencia_tipo_container").hide();
            //
            if (data.excepcion_tipo==="solo_dia" || data.excepcion_tipo==="solo_dia_con_horario"){
                //
                $("#recurrencia_tipo_container").show();
                //
                app.setShowRecurrenciaDuracion(data.recurrencia_tipo);
                app.setShowRecurrenciaFechaLimite(false);
                $("#recurrencia_duracion_ilimitado").prop("checked", true);

                //
                if (data.recurrencia_fecha_limite){
                    //
                    app.setShowRecurrenciaFechaLimite(true);
                    $("#recurrencia_duracion_hasta_fecha_limite").prop("checked", true);
                    $('#recurrencia_fecha_limite').datetimepicker('date', data.recurrencia_fecha_limite.date);
                }
            }

            $("#motivo").focus();

        }





        //
        $('#form_edit_exception').validate();
        //
        $('#form_edit_exception').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_edit_exception').valid() ) {
                //
                $('#form_edit_exception').ajaxSubmit({
                    url: app.admin_url + "/working-hours/schedule-exceptions" + "/" + section_data.id,
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
                            $("#grid_schedule_exceptions").DataTable().ajax.reload();
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
        app.loadScheduleException = function(){
            $.ajax({
                type: 'GET',
                url: app.admin_url + "/working-hours/schedule-exceptions" + "/" + section_data.id,
                beforeSend: function (xhr) {
                    disable_btns();
                },
                success: function(data){
                    enable_btns();
                    //
                    if (data && data.id){
                        app.onScheduleExceptionReady(data);
                    }
                },
                error: function(){
                    enable_btns();
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });
        };



        app.loadScheduleException();
        


    }
    return {init: moduleReady}
});