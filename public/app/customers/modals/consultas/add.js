define(function(){
    function moduleReady(modal, customer_data){
        console.log("Add consulta modal:", customer_data);
    


        //
        let person_data = $("#customer_person_id option:selected").data("info");
        //console.log(person_data)
        //
        if (person_data && person_data.id){
            $("#modal-title").html("Nueva Consulta para " + person_data.person_name + " (" + person_data.relative_type + ")");

        } else {
            $("#modal-title").html("Nueva Consulta para " + customer_data.customer_name);
        }            

        

        //
        var defaultRevisionDate = moment();
        //
        $('#revision_date').datetimepicker({
            format: 'DD/MM/YYYY hh:mm A',
            locale: 'es-mx',
            date: defaultRevisionDate,
            buttons: {
                showToday: false,
                showClear: false,
                showClose: false
            },
            icons: app.tdBs5Icons,
            viewMode: 'days',
            pickTime: true,
            stepping: 15,
            sideBySide: true,
            calendarWeeks: false,
            showTodayButton: true
        });




        //
        $('#form_add_record').validate({
            rules: {
                revision_date: {
                    required: true
                },
                birth_date: {
                    required: true
                },
                motivo_consulta: {
                    required: true,
                    minlength: 5
                },
                dolor_nivel_1_10: {
                    range: [1, 10]
                },
                peso_kg: {
                    range: [0, 999.99]
                },
                estatura_cm: {
                    range: [0, 999.99]
                },
                temperatura_celsius: {
                    range: [30, 45]
                },
                cepillado_diario_veces: {
                    range: [0, 10]
                }
            },
            messages: {
                revision_date: {
                    required: "La fecha de revisión es obligatoria"
                },
                birth_date: {
                    required: "La fecha de nacimiento es obligatoria"
                },
                motivo_consulta: {
                    required: "El motivo de consulta es obligatorio",
                    minlength: "Debe tener al menos 5 caracteres"
                },
                dolor_nivel_1_10: {
                    range: "El nivel de dolor debe estar entre 1 y 10"
                },
                peso_kg: {
                    range: "Peso inválido"
                },
                estatura_cm: {
                    range: "Estatura inválida"
                },
                temperatura_celsius: {
                    range: "La temperatura debe estar entre 30 y 45 grados"
                }
            }
        });

        //
        $('#form_add_record').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_add_record').valid() ) {
                //
                $('#form_add_record').ajaxSubmit({
                    url: app.admin_url + "/customers/" + customer_data.id + "/clinical-records",
                    beforeSubmit: function(arr){
                        disable_btns();

                        //
                        arr.push({
                            name: "revision_date",
                            value: $('#revision_date').datetimepicker('date').format('YYYY-MM-DD HH:mm')
                        });

                        

                        //
                        if (person_data && person_data.id){
                            //
                            arr.push({
                                name: "customer_relative_id",
                                value: person_data.id
                            });
                        }

                    },
                    success: function(response){
                        //
                        enable_btns();
                        //
                        if (response && response.id){
                            //
                            app.Toast.fire({ 
                                icon: 'success', 
                                title: "Consulta agregada correctamente" 
                            });

                            //
                            app.loadLastClinicalRecord();
                            app.filterClinicalRecordsGrid();
                            //
                            app.goToTab("#tab-detalles");
                            
                            // Limpiar formulario
                            clearForm();
                            //
                            $("#modal-consulta").find('.modal').modal("hide");
                        }
                        //
                        else if (response.error){
                            app.Toast.fire({ 
                                icon: 'error', 
                                title: response.error
                            });
                        }
                        //
                        else {
                            app.Toast.fire({ 
                                icon: 'error', 
                                title: "The operation could not be completed. Check your connection or contact the administrator." 
                            });
                        }
                    },
                    error: function(response){
                        enable_btns();
                        //
                        app.Toast.fire({ 
                            icon: 'error', 
                            title: "The operation could not be completed. Check your connection or contact the administrator." 
                        });
                    }
                });

            }
        });

        function clearForm() {
            $('#form_add_record')[0].reset();
            
            // Restablecer fecha actual
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            $('#revision_date').val(now.toISOString().slice(0, 16));
        }

        // def focus
        $("#motivo_consulta").focus();

    }
    return {init: moduleReady}
});