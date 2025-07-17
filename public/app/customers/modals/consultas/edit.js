define(function(){
    function moduleReady(modal, record_data){
        console.log("Edit consulta modal:", record_data);
    


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
        var defaultBirthDate = moment().subtract(35, 'years');
        //
        $('#birth_date').datetimepicker({
            format: 'DD/MM/YYYY',
            locale: 'es-mx',
            date: defaultBirthDate,
            buttons: {
                showToday: false,
                showClear: false,
                showClose: false
            },
            icons: app.tdBs5Icons,
            viewMode: 'years', 
             minViewMode: 'days',
            pickTime: false,
            calendarWeeks: false,
            showTodayButton: false
        });




        //
        if (record_data && record_data.customer_relative_id){
        }
        

        //
        $("#modal-title").html("Editar Consulta de " + record_data.customer_name);


        // Cargar datos del registro
        loadRecordData(record_data);

        //
        $('#form_edit_record').validate({
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
        $('#form_edit_record').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_edit_record').valid() ) {
                //
                $('#form_edit_record').ajaxSubmit({
                    url: app.admin_url + "/customers/" + record_data.customer_id + "/clinical-records/" + record_data.id,
                    beforeSubmit: function(arr){
                        disable_btns();

                        //
                        arr.push({
                            name: "revision_date",
                            value: $('#revision_date').datetimepicker('date').format('YYYY-MM-DD HH:mm')
                        });
                        //
                        arr.push({
                            name: "birth_date",
                            value: $('#birth_date').datetimepicker('date').format('YYYY-MM-DD')
                        });

                    },
                    success: function(response){
                        //
                        enable_btns();
                        //
                        if (response && response.id){
                            //
                            app.Toast.fire({ 
                                icon: 'success', 
                                title: "Consulta actualizada correctamente" 
                            });

                            //
                            app.loadLastClinicalRecord();
                            app.filterClinicalRecordsGrid();
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

        function loadRecordData(data) {
            // Información básica de la consulta            
            $('#motivo_consulta').val(data.motivo_consulta || '');
            $('#notes').val(data.clinical_notes || '');

            // Información básica médica
            $('#peso_kg').val(data.peso_kg || '');
            $('#estatura_cm').val(data.estatura_cm || '');
            $('#presion_sistolica').val(data.presion_sistolica || '');
            $('#presion_diastolica').val(data.presion_diastolica || '');
            $('#glucosa_mg_dl').val(data.glucosa_mg_dl || '');
            $('#temperatura_celsius').val(data.temperatura_celsius || '');
            $('#frecuencia_cardiaca').val(data.frecuencia_cardiaca || '');
            $('#alergias').val(data.alergias || '');
            $('#medicamentos_actuales').val(data.medicamentos_actuales || '');
            $('#antecedentes_familiares').val(data.antecedentes_familiares || '');

            // Información dental
            $('#dolor_nivel_1_10').val(data.dolor_nivel_1_10 || '');
            $('#cepillado_diario_veces').val(data.cepillado_diario_veces || '');
            $('#ultima_limpieza_meses').val(data.ultima_limpieza_meses || '');
            
            // Checkboxes
            $('#sangrado_encias').prop('checked', data.sangrado_encias == 1);
            $('#sensibilidad_dental').prop('checked', data.sensibilidad_dental == 1);
            $('#mal_aliento').prop('checked', data.mal_aliento == 1);
            $('#uso_hilo_dental').prop('checked', data.uso_hilo_dental == 1);
            $('#ortodoncia_previa').prop('checked', data.ortodoncia_previa == 1);

            $('#diagnostico').val(data.diagnostico || '');
            $('#tratamiento_recomendado').val(data.tratamiento_recomendado || '');
            $('#odontograma').val(data.odontograma || '');
            
            //
            const revision_date = moment(data.revision_date.date).format('DD/MM/YYYY hh:mm A');
            $('#revision_date').datetimepicker('date', revision_date);
            

            // Información del registro
            $('#registro_folio').text(data.id || '--');
            $('#registro_fecha_creacion').text(moment(data.clinical_created).format('DD/MM/YYYY HH:mm'));
        }

        // def focus
        $("#motivo_consulta").focus();



        

    }
    return {init: moduleReady}
});