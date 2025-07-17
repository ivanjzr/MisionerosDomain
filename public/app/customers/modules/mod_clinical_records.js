define(function(){
    function moduleReady(section_data){
        console.log("----Clinical Records data: ", section_data);


        //
        let customer_name = (section_data.name);

        
        //
        app.createSection({
            gridId: "#grid_customer_consultas",
            section_title: "Consultas",
            data: section_data,
            section_title_singular: "Consulta",
            scripts_path: "/app/customers",
            endpoint_url: app.admin_url + "/customers/" + section_data.id + "/clinical-records",
            gridOptions:{
                columns: [
                    {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name": "id", "data" : "id"},
                    {"name" : "revision_date", "data" : function(obj){
                        if (obj.revision_date && obj.revision_date.date) {
                            return moment(obj.revision_date.date).format('DD MMM YYYY hh:mm A');
                        }
                        return '--';
                    }},
                    {"name" : "motivo_consulta", "data" : function(obj){
                        return obj.motivo_consulta ? obj.motivo_consulta.substring(0, 50) + '...' : '--';
                    }},
                    {"name" : "dolor_nivel_1_10", "data" : function(obj){
                        if (obj.dolor_nivel_1_10) {
                            return obj.dolor_nivel_1_10 + '/10';
                        }
                        return '--';
                    }},
                    {"name" : "diagnostico", "data" : function(obj){
                        return obj.diagnostico ? obj.diagnostico.substring(0, 40) + '...' : '--';
                    }},
                    {"name" : "datetime_created", "data" : function(obj){
                        return moment(obj.datetime_created.date).format('DD MMM YYYY hh:mm A');
                    }},
                    {"data": function(obj){
                            //
                            var data_info = JSON.stringify({
                                id: obj.id,
                                customer_id: obj.customer_id,
                                customer_name: customer_name,
                                revision_date: obj.revision_date,
                                motivo_consulta: obj.motivo_consulta,
                                diagnostico: obj.diagnostico,
                                clinical_notes: obj.clinical_notes,
                                // Datos básicos
                                birth_date: obj.birth_date,
                                edad_years: obj.edad_years,
                                peso_kg: obj.peso_kg,
                                estatura_cm: obj.estatura_cm,
                                presion_sistolica: obj.presion_sistolica,
                                presion_diastolica: obj.presion_diastolica,
                                glucosa_mg_dl: obj.glucosa_mg_dl,
                                temperatura_celsius: obj.temperatura_celsius,
                                frecuencia_cardiaca: obj.frecuencia_cardiaca,
                                alergias: obj.alergias,
                                medicamentos_actuales: obj.medicamentos_actuales,
                                antecedentes_familiares: obj.antecedentes_familiares,
                                // Datos dentales
                                dolor_nivel_1_10: obj.dolor_nivel_1_10,
                                sangrado_encias: obj.sangrado_encias,
                                sensibilidad_dental: obj.sensibilidad_dental,
                                mal_aliento: obj.mal_aliento,
                                ortodoncia_previa: obj.ortodoncia_previa,
                                cepillado_diario_veces: obj.cepillado_diario_veces,
                                uso_hilo_dental: obj.uso_hilo_dental,
                                ultima_limpieza_meses: obj.ultima_limpieza_meses,
                                odontograma: obj.odontograma,
                                tratamiento_recomendado: obj.tratamiento_recomendado
                            });
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            str_btns += " <button type='button' class='btn btn-sm btn-outline-secondary btn-editar me-1' data-info='"+data_info+"' title='Editar'><i class='fas fa-edit'></i></button> ";
                            str_btns += " <button type='button' class='btn btn-sm btn-outline-danger btn-eliminar' data-id='"+obj.id+"' title='Eliminar'><i class='fas fa-trash'></i></button>";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                deferLoading: true,
                columnDefs: [
                    { "targets": [0,1,2,3,4,5,6,7],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onEditReady: function(record_data){
                //console.log("Loading patient data:", record_data);
            },
            onGridReady: function(opts){

                //
                $('.btn-editar').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    let record_data = $(this).data("info");
                    //console.log("Edit clinical record:", record_data);
                    //
                    loadModalV2({
                        id: "modal-consulta",
                        modal_size: "xl",
                        data: record_data,
                        html_tmpl_url: "/app/customers/modals/consultas/edit.html?v=" + dynurl(),
                        js_handler_url: "/app/customers/modals/consultas/edit.js?v=" + dynurl(),
                        onBeforeLoad: function(){
                            disable_btns();
                        },
                        onInit: function(){
                            enable_btns();
                        }
                    });

                });

            },
            onSectionReady: function(opts){
            }
        });


        //
        app.noData = "<span class='fw-normal' style='color:orangered;'>sin-dato</span>";
        




        app.setHistoryData = function(data){
            //
            if (data.arr_history && data.arr_history.length){
                
                // Limpiar historiales previos
                $('.field-history-container').remove();
                
                // Definir los campos que queremos mostrar historial
                const fieldsToShow = [
                    'peso_kg', 'estatura_cm', 'temperatura_celsius', 'frecuencia_cardiaca',
                    'presion_sistolica', 'presion_diastolica', 'glucosa_mg_dl',
                    'alergias', 'medicamentos_actuales', 'antecedentes_familiares',
                    'motivo_consulta', 'dolor_nivel_1_10', 'diagnostico', 'tratamiento_recomendado',
                    'sangrado_encias', 'sensibilidad_dental', 'mal_aliento', 'uso_hilo_dental'
                ];
                
                // Preparar arrays para almacenar los valores históricos
                let fieldHistory = {};
                fieldsToShow.forEach(field => {
                    fieldHistory[field] = [];
                });
                
                // Recopilar valores históricos
                $.each(data.arr_history, function(idx, item){
                    fieldsToShow.forEach(field => {
                        let value = item[field];
                        let displayValue = '';
                        
                        // Formatear valores especiales
                        if (field === 'dolor_nivel_1_10' && value) {
                            displayValue = value + '/10';
                        } else if (field === 'sangrado_encias') {
                            displayValue = value ? 'Sangra' : 'No sangra';
                        } else if (field === 'sensibilidad_dental') {
                            displayValue = value ? 'Sensible' : 'Normal';
                        } else if (field === 'mal_aliento') {
                            displayValue = value ? 'Presente' : 'Ausente';
                        } else if (field === 'uso_hilo_dental') {
                            displayValue = value ? 'Usa' : 'No usa';
                        } else if (value !== null && value !== undefined && value !== '') {
                            // Truncar texto largo
                            displayValue = value.toString().length > 50 ? 
                                value.toString().substring(0, 50) + '...' : value.toString();
                        } else {
                            displayValue = app.noData;
                        }
                        
                        fieldHistory[field].push({
                            value: displayValue,
                            date: moment(item.revision_date.date).format('DD/MMM/YY')
                        });
                    });
                });
                
                // Mostrar historial debajo de cada campo
                fieldsToShow.forEach(field => {
                    if (fieldHistory[field].length > 0) {
                        let targetElement = $('#basic_' + field).length ? 
                            $('#basic_' + field) : $('#dental_' + field);
                            
                            // 
                        if (targetElement.length) {
                            let fieldId = 'history_' + field;
                            let historyHtml = `
                                <div class="field-history-container mt-2">
                                    <div class="d-flex align-items-center cursor-pointer" data-bs-toggle="collapse" data-bs-target="#${fieldId}" aria-expanded="false">
                                        <small class="text-primary text-decoration-underline">Historial</small>&nbsp;<span style='font-size:12px;color:dodgerblue;'>(<i class="fas fa-minus mx-1 collapse-icon"></i>)</span>
                                    </div>
                                    <div class="collapse show"" id="${fieldId}">
                                        <div class="">
                            `;
                            
                    fieldHistory[field].forEach((item) => {
                        historyHtml += `
                            <span class="badge bg-white text-dark border me-1 mb-1">
                                <small class="fw-normal text-primary"><span class="fw-bold">${item.date}:</span> ${item.value}</small>
                            </span>
                        `;
                    });
                            
                            historyHtml += `
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            targetElement.after(historyHtml);
                        }
                    }
                });
                
                // Manejar el cambio de icono al expandir/colapsar
                $(document).on('show.bs.collapse', '.collapse', function() {
                    $(this).prev().find('.collapse-icon').removeClass('fa-minus').addClass('fa-plus');
                });
                
                $(document).on('hide.bs.collapse', '.collapse', function() {
                    $(this).prev().find('.collapse-icon').removeClass('fa-plus').addClass('fa-minus');
                });
            }
        }


        
        //
        app.onLastClinicalRecordDataReady = function(data) {

                
            // Cargar información del paciente en footer
            $('#registro_folio').html(data.id);
            $('#registro_fecha_creacion').html(moment(data.datetime_created).format('DD MMM YYYY HH:mm'));

            
            // Datos básicos
            $('#basic_peso_kg').html(data.peso_kg || app.noData);
            $('#basic_estatura_cm').html(data.estatura_cm || app.noData);
            $('#basic_presion_sistolica').html(data.presion_sistolica || app.noData);
            $('#basic_presion_diastolica').html(data.presion_diastolica || app.noData);
            $('#basic_glucosa_mg_dl').html(data.glucosa_mg_dl || app.noData);
            $('#basic_temperatura_celsius').html(data.temperatura_celsius || app.noData);
            $('#basic_frecuencia_cardiaca').html(data.frecuencia_cardiaca || app.noData);
            $('#basic_alergias').html(data.alergias || app.noData);
            $('#basic_medicamentos_actuales').html(data.medicamentos_actuales || app.noData);
            $('#basic_antecedentes_familiares').html(data.antecedentes_familiares || app.noData);

            // Datos dentales
            $('#dental_motivo_consulta').html(data.motivo_consulta || app.noData);
            $('#dental_dolor_nivel_1_10').html(data.dolor_nivel_1_10 ? data.dolor_nivel_1_10 + '/10' : app.noData);
            $('#dental_cepillado_diario_veces').html(data.cepillado_diario_veces ? data.cepillado_diario_veces + ' veces' : app.noData);
            
            $('#dental_sangrado_encias').html(
                data.sangrado_encias ? 
                '<span class="badge text-success border border-success">Sí</span>' : 
                '<span class="badge text-warning border border-warning">No</span>'
            );

            $('#dental_sensibilidad_dental').html(
                data.sensibilidad_dental ? 
                '<span class="badge text-success border border-success">Sí</span>' : 
                '<span class="badge text-warning border border-warning">No</span>'
            );

            $('#dental_mal_aliento').html(
                data.mal_aliento ? 
                '<span class="badge text-success border border-success">Sí</span>' : 
                '<span class="badge text-warning border border-warning">No</span>'
            );

            $('#dental_uso_hilo_dental').html(
                data.uso_hilo_dental ? 
                '<span class="badge text-success border border-success">Sí</span>' : 
                '<span class="badge text-warning border border-warning">No</span>'
            );

            $('#dental_diagnostico').html(data.diagnostico || app.noData);
            $('#dental_tratamiento_recomendado').html(data.tratamiento_recomendado || app.noData);

            // Fechas
            $('#clinical_revision_date').html(moment(data.revision_date.date).format('DD MMM YYYY hh:mm A'));
            $('#registro_fecha_creacion').html(moment(data.datetime_created.date).format('DD MMM YYYY hh:mm A'));
        }



        

        //
        $('#btnAddConsulta').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();            
            //
            loadModalV2({
                id: "modal-consulta",
                modal_size: "xl",
                data: section_data,
                html_tmpl_url: "/app/customers/modals/consultas/add.html?v=" + dynurl(),
                js_handler_url: "/app/customers/modals/consultas/add.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){
                    enable_btns();
                }
            });
        });




        //
        $('#btnSendExpediente').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            var str_last_rec = "";
            var str_customer_name = customer_name;
            //
            let person_data = $("#customer_person_id option:selected").data("info");
            //console.log(person_data)
            if (person_data && person_data.id){
                str_last_rec = "/" + person_data.id;
                str_customer_name = person_data.name;
            }

            //
            if (confirm("Enviar expediente clinico por correo a " + str_customer_name + "?")){
                //
                disable_btns();
                preload(".section-preloader, .overlay", true);
                //
                $.ajax({
                    type: "POST",
                    url: app.admin_url + "/customers/" + section_data.id + "/clinical-records/send-last-expediente" + str_last_rec,
                    dataType: "json",
                    data: JSON.stringify({}),
                    contentType: "application/json",
                    timeout: 10000,
                    success: function(data) {
                        if (data && data.success){
                            app.Toast.fire({ icon: 'success', title: "El expediente ha sido procesado y enviado al correo electrónico registrado. Indica al cliente que revise su bandeja de entrada y carpeta de spam" });
                        } else {
                            const err = (data.error) ? data.error : "Error al intentar enviar el correo";
                            app.Toast.fire({ icon: 'error', title: err });
                        }
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay");
                    },
                    error: function(xhr, status, error) {
                        console.error("Error: ", error);
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay");
                    }
                });
            }


        });




        //
        $('#btnPrintExpediente').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            var str_last_rec = "";
            let person_data = $("#customer_person_id option:selected").data("info");
            //console.log(person_data)
            if (person_data && person_data.id){
                str_last_rec = "/" + person_data.id;
            }

            //
            window.open(app.admin_url + "/customers/" + section_data.id + "/clinical-records/print-last-expediente" + str_last_rec, '_blank');
        });



        //
        app.loadLastClinicalRecord = function(){

            //
            $('.field-history-container').remove();
            $('#basic_info_container').hide();
            $('#dental_info_container').hide();
            $('#no_info_container').show()
            //
            $('.reset-item').html('');

            //
            var str_last_rec = "";
            let person_data = $("#customer_person_id option:selected").data("info");
            //console.log(" ***Person Data: ", person_data)
            let birth_date = "";

            //
            if (person_data && person_data.id){                
                //
                str_last_rec = "/" + person_data.id;
                birth_date = (person_data.birth_date && person_data.birth_date.date) ? moment(person_data.birth_date.date).format("DD MMM YYYY") + " / " + person_data.edad_years : "--";
                $('#patient_name').html(person_data.name + " / " + person_data.relative_type);

            } else {
                //
                birth_date = (section_data.birth_date && section_data.birth_date.date) ? moment(section_data.birth_date.date).format("DD MMM YYYY")  + " / " + section_data.edad_years : "--";
                $('#patient_name').html(customer_name);
            }


            //            
            $('#patient_email').html(section_data.email);
            $('#patient_phone').html(section_data.phone_number);
            $('#patient_birth_date').html(birth_date);
            


            
            //
            $.ajax({
                type: 'GET',
                url: app.admin_url + "/customers/" + section_data.id + "/clinical-records/last" + str_last_rec,
                success: function(data){
                    if (data && data.id){
                        //
                        app.onLastClinicalRecordDataReady(data);
                        app.setHistoryData(data);
                        //
                        $('#basic_info_container').show();
                        $('#dental_info_container').show();
                        $('#no_info_container').hide();
                    }
                },
                error: function(){
                    app.Toast.fire({ 
                        icon: 'error', 
                        title: "Error de conexión. Verifica tu conexión e intenta nuevamente." 
                    });
                }
            });
        }


        //
        app.filterClinicalRecordsGrid = function(){

            //
            var str_last_rec = "";
            let person_data = $("#customer_person_id option:selected").data("info");
            //console.log(person_data)
            if (person_data && person_data.id){
                str_last_rec = "/rel/" + person_data.id;
            }

            //var filter_sale_type_id = $("#filter_sale_type_id").val();
            $("#grid_customer_consultas").DataTable().ajax.url(app.admin_url + "/customers/" + section_data.id + "/clinical-records" + str_last_rec);
            $("#grid_customer_consultas").DataTable().ajax.reload();
        }

   

        //
        $('#btnReloadConsulta').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //
            $("#grid_customer_consultas").DataTable().ajax.reload();
        });



        //
        $('#btnReloadDetails').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //            
            app.loadLastClinicalRecord();
            app.filterClinicalRecordsGrid();
        });
    


        


        //
        selectLoad2({
            id: "#customer_person_id",
            url: app.admin_url + "/customers/" + section_data.id + "/relatives/list",
            parseFields: function(item){
                return item.name + " ( " + item.relative_type + " )";
            },
            emptyOptionText: customer_name, 
            enable: true,
            saveValue: true,
            onChange: function(value) {
                //console.log('onChange:', value);
                //
                app.loadLastClinicalRecord();
                app.filterClinicalRecordsGrid();
            },
            onReady: function(value, items) {
                //console.log('onReady:', value, items);
                //
                app.loadLastClinicalRecord();
                app.filterClinicalRecordsGrid();
            }
        });



    }
    return {init: moduleReady}
});