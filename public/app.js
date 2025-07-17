
var app = {};


//
app.public_url = "/public";
//
app.admin_url = "/admin";
app.supadmin_url = "/adm27";

//
app.STRP_CARD = "stripe_card";
app.STRP_OXXO = "stripe_oxxo";
app.ANET_CARD = "authorizenet_card";
app.SQR_CARD = "square_card";
app.CASH = "cash";
app.CREDIT = "credit";

//
app.APP_ID_MSNEXPR = 11;
app.APP_ID_PLBZ = 13;
app.APP_ID_T4B = 16;

//
app.CITA_STATUS_CONFIRMED = 1;
app.CITA_STATUS_PENDING = 2;
app.CITA_STATUS_CANCELLED = 3;
app.CITA_STATUS_IN_PROGRESS = 4;

//
app.inv_status_id_opened = 1;
app.inv_status_id_por_cobrar = 2;
app.inv_status_id_paid = 3;
app.inv_status_id_cancelled = 4;

//
app.PROD_TYPE_CUSTOMER_ID = 12;
app.PROD_TYPE_STORE_ID = 13;

//
app.ID_PAIS_MEXICO = 379;
app.ID_PAIS_EU = 467;

//
app.PAYMENT_METHOD_ID_EFECTIVO = 2;
app.PAYMENT_METHOD_ID_TARJETA = 8;
app.PAYMENT_METHOD_ID_DOLARES = 10;


//
app.Toast = Swal.mixin({
    toast: true,
    position: 'top',
    showConfirmButton: false,
    timer: 3000
});


app.dt_lang = {
    "sProcessing":     "Procesando...",
    "sLengthMenu":     "Mostrar _MENU_ registros",
    "sZeroRecords":    "No se encontraron resultados",
    "sEmptyTable":     "Ningún dato disponible en esta tabla",
    "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
    "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
    "sInfoPostFix":    "",
    "sSearch":         "Buscar:",
    "sUrl":            "",
    "sInfoThousands":  ",",
    "sLoadingRecords": "Cargando...",
    "oPaginate": {
        "sFirst":    "Primero",
        "sLast":     "Último",
        "sNext":     "Siguiente",
        "sPrevious": "Anterior"
    },
    "oAria": {
        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
    }
}



/*
Convierte fechas obtenidas de full calendar a moment obj
*/
app.fc_date = function(full_calendar_date){
    //
    let dt_obj = moment(full_calendar_date);
    //
    let str_date = dt_obj.format('DD MMM YYYY');
    let str_time = dt_obj.format('h:mm A');
    let str_day = dt_obj.format('dddd');
    let sql_datetime = dt_obj.format('YYYY-MM-DD HH:mm:ss');
    //
    return {
        dt_obj,
        str_date,
        str_time,
        str_day,
        sql_datetime
    }
}


app.getColumnName = function(column_number){
    switch(column_number){
        case(1):
            return "A";
        case(2):
            return "B";
        case(3):
            return "C";
        case(4):
            return "D";
        default:
            return null;
    }
}

//
app.getPriorityByCode = function(prioridad){
    if (prioridad===10){ return "Máxima prioridad - Cierre de emergencia"; }
    else if (prioridad===20){ return "Alta prioridad - Evento especial"; }
    else if (prioridad===30){ return "Media-alta - Días festivos/feriados"; }
    else if (prioridad===40){ return "Media - Temporada especial"; }
    else if (prioridad===50){ return "Media-baja - Cambio en día específico"; }
    else if (prioridad===100){ return "Baja - Excepción regular"; }
    else if (prioridad===999){ return "Mínima - Solo aplica si no hay otra excepción"; }
    else {return "";}
}



app.excepcionTipoField = function(obj){
    var str_prioridad = "<small>(" + app.getPriorityByCode(obj.prioridad) + ")</small>";
    if (obj.excepcion_tipo==="solo_dia"){
        return "Dia Completo " + str_prioridad;
    }
    else if (obj.excepcion_tipo==="solo_dia_con_horario"){
        return "Por Horario " + str_prioridad;
    }
    else if (obj.excepcion_tipo==="rango_dias"){
        return "Rango de Dias Completos " + str_prioridad;
    }
    else if (obj.excepcion_tipo==="rango_dias_con_horario"){
        return "Horario en Rango de Dias " + str_prioridad;
    }
    //
    return "";
}

app.excepcionFechaField = function(obj){
    if (obj.excepcion_tipo==="solo_dia" || obj.excepcion_tipo==="solo_dia_con_horario"){
        //
        if (obj.fecha){
            return fmtDateSpanish(obj.fecha.date);
        } else {
            return "--";                            
        }
    }
    else if (obj.excepcion_tipo==="rango_dias" || obj.excepcion_tipo==="rango_dias_con_horario"){
        //
        if (obj.fecha && obj.fecha_fin){
            return "desde " + fmtDateSpanish(obj.fecha.date) + " hasta " + fmtDateSpanish(obj.fecha_fin.date);
        } else {
            return "--";
        }
    }
    //
    return "";
}


app.formatCurrency = function(amount) {
    return Math.round(amount * 100) / 100;
}

app.excepcionHorarioField = function(obj){
    if (obj.excepcion_tipo==="solo_dia_con_horario" || obj.excepcion_tipo==="rango_dias_con_horario"){
        //
        if (obj.hora_inicio && obj.hora_fin){
            return "desde <strong>" + fmtTime(obj.hora_inicio.date) + "</strong> hasta <strong>" + fmtTime(obj.hora_fin.date) + "</strong>";
        } else {
            return "--";                            
        }
    }
    //
    return "";
}

app.excepcionRecurrenciaField = function(obj){
    //
    if (obj.recurrencia_tipo){

        //
        var str_recurrencia_tipo_diaria_dias = obj.recurrencia_tipo;
        if (obj.recurrencia_tipo==="diaria"){
            //
            var str_dias = "";
            var count_dias = 0;
            //
            if (obj.recurrencia_mon){ str_dias += "Lunes, "; count_dias += 1;}
            if (obj.recurrencia_tue){ str_dias += "Martes, "; count_dias += 1;}
            if (obj.recurrencia_wed){ str_dias += "Miercoles, "; count_dias += 1;}
            if (obj.recurrencia_thu){ str_dias += "Jueves, "; count_dias += 1;}
            if (obj.recurrencia_fri){ str_dias += "Viernes, "; count_dias += 1;}
            if (obj.recurrencia_sat){ str_dias += "Sabado, "; count_dias += 1;}
            if (obj.recurrencia_sun){ str_dias += "Domingo, "; count_dias += 1;}
            if (count_dias===7){
                str_recurrencia_tipo_diaria_dias = "Todos los dias"
            } else {
                str_recurrencia_tipo_diaria_dias = " Diario los Dias <strong>" + str_dias + "</strong>";
            }
        }

        //
        if (obj.recurrencia_fecha_limite){
            //
            return "Repetir " + str_recurrencia_tipo_diaria_dias + "<br /> Hasta el " +  fmtDateSpanish(obj.recurrencia_fecha_limite.date, true);
        } else {
            return "Repetir " + str_recurrencia_tipo_diaria_dias + " ilimitado";
        }
    }
    return ""   
}


//
app.templateResultDefault = function(item){
    if (item.loading) {
        return item.text;
    }
    return item.id + " " + item.nombre;
}
//
app.templateSelectionDefault = function(item){
    if (item.id){
        return item.id + " " + item.nombre;
    }
    return item.text;
}




// Template para mostrar resultados en dropdown
app.templateResultPromos = function(item){
    if (item.loading) {
        return item.text;
    }
    
    // Formatear fechas
    let fechaInicio = moment(item.fecha_hora_inicio.date).format('DD MMM YYYY h:mm A');
    let fechaFin = moment(item.fecha_hora_fin.date).format('DD MMM YYYY h:mm A');
    
    // Determinar tipo de descuento
    let tipoDescuento = item.es_porcentaje ? item.valor + '%' : '$' + parseFloat(item.valor).toFixed(2);
    
    return $(`
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>${item.clave}</strong> - ${item.descripcion}
                <br>
                 <small class="text-white">
                    Descuento: ${tipoDescuento} | ${fechaInicio} - ${fechaFin}
                </small>
            </div>
        </div>
    `);
}

// Template para mostrar selección
app.templateSelectionPromos = function(item){
    if (item.id){
        let fechaInicio = moment(item.fecha_hora_inicio.date).format('DD MMM YYYY');
        let fechaFin = moment(item.fecha_hora_fin.date).format('DD MMM YYYY');
        let tipoDescuento = item.es_porcentaje ? item.valor + '%' : '$' + parseFloat(item.valor).toFixed(2);
        
        return `${item.clave} - ${item.descripcion} (${tipoDescuento}) | ${fechaInicio} - ${fechaFin}`;
    }
    return item.text;
}



//
app.templateResultContact = function(item){
    if (item.loading) {
        return item.text;
    }
    var str_email = item.email ? item.email + " - " : "";
    return item.id + " - " + str_email + item.name;
}
//
app.templateSelectionContact = function(item){
    if (item.id){
        var str_email = item.email ? item.email + " - " : "";
        return item.id + " - " + str_email + item.name;
    }
    return item.text;
}




//
app.templateResultProduct = function(item){
    if (item.loading) {
        return item.text;
    }
    return item.id + " - " + item.nombre;
}
//
app.templateSelectionProduct = function(item){
    if (item.id){
        return item.id + " - " + item.nombre
    }
    return item.text;
}






//
app.templateCreatedUsersResults = function(item){
    if (item.loading) {
        return item.text;
    }
    var str_email = item.created_user_email ? item.created_user_email + " - " : "";
    return item.id + " - " + str_email + item.created_user_name;
}
//
app.templateCreatedUsersSelection = function(item){
    if (item.id){
        var str_email = item.created_user_email ? item.created_user_email + " - " : "";
        return item.id + " - " + str_email + item.created_user_name;
    }
    return item.text;
}


//
app.templateOpenedUsersResults = function(item){
    if (item.loading) {
        return item.text;
    }
    var str_email = item.opened_user_email ? item.opened_user_email + " - " : "";
    return item.id + " - " + str_email + item.opened_user_name;
}
//
app.templateOpenedUsersSelection = function(item){
    if (item.id){
        var str_email = item.opened_user_email ? item.opened_user_email + " - " : "";
        return item.id + " - " + str_email + item.opened_user_name;
    }
    return item.text;
}




app.tdBs5Icons = {
    time: "fas fa-clock",
    date: "fas fa-calendar",
    up: "fas fa-arrow-up",
    down: "fas fa-arrow-down",
    previous: "fas fa-chevron-left", 
    next: "fas fa-chevron-right",
    today: "fas fa-calendar-check",
    clear: "fas fa-trash",
    close: "fas fa-times"
}



$.fn.keypressDelay = function (cb) {
    var self = this;
    $(this).keyup(function(e){
        var timer;
        clearTimeout(timer);
        timer = setTimeout(function(){
            cb(e, $(self).val());
        }, 350);

    });
    return $(this);
}






function fmtTime(field_value, time_format){
    var dt = moment(field_value);
    if (field_value && dt){
        if (time_format){
            return dt.format(time_format);
        } else {
            return dt.format("h:mm A");
        }
    }
    return null;
}



function fmtDateSpanish(field_value, inc_time){
    var dt = moment(field_value);
    if (field_value && dt){
        if (inc_time){
            return dt.format("DD MMM YYYY - h:mm A");
        } else {
            return dt.format("DD MMMM YYYY");
        }
    }
    return "";
}

// ===== FUNCIONES BOOTSTRAP 5 =====

function setCheckbox(obj_id, str_name) {
    return `<div class="form-check">
        <input class="form-check-input" type="checkbox" id="check_${obj_id}" value="${obj_id}">
    </div>`;
}



function fmtStatusWithText(field_value, active_text, inactive_text) {
    if (field_value) {
        return `<span class="badge bg-success">
            <i class="fas fa-check me-1"></i>${active_text}
        </span>`;
    } else {
        return `<span class="badge bg-danger">
            <i class="fas fa-times me-1"></i>${inactive_text}
        </span>`;
    }
}



function delayBtn(btn_id) {
    const btn = $(btn_id);
    const originalHtml = btn.html();
    
    btn.prop("disabled", true).html(`
        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
        Procesando...
    `);
    
    setTimeout(function() {
        btn.prop("disabled", false).html(originalHtml);
    }, 1000);
}

// ===== FUNCIONES ADICIONALES ÚTILES =====

function fmtBooleanIcon(field_value, true_icon = "check", false_icon = "times") {
    if (field_value) {
        return `<span class="text-success">
            <i class="fas fa-${true_icon}"></i>
        </span>`;
    } else {
        return `<span class="text-muted">
            <i class="fas fa-${false_icon}"></i>
        </span>`;
    }
}

function fmtStatusDot(field_value, active_color = "success", inactive_color = "danger") {
    const color = field_value ? active_color : inactive_color;
    const text = field_value ? "Activo" : "Inactivo";
    
    return `<span class="d-flex align-items-center">
        <span class="badge rounded-pill bg-${color} me-2" style="width:8px;height:8px;"></span>
        <small class="text-${color}">${text}</small>
    </span>`;
}

function fmtPriority(level) {
    const priorities = {
        'high': { color: 'danger', icon: 'exclamation-triangle', text: 'Alta' },
        'medium': { color: 'warning', icon: 'exclamation-circle', text: 'Media' },
        'low': { color: 'success', icon: 'check-circle', text: 'Baja' }
    };
    
    const priority = priorities[level] || priorities['low'];
    
    return `<span class="badge bg-${priority.color}">
        <i class="fas fa-${priority.icon} me-1"></i>${priority.text}
    </span>`;
}




/*
* Get Table Row Responsive Element
* */
function getTrRespElem(elem){
    //
    var curr_tr = $(elem).parents('tr');
    if ( curr_tr.hasClass("child") ){
        return curr_tr.prev();
    }
    return curr_tr;
}



$('.btnLogout').click(function(e) {
    e.preventDefault();

    //
    var logout_type = $(this).data("type");
    //console.log(logout_type); return;


    //
    if (logout_type === "admin"){
        //
        if (confirm("Cerrar sesion?")){
            $.ajax({
                type: "GET",
                url: app.admin_url + "/auth/logout",
                success: function(response){
                    //
                    location.href = "/admin/home";
                },
                error: function(){
                    app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                }
            });
        }
    }
    else if (logout_type === "adm27"){
        //
        if (confirm("Cerrar sesion?")){
            $.ajax({
                type: "GET",
                url: app.supadmin_url + "/auth/logout",
                success: function(response){
                    //
                    location.href = "/adm27/home";
                },
                error: function(){
                    app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                }
            });
        }
    }

});











//
function convertToUrl(str_val){

    // remove accents
    str_val = str_val.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // remove symbols
    str_val = str_val.replace(/[^a-zA-Z0-9 ]/g, "");
    // replace spaces with dashes
    str_val = str_val.replace(/\s+/g, '-').toLowerCase();
    // encode to valid uri
    return encodeURI(str_val.trim());
}




app.addItem = function(item, show_select_btn){
    //console.log(item);

    //
    var sel_item = JSON.stringify({
        id: item.id,
        ruta_id: item.ruta_id,
        origen_ciudad_id: item.origen_ciudad_id,
        destino_ciudad_id: item.destino_ciudad_id
    });

    //
    var str_item = "<tr>";
    str_item += "<td>" + item.id + " / " + item.ruta_id + " / " + item.autobus_clave + "</td>";


    //
    var str_ext_destino = (item.ext_destino_ciudad_id) ? "<br /><small>extension a " + item.ext_destino_info + "</small> " : "";
    str_item += "<td>" + item.origen_ciudad_info + " <br /> " + momentFormat(item.fecha_hora_salida.date, "DD MMM YY h:mm A") + str_ext_destino + " </td>";
    //
    var str_ext_origen = (item.ext_origen_ciudad_id) ? "<br /><small>extension de " + item.ext_origen_info + "</small> " : "";
    str_item += "<td>" + item.destino_ciudad_info + " <br /> " + momentFormat(item.fecha_hora_llegada.date, "DD MMM YY h:mm A") + str_ext_origen + "</td>";


    var total = toFloat(item.total);
    str_item += "<td>$" + total + "</td>";

    //
    if (show_select_btn){
        str_item += "<td><button type='button' class='btn btn-xs btn-primary btn-select-item btn-block' data-selected-item='" + sel_item + "'> Select </button></td>";
    }


    //
    str_item += "</tr>";
    //
    return str_item
}




function block(container_name_id, message){
    $(container_name_id).block({ 
        message: message, 
        css: {
            padding: '15px 25px',           // Padding más compacto
            width: '35%',                   // Más estrecho: de 50% a 35%
            maxWidth: '250px',              // Ancho máximo más pequeño
            minWidth: '150px',              // Ancho mínimo
            border: 'none',
            borderRadius: '12px',
            backgroundColor: 'rgba(248, 249, 250, 0.9)', // De vuelta a 0.9
            color: '#495057',
            fontSize: '14px',
            textAlign: 'center',
            boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
            left: '32.5%',                  // Recentrar para el nuevo ancho
            top: '45%'
        },
        overlayCSS: { 
            borderRadius: "10px",
            opacity: 0.3,                   // De vuelta como al principio
            backgroundColor: '#6c757d'
        }
    });
}


function generateRandom4DigitNumber() {
    return Math.floor(1000 + Math.random() * 9000);
}


//
app.goToTab = function(tabSelector) {
    // Validar que el tab existe
    if ($(tabSelector).length) {
        $(tabSelector).tab('show');
        return true;
    } else {
        console.warn('Tab no encontrado:', tabSelector);
        return false;
    }
}

