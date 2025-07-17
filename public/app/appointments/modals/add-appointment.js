define(function(){
    
    // ========================================
    // VARIABLES Y UTILIDADES
    // ========================================
    
    /**
     * Calcula la hora de finalización basada en fecha de inicio y duración
     * @param {string} startDatetime - Fecha/hora de inicio en formato SQL
     * @param {number} durationMinutes - Duración en minutos
     * @returns {string} - Hora de finalización formateada
     */
    function calculateEndTime(startDatetime, durationMinutes) {
        const startDate = new Date(startDatetime);
        const endDate = new Date(startDate.getTime() + (durationMinutes * 60000));
        
        const options = {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        };
        
        return endDate.toLocaleTimeString('es-US', options);
    }

    /**
     * Actualiza la información de duración del servicio
     * @param {object} serviceData - Datos del servicio seleccionado
     */
    function updateServiceDurationInfo(serviceData) {
        const startDatetime = $("#start_datetime").val();
        
        if (serviceData && startDatetime && serviceData.servicio_duracion_minutos) {
            const endTime = calculateEndTime(startDatetime, serviceData.servicio_duracion_minutos);
            
            $("#service_duration_info").html(
                "Finalización estimada: <strong>" + endTime + "</strong> " +
                "(duración <strong>" + serviceData.servicio_duracion_minutos + " mins</strong>)"
            );
            
            $("#service_duration_container").slideDown();
        } else {
            $("#service_duration_container").slideUp();
        }
    }

    // ========================================
    // FUNCIÓN PRINCIPAL DEL MÓDULO
    // ========================================
    
    function moduleReady(modal, data) {
        console.log('Appointment Modal Data:', data);
        
        // ========================================
        // INICIALIZACIÓN DE LA INTERFAZ
        // ========================================
        
        // Configurar título del modal
        $("#modal-appmt-title").html(
            "Cita <strong>" + data.resource.title + "</strong> " +
            "<small style='color:gray;'>" + data.location.name + "</small>"
        );

        // Configurar información de la reserva
        $("#reservar_info").text(
            "Reservar el día " + data.start_date.str_day + " del " + 
            data.start_date.str_date + " a las " + data.start_date.str_time
        );
        
        // Establecer fecha/hora de inicio
        $("#start_datetime").val(data.start_date.sql_datetime);

        // ========================================
        // GESTIÓN DE SELECCIÓN DE CLIENTE
        // ========================================
        
        /**
         * Función que se ejecuta cuando se selecciona un cliente
         * @param {string} sel_customer_id - ID del cliente seleccionado
         * @param {object} customer_data - Datos del cliente
         */
        app.onSelectCustomer = function(sel_customer_id, customer_data) {
            
            // Configurar evento para recargar lista de familiares
            $("#form_add_reservar").off("reloadRelativesList").on("reloadRelativesList", function(){
                if (app.relatives_list) {
                    app.relatives_list.reload();
                }
            });

            // Configurar botón de recarga de familiares
            $('.btnReloadCustomerPeople').off("click").on("click", function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $("#form_add_reservar").trigger("reloadRelativesList");
            });

            // Configurar botón para agregar familiar
            $('.btnAddCustomerPeople').off("click").on("click", function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                loadModalV2({
                    id: "modal-relative",
                    modal_size: "md",
                    data: customer_data,
                    html_tmpl_url: "/app/customers/modals/relatives/add.html?v=" + dynurl(),
                    js_handler_url: "/app/customers/modals/relatives/add.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });
            });

            // Inicializar selector de familiares
            app.relatives_list = selectLoad2({
                id: "#customer_person_id",
                url: app.admin_url + "/customers/" + customer_data.id + "/relatives/list",
                parseFields: function(item){
                    return item.name + " ( " + item.relative_type + " )";
                },
                emptyOptionText: "--Para Paciente",
                enable: true,
                onChange: function(value) {
                    console.log('Familiar seleccionado:', value);
                }
            });

            // Mostrar contenedor de selección de familiares
            $("#sel_customer_people_container").show();
        };

        // ========================================
        // CONFIGURACIÓN DE BÚSQUEDA DE CLIENTES
        // ========================================
        
        $.S2Ext({
            S2ContainerId: "customer_id",
            placeholder: "...buscar cliente",
            language: {
                noResults: function(){ return ""; },
                searching: function(){ return ""; }
            },
            dropdownParent: $('#modal-add-record .modal'),
            allowClear: true,
            minimumInputLength: 2,
            minimumResultsForSearch: "-1",
            remote: {
                qs: function(){
                    return {};
                },
                url: app.admin_url + "/customers/search",
                dataType: 'json',
                delay: 250,
                processResults: function (response, page) {
                    return {
                        results: response
                    };
                },
                cache: false,
                templateResult: app.templateResultContact,
                templateSelection: app.templateSelectionContact,
            },
            onChanged: function(sel_id, data){
                console.log('Cliente seleccionado:', sel_id, data);
                app.onSelectCustomer(sel_id, data);
            },
            onClose: function(){
                $("#sel_customer_people_container").hide();
            }
        });

        // ========================================
        // CONFIGURACIÓN DE SERVICIOS
        // ========================================
        
        app.services_list = selectLoad2({
            id: "#resource_service_id",
            url: app.admin_url + "/appointments/resources/" + data.resource.id + "/services/list",
            parseFields: function(item){
                return item.service_name + " (" + item.servicio_duracion_minutos + " mins)";
            },
            prependEmptyOption: true,
            emptyOptionText: "--seleccionar",
            nameOnNoRecords: "--sin-registros",
            enable: true,
            onChange: function(value) {
                const sel_res_service = $("#resource_service_id").find("option:selected").data("info");
                //console.log('Servicio seleccionado onChange:', sel_res_service);
                
                // Actualizar información de duración
                if (value && sel_res_service) {
                    updateServiceDurationInfo(sel_res_service);
                } else {
                    $("#service_duration_container").slideUp();
                }
            },
            onReady: function(value, items) {
                const sel_res_service = $("#resource_service_id").find("option:selected").data("info");
                //console.log('Servicio seleccionado onReady:', sel_res_service, items.length);
                
                // Si ya hay un servicio seleccionado al cargar
                if (value && sel_res_service) {
                    updateServiceDurationInfo(sel_res_service);
                }
            }
        });

        // ========================================
        // GESTIÓN DE BOTONES
        // ========================================
        
        // Botón para agregar nuevo cliente
        $('.btnAddCustomer').off("click").on("click", function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //            
            loadModalV2({
                id: "modal-add-customer",
                modal_size: "md",
                data: {},
                html_tmpl_url: "/app/appointments/modals/add-customer.html?v=" + dynurl(),
                js_handler_url: "/app/appointments/modals/add-customer.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){
                    enable_btns();
                }
            });
        });

        // ========================================
        // CONFIGURACIÓN DEL FORMULARIO
        // ========================================
        
        // Inicializar validación
        $('#form_add_reservar').validate();
        
        // Manejar envío del formulario
        $('#form_add_reservar').off("submit").on("submit", function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if ( $('#form_add_reservar').valid() && confirm("Reservar Cita?")) {
                
                $('#form_add_reservar').ajaxSubmit({
                    url: app.admin_url + "/appointments/" + data.location.id + "/resources/" + data.resource.id + "/add",
                    beforeSubmit: function(arr){
                        disable_btns();
                        console.log('Enviando datos de la cita:', arr);
                    },
                    success: function(response){
                        enable_btns();
                        
                        if (response && response.id) {
                            app.Toast.fire({ 
                                icon: 'success', 
                                title: "Cita agendada correctamente" 
                            });
                            
                            $("#modal-add-record").find('.modal').modal("hide");
                            
                            // Recargar eventos del calendario
                            if (app.loadLocationResourcesEvents) {
                                app.loadLocationResourcesEvents();
                            }
                        }
                        else if (response.error) {
                            app.Toast.fire({ 
                                icon: 'error', 
                                title: response.error
                            });
                        }
                        else {
                            app.Toast.fire({ 
                                icon: 'error', 
                                title: "The operation could not be completed. Check your connection or contact the administrator." 
                            });
                        }
                    },
                    error: function(response){
                        enable_btns();
                        console.error('Error al enviar formulario:', response);
                        
                        app.Toast.fire({ 
                            icon: 'error', 
                            title: "The operation could not be completed. Check your connection or contact the administrator." 
                        });
                    }
                });
            }
        });

        console.log('Appointment modal initialized successfully');
    }

    // ========================================
    // RETORNO DEL MÓDULO
    // ========================================
    
    return {
        init: moduleReady
    };
});