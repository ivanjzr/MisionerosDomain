(function ($) {
    'use strict';



     // Elemento DOM que contendrá el calendario
    var calendarEl = document.getElementById('calendar');
    
    //==============================================
    // VARIABLES DE CONTROL DE ESTADO
    //==============================================
    
    var isLoadingResources = false;
    var isLoadingEvents = false;
    var isNavigating = false; 
    
    
    // Referencia al datepicker
    let $calendarDate = $('#calendar_date');
    
    //==============================================
    // INICIALIZACIÓN DEL CALENDARIO
    //==============================================

    // Crear instancia del calendario con todas las opciones
    var calendar = new FullCalendar.Calendar(calendarEl, {
        schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
        initialView: 'dayGridMonth',
        headerToolbar: false,
        firstDay: 1,
        resources: [],
        height: '100%',
        eventContent: function(arg) {
            //console.log(arg.event)
            //
            let str_title = `<div class="fc-event-time">${moment(arg.event.start).format('h:mm A')} - ${moment(arg.event.end).format('h:mm A')}</div>`;
            if (arg.event.extendedProps && arg.event.extendedProps.hide_time_title){
                str_title = "";
            }
            //
            return {
                html: `
                    <div class="fc-event-main-frame">
                        ${str_title}
                        <div class="fc-event-title-container">
                            <div class="fc-event-title fc-sticky">${arg.event.title} </div>
                        </div>
                    </div>`
            };
        },
        slotMinTime: '00:00:00',
        slotMaxTime: '00:00:00',
        slotDuration: '00:15:00',
        allDaySlot: false,
        //editable: true,
        selectable: true,
        //selectMirror: true,
        dayMaxEvents: true,
        locale: 'es-mx',
        events: [], /* array o json url */
        // Evento al seleccionar un intervalo de tiempo (crear nueva cita)
        select: function(info) {
            console.log("select: ", info);

            //
            if ( isLoadingResources || isLoadingEvents || isNavigating ) {
                console.log("event click loading resources/events");
                return;
            }

            //
            let start_date = app.fc_date(info.start);
            //
            var selected_location = $("#filter_location option:selected");
            var location = (selected_location) ? selected_location.data("info") : null;
            let resource = info.resource;
            //
            //if (confirm("Reservar en " + resource.title + " el " + start_date.str_day + " " + start_date.str_date + " a las " + start_date.str_time + "?")){
                //
                loadModalV2({
                    id: "modal-add-record",
                    modal_size: "lg",
                    data: {
                        start_date,
                        resource,
                        location
                    },
                    /*onHide: function(){},*/
                    html_tmpl_url: "/app/appointments/modals/add-appointment.html?v=" + dynurl(),
                    js_handler_url: "/app/appointments/modals/add-appointment.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        //disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();
                    }
                });
            //}
        },
        // Evento al hacer clic en una cita existente
        eventClick: function(info) {
            console.log("event click: ", info.event);

            //
            if ( isLoadingResources || isLoadingEvents || isNavigating ) {
                console.log("event click loading resources/events");
                return;
            }

            //
            if ( info.event.extendedProps.type === "appmnt" ){
                //
                loadModalV2({
                    id: "modal-view-record",
                    modal_size: "md",
                    data: info.event,
                    /*onHide: function(){},*/
                    html_tmpl_url: "/app/appointments/modals/view-appointment.html?v=" + dynurl(),
                    js_handler_url: "/app/appointments/modals/view-appointment.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();
                    }
                });
            }
           
        },
        // Evento cuando se arrastra una cita a un nuevo horario
        eventDrop: function(info) {
            //console.log(info.event);
            // Aquí harías una petición AJAX para actualizar la cita
            //console.log('Cita movida:', info.event.id, info.event.startStr, info.event.endStr);
        },
        datesSet: function(){

            try {

                //
                app.updateCalendarTitle();
                

                // Liberar el flag de navegación
                isNavigating = false;
                

                // Solo cargar recursos si no hay operaciones en curso
                if ( !isLoadingResources && !isLoadingEvents) {
                    // Delay pequeño para evitar condiciones de carrera
                    setTimeout(() => {
                        if (!isLoadingResources) {
                            app.loadLocationResources();
                        }
                    }, 100);
                } else {
                    console.log("Skipping datesSet - operations in progress");
                }
            } catch (error) {
                console.error('Error in datesSet:', error);
                isNavigating = false;
                enable_btns();
            }

        }
    });
    
    //==============================================
    // INICIALIZACIÓN DEL DATEPICKER
    //==============================================
    
    //
    $('#calendar_date').datetimepicker({
        autoclose: true,
        inline: true,
        sideBySide: true,
        format: 'DD/MM/YYYY',
        stepping: 15,
        icons: { time: 'far fa-clock' },
        daysOfWeekDisabled: [],
        locale: 'es-mx' 
    });
    
    //==============================================
    // FUNCIONES UTILITARIAS
    //==============================================
    
    // Habilita sólo domingos en el datepicker
    function enableWeekDays() {
        $calendarDate.datetimepicker('daysOfWeekDisabled', [0, 2, 3, 4, 5, 6, 7]);
    }
    
    // Habilita todos los días en el datepicker
    function enableAllDays() {
        $calendarDate.datetimepicker('daysOfWeekDisabled', []);
    }
    
    
    
    //==============================================
    // EVENTOS Y LISTENERS
    //==============================================
    
    $("#prev-btn").click(function() {
        if (isNavigating || isLoadingResources || isLoadingEvents) {
            console.log("Navigation blocked - operation in progress");
            return;
        }
        
        isNavigating = true;
        disable_btns();
        
        try {
            calendar.prev();
        } catch (error) {
            console.error('Error navigating prev:', error);
            enable_btns();
            isNavigating = false;
        }
    });
    
    $("#next-btn").click(function() {
        if (isNavigating || isLoadingResources || isLoadingEvents) {
            console.log("Navigation blocked - operation in progress");
            return;
        }
        
        isNavigating = true;
        disable_btns();
        
        try {
            calendar.next();
        } catch (error) {
            console.error('Error navigating next:', error);
            enable_btns();
            isNavigating = false;
        }
    });
    
    $("#today-btn").click(function() {
        if (isNavigating || isLoadingResources || isLoadingEvents) {
            console.log("Navigation blocked - operation in progress");
            return;
        }
        
        isNavigating = true;
        disable_btns();
        
        try {
            calendar.today();
        } catch (error) {
            console.error('Error navigating today:', error);
            enable_btns();
            isNavigating = false;
        }
    });
    
    // Evento al cambiar la fecha en el datepicker
    $("#calendar_date").on("change.datetimepicker", function(e) {
        if (e.date) {

            // Actualiza el calendario con la fecha seleccionada
            let selectedDate = e.date.toDate();
            //alert(selectedDate)
            calendar.gotoDate(selectedDate);

        }
    });
    

    app.getCalendarDate = function(){
        try {
            const date = calendar.getDate();
            return date ? moment(date).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD');
        } catch (error) {
            console.error('Error getting calendar date:', error);
            return moment().format('YYYY-MM-DD');
        }
    }


    function validateCalendarData(data) {
        if (!data) return false;
        
        // Validar recursos
        if (data.resources && Array.isArray(data.resources)) {
            for (let resource of data.resources) {
                if (!resource.id || !resource.title) {
                    console.warn('Invalid resource data:', resource);
                    return false;
                }
            }
        }
        
        // Validar horarios
        if (data.slot_min_time && data.slot_max_time) {
            const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/;
            if (!timeRegex.test(data.slot_min_time) || !timeRegex.test(data.slot_max_time)) {
                console.warn('Invalid time format:', data.slot_min_time, data.slot_max_time);
                return false;
            }
        }
        
        return true;
    }
    //
    app.updateCalendarTitle = function(){
        //
        let cal_date = app.getCalendarDate();
        $('#calendar-title').html(calendar.currentData.viewTitle + " (" + cal_date + ")");
    }



    //
    $(".btnReload").click(function() {
        if (isLoadingResources || isLoadingEvents) {
            console.log("Reload blocked - operation in progress");
            return;
        }
        app.loadLocationResourcesEvents();
    });

    

    // Carga los recursos disponibles según la ubicación seleccionada
    app.loadResourcesList = function(calendar_view_type) {

        //
        $("#filter_resource").html("");
        let location_id = $("#filter_location").val();
        
        //
        let select_resources_options = {
            id: "#filter_resource",
            url: app.admin_url + "/appointments/resources/" + location_id + "/list-available",
            parseFields: function(item) {
                return item.name;
            },
            onChange: function() {
                //
                app.updateCalendarTitle();
                app.loadLocationResources();
            },
            onReady: function() {
                //
                app.updateCalendarTitle();
                app.loadLocationResources();                
            },
            prependEmptyOption: true,
            saveValue: true,
            enable: true,
        }

        //
        if ( calendar_view_type === "resourceTimeGridWeek" ){
            select_resources_options.emptyOptionText = "--seleccionar";
        } else if ( calendar_view_type === "resourceTimeGridDay" ){
            select_resources_options.emptyOptionText = "--todos";
        }

        //        
        loadSelectAjax(select_resources_options);

    };

    app.loadLocationResources = function(){
        //
        if (isLoadingResources) {
            console.log("Already loading resources, adding to queue...");
            return;
        }

        //
        block("#calendar-main", "loading dates..");
        disable_btns();
        isLoadingResources = true;

        //        
        try {
            
            //
            let location_id = $("#filter_location").val();

            //
            if (!location_id) {
                console.warn('No location selected');
                isLoadingResources = false;
                enable_btns();
                $("#calendar-main").unblock();
                return;
            }

            calendar.getResources().forEach(resource => resource.remove());
            calendar.setOption('resources', []);
            
            
            
            const resource_id = $("#filter_resource").val();
            
            //
            let path_res_id = "";
            let cal_date = "";
            let cal_type = "";
            
            //
            if (calendar.currentData.currentViewType === "resourceTimeGridWeek"){

                //
                if (!resource_id) {
                    console.warn('No location selected');
                    isLoadingResources = false;
                    enable_btns();
                    $("#calendar-main").unblock();
                    return;
                }

                //
                cal_date = moment(calendar.view.activeStart).format('YYYY-MM-DD');

                //
                cal_type = "week";
                path_res_id = "/" + resource_id;

            } else if (calendar.currentData.currentViewType === "resourceTimeGridDay"){

                //
                if (resource_id) {
                    path_res_id = "/" + resource_id;
                }

                //
                cal_type = "day";
                cal_date = app.getCalendarDate();
            }


            //
            $.ajax({
                type: "POST",
                url: app.admin_url + "/appointments/" + location_id + "/resources" + path_res_id,
                data: {
                    cal_type,
                    cal_date
                },
                timeout: 10000,
                success: function(data) {
                    try {
                        if (!validateCalendarData(data)) {
                            alert('Invalid data received from server, view err/warn logs')
                            console.error('Invalid data received from server');
                            return;
                        }
                        
                        if (data && data.slot_min_time && data.slot_max_time && 
                            data.resources && data.resources.length) {
                            
                            // Aplicar cambios de forma segura
                            calendar.setOption('resources', data.resources);
                            calendar.setOption('slotMinTime', data.slot_min_time);
                            calendar.setOption('slotMaxTime', data.slot_max_time);
                            
                            // Render solo si es necesario
                            calendar.render();
                            
                            // Cargar eventos después de un pequeño delay
                            setTimeout(() => {
                                app.loadLocationResourcesEvents();
                            }, 100);

                        } else {
                            $("#calendar-main").unblock();
                            enable_btns();
                        }

                    } catch (error) {
                        $("#calendar-main").unblock();
                        enable_btns();
                        console.error('Error processing resources:', error);
                    } finally {
                        isLoadingResources = false;                        
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading resources:", error);
                    isLoadingResources = false;
                    $("#calendar-main").unblock();
                    enable_btns();
                }
            });
        } catch (error) {
            console.error('Error in loadLocationResources:', error);
            isLoadingResources = false;
            $("#calendar-main").unblock();
            enable_btns();
        }
    }

    app.loadLocationResourcesEvents = function(){
        if (isLoadingEvents) {
            console.log("Already loading events, skipping...");
            return;
        }

        isLoadingEvents = true;
        disable_btns();
        
        try {

            
            calendar.removeAllEvents();
            calendar.setOption('events', []);
            //calendar.render();
            
            
            //
            let location_id = $("#filter_location").val();
            //            
            if (!location_id) {
                console.warn('No location selected');
                isLoadingEvents = false;
                return;
            }

            
            let resources_ids = calendar.getResources().map(resource => resource.id);
            if (!resources_ids.length){
                console.warn("no resources provided")
                isLoadingEvents = false;
                $("#calendar-main").unblock();
                enable_btns();
                return;
            }


            //
            let cal_date = "";
            let cal_type = "";
            //
            if (calendar.currentData.currentViewType === "resourceTimeGridWeek"){

                //
                const view = calendar.view;
                cal_date = moment(view.activeStart).format('YYYY-MM-DD');
                //
                cal_type = "week";

            } else if (calendar.currentData.currentViewType === "resourceTimeGridDay"){

                //
                cal_type = "day";
                cal_date = app.getCalendarDate();
            }
            
            $.ajax({
                type: "POST",
                url: app.admin_url + "/appointments/" + location_id + "/resources/events",
                dataType: "json",
                data: JSON.stringify({
                    cal_type,
                    cal_date,
                    resources_ids
                }),
                contentType: "application/json",
                timeout: 10000,
                success: function(data) {
                    try {
                        if (data && data.events && Array.isArray(data.events)) {
                            
                            //calendar.setOption('events', data.events);
                            calendar.addEventSource(data.events);
                            

                        }
                    } catch (error) {
                        console.error('Error processing events:', error);
                    } finally {
                        isLoadingEvents = false;
                        enable_btns();

                        //
                        app.updateCalendarTitle();
                        $("#calendar-main").unblock();

                        //
                        calendar.render();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading events:", error);
                    isLoadingEvents = false;
                    enable_btns();
                    $("#calendar-main").unblock();
                }
            });
        } catch (error) {
            console.error('Error in loadLocationResourcesEvents:', error);
            isLoadingEvents = false;
            enable_btns();
            $("#calendar-main").unblock();
        }
    }


    //
    app.determineCalendarViewType = function(calendar_view_type){
        //
        //$("#resources_list_container").hide();
        enableAllDays();
        
        
        /**
         * aqui el isLoadingResources permite actualizar calendario y setear su view type sin llamar a loadLocationResources
         */
        //
        isLoadingResources = true;
        //
        calendar.getResources().forEach(resource => resource.remove());
        calendar.setOption('resources', []);
        calendar.removeAllEvents();
        calendar.setOption('events', []);
        calendar.changeView(calendar_view_type);
        calendar.render();            
        //
        isLoadingResources = false;

        //
        if ( calendar_view_type === "resourceTimeGridWeek" ){
            enableWeekDays();
        }
        //
        app.loadResourcesList(calendar_view_type);
    }


    //
    app.bindEvents = function(){
        
        //
        $("input[name=calendarView]").click(function() {
            //
            var calendar_view_type = $('input[name=calendarView]:checked').val();
            localStorage.setItem("cal_view_type", calendar_view_type);
            //
            app.determineCalendarViewType(calendar_view_type);
        });

        //
        $("#filter_location").change(function() {
            //
            localStorage.setItem("loc_id", $(this).val());

            //
            isLoadingResources = true;
            //
            calendar.getResources().forEach(resource => resource.remove());
            calendar.setOption('resources', []);
            calendar.removeAllEvents();
            calendar.setOption('events', []);
            calendar.render();
            //
            isLoadingResources = false;
            
            //
            app.loadResourcesList(calendar.view.type);
        });

    }

    app.loadLocations = function(data){
        //
        if ( data && data.locations  && data.locations.length ){
            //
            var ls_loc_id = localStorage.getItem("loc_id");
            //
            $.each(data.locations, function(idx, item){
                //
                var option = $("<option />")
                    .attr("data-info", JSON.stringify(item))
                    .val(item.id)    
                    .text(item.name);
                //
                if (ls_loc_id == item.id){
                    option.attr("selected", "selected");
                }
                //
                option.appendTo("#filter_location");
                
            });
            $("#filter_location").removeAttr("disabled");
        }
    }
    // 
    app.calendarViews = function(data){
        //
        if ( data && data.calendar_views  && data.calendar_views.length ){
            // Limpiar el contenedor primero
            $("#calendar_views").empty();
            //
            var ls_cal_view_type = localStorage.getItem("cal_view_type");
            
            $.each(data.calendar_views, function(idx, item){
                //
                var isChecked = (ls_cal_view_type === item.calendar_type || item.selected) ? 'checked' : '';
                var radioId = 'calendarView' + item.id;
                
                var radioHtml = `
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="calendarView" 
                            id="${radioId}" value="${item.calendar_type}" ${isChecked}>
                        <label class="form-check-label" for="${radioId}">
                            ${item.nombre}
                        </label>
                    </div>
                `;
                //
                $("#calendar_views").append(radioHtml);
            });            
        }        
    }
    //
    app.loadStatus = function(data){
        //
        if ( data && data.cal_status  && data.cal_status.length ){
            //
            $("#status_list").empty();
            //                    
            $.each(data.cal_status, function(idx, item){
                //
                var statusHtml = `
                    <div class="status-item">
                        <span class="status-color" style="background-color: ${item.status_color};"></span>
                        ${item.status}
                    </div>
                `;
                $("#status_list").append(statusHtml);
            });
        }
    }
    
    //
    app.init = function(){
        //
        $.ajax({
            type: "GET",
            url: app.admin_url + "/appointments/res-init",
            success: function(data) {
                enable_btns();
                preload(".section-preloader, .overlay");

                //
                app.loadLocations(data);
                app.calendarViews(data);
                app.loadStatus(data);
                // 
                app.bindEvents();

                //
                if (data.slot_min_duration){
                    calendar.setOption('slotDuration', data.slot_min_duration);
                }

                //
                let calendar_view_type = "resourceTimeGridDay";
                //
                var ls_cal_view_type = localStorage.getItem("cal_view_type");
                if ( ls_cal_view_type ){
                    calendar_view_type = ls_cal_view_type;
                }
                else if ( data.config_initial_view && data.config_initial_view.calendar_type ){
                    calendar_view_type = data.config_initial_view.calendar_type;
                }
                app.determineCalendarViewType(calendar_view_type);
                
            },
            error: function(xhr, status, error) {
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                console.error("Error: " + error);
            }
        });

    }

    //
    app.init();

    

})(jQuery);