$(document).ready(function(){

    app.ventInfo = null;

    // Función para actualizar estado con mensajes explicativos
    app.updateAppointmentStatus = function(new_status, status_title){
        let confirmMsg = "¿Actualizar estado a " + status_title + "?";
        
        // Mensajes específicos para acciones especiales
        if (new_status === app.CITA_STATUS_CANCELLED) {
            confirmMsg = "¿Cancelar esta cita?\n\nEsto liberará el espacio reservado y permitirá que otros puedan agendarse en este horario.";
        } else if (new_status === "reschedule") {
            confirmMsg = "¿Re-agendar esta cita?\n\nSe verificará nuevamente la disponibilidad del horario y se reactivará la cita.";
        }
        
        if (confirm(confirmMsg)){
            block("#section-main", "Actualizando estado...");
            
            $.ajax({
                type: "POST",
                url: app.admin_url + "/appointments/" + app.ventInfo.sucursal_id + "/resources/" + app.ventInfo.resource_id + "/event/" + app.ventInfo.id + "/update-status",
                dataType: "json",
                data: JSON.stringify({
                    new_status
                }),
                contentType: "application/json",
                timeout: 10000,
                success: function(data) {
                    $("#section-main").unblock();
                    if (data.id) {
                        app.Toast.fire({ 
                            icon: 'success', 
                            title: "Estado actualizado correctamente" 
                        });
                        app.loadEventData();
                    } else {
                        let err = (data.error) ? data.error : "Error al actualizar el estado";
                        app.Toast.fire({ icon: 'error', title: err });
                    }
                },
                error: function(xhr, status, error) {
                    $("#section-main").unblock();
                    console.error("Error updating status:", error);
                    app.Toast.fire({ 
                        icon: 'error', 
                        title: "Error de conexión. Intente nuevamente." 
                    });
                }
            });
        }
    }

    // Función para llenar los datos del evento
    app.onEventDataReady = function(data){
        // Formatear fechas y horas
        let start_time = moment(data.start_datetime.date).format('DD/MM/YYYY h:mm A');
        let end_time = moment(data.end_datetime.date).format('h:mm A');
        let date_only = moment(data.start_datetime.date).format('DD/MM/YYYY');
        let time_range = moment(data.start_datetime.date).format('h:mm A') + ' - ' + end_time;
        
        // Header badges
        $("#appointment_reference").text(`CITA-${data.id}`);
        $("#location_badge").text(data.location_name);
        $("#resource_badge").text(data.resource_name);
        $("#time_badge").text(time_range);
        
        // Llenar campos del formulario
        $("#customer_name").val(data.customer_name);
        $("#service_name").val(data.prod_code + " - " + data.service_name + " (" + data.service_category + ")");
        $("#appointment_time").val(`${start_time} - ${end_time}`);
        $("#prod_code").val(data.prod_code);
        $("#service_duration").val(data.servicio_duracion_minutos + " minutos");
        $("#service_price").val(parseFloat(data.precio).toFixed(2));

        // Venta asociada
        if (data.sale_id){
            $("#sale_id").val("Venta #" + data.sale_id);
        } else {
            $("#sale_id").val("Sin venta asociada");
        }
        
        // Familiar (mostrar solo si existe)
        if (data.customer_person_id && data.person_name){
            $("#relative_name_and_type").val(data.person_name + " (" + data.relative_type + ")");
            $("#relative_container").show();
        } else {
            $("#relative_container").hide();
        }
        
        // Notas
        if(data.notes && data.notes.trim()) {
            $("#appointment_notes").val(data.notes);
        } else {
            $("#appointment_notes").val("Sin notas adicionales");
        }

        // Establecer estado actual con color
        setCurrentStatus(data.status_id, data.status, data.status_color);

        // Mostrar/ocultar botones según estado y si está cobrado
        showActionButtons(data);

        // Ocultar preloader
        $("#section-main").unblock();
    }

    // Función para establecer el estado actual
    function setCurrentStatus(status_id, status_name, status_color) {
        $("#current_status_badge").html(`
            <span class="status-badge text-white" style="background-color: ${status_color};">
                <i class="fas fa-circle me-2"></i>${status_name}
            </span>
        `);
    }

    // Función para mostrar botones de acción según estado y si está cobrado
    function showActionButtons(data) {
        // Ocultar todos los botones primero
        $(".action-btn").hide();
        $("#paid_indicator").hide();

        // Si ya está cobrado, mostrar indicador y no permitir cambios
        if (data.sale_id && data.sale_item_id) {
            $("#paid_indicator").show();
            return;
        }

        // Lógica de botones según estado actual
        const statusId = parseInt(data.status_id);
        
        switch(statusId) {
            case app.CITA_STATUS_PENDING: // Pendiente
                $("#btnConfirm").show();
                $("#btnCancel").show();
                $("#btnInProgress").show();
                break;
                
            case app.CITA_STATUS_CONFIRMED: // Confirmada
                $("#btnPending").show();
                $("#btnCancel").show();
                $("#btnInProgress").show();
                break;
                
            case app.CITA_STATUS_IN_PROGRESS: // En progreso
                $("#btnConfirm").show();
                $("#btnCancel").show();
                break;
                
            case app.CITA_STATUS_CANCELLED: // Cancelada
                $("#btnReSchedule").show();
                break;
        }
    }

    // Event handlers para botones de acción
    $("#btnConfirm").click(function(e){
        e.preventDefault();
        app.updateAppointmentStatus(app.CITA_STATUS_CONFIRMED, "Confirmada");
    });
    
    $("#btnCancel").click(function(e){
        e.preventDefault();
        app.updateAppointmentStatus(app.CITA_STATUS_CANCELLED, "Cancelada");
    });
    
    $("#btnPending").click(function(e){
        e.preventDefault();
        app.updateAppointmentStatus(app.CITA_STATUS_PENDING, "Pendiente");
    });
    
    $("#btnInProgress").click(function(e){
        e.preventDefault();
        app.updateAppointmentStatus(app.CITA_STATUS_IN_PROGRESS, "En Progreso");
    });
    
    $("#btnReSchedule").click(function(e){
        e.preventDefault();
        app.updateAppointmentStatus("reschedule", "Re-agendar");
    });

    // Función para cargar datos del evento
    app.loadEventData = function(){
        block("#section-main", "Cargando información...");
        
        $.ajax({
            type: 'GET',
            url: app.admin_url + "/appointments/" + record_id + "/event",
            success: function(data){
                if (data && data.id){
                    app.ventInfo = data;
                    app.onEventDataReady(data);
                } else {
                    $("#section-main").unblock();
                    app.Toast.fire({ 
                        icon: 'error', 
                        title: "No se pudo cargar la información de la cita" 
                    });
                }
            },
            error: function(){
                $("#section-main").unblock();
                app.Toast.fire({ 
                    icon: 'error', 
                    title: "Error de conexión. Verifica tu conexión e intenta nuevamente." 
                });
            }
        });
    };

    // Inicializar - cargar datos del evento
    app.loadEventData();

});