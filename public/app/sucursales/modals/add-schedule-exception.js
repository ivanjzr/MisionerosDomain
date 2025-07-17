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






        //
        $('#excepcion_tipo').change(function(e) {
            //
            $(".fecha_fin_container").hide();
            $(".hora_container").hide();
            $(".hora_fin_container").hide();
            $("#recurrencia_tipo_container").hide();
            //
            var this_val = $(this).val();            
            //
            if (this_val==="solo_dia"){
                $("#recurrencia_tipo_container").show();
            }
            //
            else if (this_val==="solo_dia_con_horario"){
                //
                $(".hora_container").show();
                $(".hora_fin_container").show();
                $("#recurrencia_tipo_container").show();
            }
            else if (this_val==="rango_dias"){
                //
                $(".fecha_fin_container").show();
                $("#recurrencia_tipo_container").hide();
            }
            else if (this_val==="rango_dias_con_horario"){
                //
                $(".fecha_fin_container").show();
                //
                $(".hora_container").show();
                $(".hora_fin_container").show();
                $("#recurrencia_tipo_container").hide();
            }
        });




        //
        $('#recurrencia_tipo').change(function(e) {
            //
            $(".recurrencia_duracion").hide();
            $("#recurrencia_tipo_diaria_container").hide();
            //
            var this_val = $(this).val();
            //
            if (this_val){
                //
                if (this_val==="diaria"){                    
                    $("#recurrencia_tipo_diaria_container").show();
                }
                $(".recurrencia_duracion").show();
            }
        });

            

        //
        $("input[name=recurrencia_duracion]").click(function(){
            //
            $(".recurrencia_fecha_limite").hide();
            //
            var recurrencia_duracion = $('input[name=recurrencia_duracion]:checked').val()
            //
            if (recurrencia_duracion==="fecha_limite"){
                $(".recurrencia_fecha_limite").show();
            }
        });





        //
        $('#form_add_exception').validate();
        //
        $('#form_add_exception').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_add_exception').valid() ) {
                //
                $('#form_add_exception').ajaxSubmit({
                    url: section_data.opts.endpoint_url + "/" + section_data.id + "/schedule-exceptions",
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
                            $("#grid_schedule_exceptions").DataTable().ajax.reload();
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





        


    }
    return {init: moduleReady}
});