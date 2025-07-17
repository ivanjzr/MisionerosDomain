define(function(){
    function moduleReady(modal, event_data){
        console.log(event_data);

        app.onEventDataReady = function(data){
            // Formatear fechas en formato AM/PM
            let start_date = moment(data.start_datetime.date).format('DD [de] MMMM [de] YYYY');
            let start_time = moment(data.start_datetime.date).format('h:mm A');
            let end_time = moment(data.end_datetime.date).format('h:mm A');
            let duration = moment(data.end_datetime.date).diff(moment(data.start_datetime.date), 'minutes');
            
            // Título del modal
            $("#modal-appmt-title").text(`${data.customer_name} - ${data.resource_name}`);
            
            // Fecha y hora destacada en formato AM/PM
            $("#appointment_date").text(start_date);
            $("#appointment_time").text(`${start_time} - ${end_time} (${duration} min)`);
            
            // Información detallada
            $("#appointment_reference").text(`CITA-${data.id}`);
            $("#customer_name").text(data.customer_name);
            $("#service_name").text(data.service_name);
            $("#resource_name").text(data.resource_name);
            $("#location_name").text(data.location_name);

            // Venta asociada con estilo mejorado
            if (data.sale_id){
                $("#appmnt_sale_container").show();
                $("#sale_text").text("Venta #" + data.sale_id);
            } else {
                $("#appmnt_sale_container").hide();
            }
            
            // Estado con color de fondo
            $("#status_badge")
                .css('background-color', data.status_color)
                .css('color', 'white')
                .removeClass('bg-secondary'); // Remover clases anteriores
            $("#status_text").text(data.status);
            
            // Notas (solo mostrar si existen)
            if(data.notes && data.notes.trim()) {
                $("#appointment_notes").text(data.notes);
                $("#notes_container").show();
            } else {
                $("#notes_container").hide();
            }
        }
       
        // Propiedades del evento
        let props = event_data.extendedProps;

        // Configurar botón de editar
        $("#btnEditarCita").attr("href", app.admin_url + "/appointments/" + event_data.id + "/edit");

        // Cargar datos completos del evento
        $.ajax({
            type: 'GET',
            url: app.admin_url + "/appointments/" + props.location_id + "/resources/" + props.resource_id  + "/events/" + event_data.id,
            beforeSend: function (xhr) {
                disable_btns();
            },
            success: function(data){
                enable_btns();
                if (data && data.id){
                    app.onEventDataReady(data);
                } else {
                    app.Toast.fire({ 
                        icon: 'error', 
                        title: "No se pudo cargar la información de la cita" 
                    });
                }
            },
            error: function(){
                enable_btns();
                app.Toast.fire({ 
                    icon: 'error', 
                    title: "Error de conexión. Verifica tu conexión e intenta nuevamente." 
                });
            }
        });
    }
    
    return {init: moduleReady}
});