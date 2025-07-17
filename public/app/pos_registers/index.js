(function ($) {
    'use strict';


    //
    let defaultPosItem = null;
    let qs_pos_id = (URI().search(true).pid);
    let default_pos_id = (qs_pos_id && $.isNumeric(qs_pos_id)) ? qs_pos_id : false;


    // 
    app.createSection({
        section_title: "Cajas",
        section_title_singular: "Caja",
        scripts_path: "/app/pos_registers",
        endpoint_url: app.admin_url + "/pos/registers",
        gridOptions: {
    columns: [
        // Checkbox
        {visible: false, "data": function(obj){ return setCheckbox(obj.id); }},
        
        // Folio
        {"name": "id", "data": "id"},
        
        // Punto de Venta
        {"name": "pos_name", "data": "pos_name"},
        
        // Fecha/Usuario (combinado)
        {"name": "opened_user_name", "data": function(obj){ 
            return obj.opened_user_name + "<br><small class='text-muted'>" + 
                   moment(obj.opened_datetime.date).format('DD/MM/YY HH:mm') + "</small>"; 
        }},
        
        // Cantidad de Ventas
        {"name": "total_ventas", "data": "total_ventas"},
        
        // Total Vendido
        {"name": "ventas_total", "data": function(obj){ 
            return '$' + parseFloat(obj.ventas_total || 0).toFixed(2); 
        }},
        
        // Efectivo Final (MXN)
        {"name": "efectivo_final_esperado_mxn", "data": function(obj){ 
            const esperado = parseFloat(obj.efectivo_final_esperado_mxn || 0).toFixed(2);
            const real = parseFloat(obj.efectivo_final_real_mxn || 0).toFixed(2);
            const diferencia = parseFloat(obj.diferencia_mxn || 0);
            
            let html = '$' + esperado;
            if (obj.closed_user_id) {
                // Si está cerrada, mostrar real vs esperado
                if (diferencia !== 0) {
                    const color = diferencia > 0 ? 'text-success' : 'text-danger';
                    html += `<br><small class="${color}">Real: $${real}</small>`;
                }
            }
            return html;
        }},
        
        // Dólares
        {"name": "dolares", "data": function(obj){ 
            const inicial = parseFloat(obj.balance_inicial_usd || 0).toFixed(2);
            const vendidos = parseFloat(obj.dolares_vendidos_usd || 0).toFixed(2);
            const final = parseFloat(obj.efectivo_final_esperado_usd || 0).toFixed(2);
            
            if (vendidos > 0) {
                return `${inicial} <small class="text-success">(+${vendidos})</small><br><strong>${final} USD</strong>`;
            } else {
                return `${final} USD`;
            }
        }},
        
        // Diferencias (MXN + USD combinado)
        {"name": "diferencias", "data": function(obj){
            if (!obj.closed_user_id) {
                return '<span class="badge bg-secondary">Abierta</span>';
            }
            
            const difMxn = parseFloat(obj.diferencia_mxn || 0);
            const difUsd = parseFloat(obj.diferencia_usd || 0);
            let html = '';
            
            // Diferencia MXN
            if (difMxn !== 0) {
                const colorMxn = difMxn > 0 ? 'text-success' : 'text-danger';
                const signMxn = difMxn > 0 ? '+' : '';
                html += `<span class="${colorMxn}">${signMxn}$${difMxn.toFixed(2)}</span>`;
            } else {
                html += '<span class="text-muted">$0.00</span>';
            }
            
            html += '<br>';
            
            // Diferencia USD
            if (difUsd !== 0) {
                const colorUsd = difUsd > 0 ? 'text-success' : 'text-danger';
                const signUsd = difUsd > 0 ? '+' : '';
                html += `<span class="${colorUsd}">${signUsd}${difUsd.toFixed(2)} USD</span>`;
            } else {
                html += '<span class="text-muted">0.00 USD</span>';
            }
            
            return html;
        }},
        
        // Estado
        {"name": "estado", "data": function(obj){
            if (obj.closed_user_id && obj.closed_datetime) {
                return '<span class="badge bg-primary">Cerrada</span>';
            } 
            return '<span class="badge bg-info">Abierta</span>';
        }},
        
        // Acciones
        {"data": function(obj){
            var str_btns = "<div class='text-center'>";
            var edit_url = "/admin/pos/registers/" + obj.id + "/view";
            str_btns += "<a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info'><i class='fas fa-eye'></i></a>";
            str_btns += "</div>";
            return str_btns;
        }}
    ],
    
    columnDefs: [
        { "targets": [0, 4, 5, 6, 7, 8, 9, 10], "orderable": false },
        { "targets": [4, 5, 6, 7, 8], "className": "text-end" },
        { "targets": [3, 6, 7, 8], "width": "120px" },
        { "targets": "_all", "searchable": false }
    ],
    
    deferLoading: true,
    hdrBtnsSearch: true,
    order: [[ 1, "desc" ]],
},

// TOTALES ACTUALIZADOS:
onDataReady: function(response) {
    if (response && response.header) {
        $('#total-registros').text(response.header.total + ' registros');
        $('#total-cantidad-ventas').text(response.header.sum_total_ventas);
        $('#total-ventas').text('$' + parseFloat(response.header.sum_ventas_total || 0).toFixed(2));
        
        // Efectivo Final
        $('#total-efectivo-final').text('$' + parseFloat(response.header.sum_efectivo_final_esperado_mxn || 0).toFixed(2));
        
        // Dólares
        const finalUsd = parseFloat(response.header.sum_efectivo_final_esperado_usd || 0).toFixed(2);
        const vendidosUsd = parseFloat(response.header.sum_dolares_vendidos_usd || 0).toFixed(2);
        $('#total-dolares').html(`<strong>${finalUsd} USD</strong><br><small class="text-success">+${vendidosUsd}</small>`);
        
        // Diferencias
        const difMxn = parseFloat(response.header.sum_diferencia_mxn || 0);
        const difUsd = parseFloat(response.header.sum_diferencia_usd || 0);
        let difHtml = '';
        
        const colorMxn = difMxn > 0 ? 'text-success' : (difMxn < 0 ? 'text-danger' : 'text-muted');
        const signMxn = difMxn > 0 ? '+' : '';
        difHtml += `<span class="${colorMxn}">${signMxn}$${difMxn.toFixed(2)}</span><br>`;
        
        const colorUsd = difUsd > 0 ? 'text-success' : (difUsd < 0 ? 'text-danger' : 'text-muted');
        const signUsd = difUsd > 0 ? '+' : '';
        difHtml += `<span class="${colorUsd}">${signUsd}${difUsd.toFixed(2)} USD</span>`;
        
        $('#total-diferencias').html(difHtml);
    }
},
        onSectionReady: function(opts){            
            //
            $(".sucursal_name").text($("#sucursal_name").val());
        }
    });



    

    //
    $.S2Ext({
        S2ContainerId: "opened_user_id",
        placeholder: "...buscar usuario",
        language: {
            noResults: function(){ return ""; },
            searching: function(){ return ""; }
        },
        //dropdownParent:   $('#sales'),
        allowClear: true,
        minimumInputLength: 2,
        minimumResultsForSearch: "-1",
        remote: {
            qs: function(){
                return {};
            },
            url: app.admin_url + "/pos/list/users/search",
            dataType: 'json',
            delay: 250,
            processResults: function (response, page) {
                return {
                    results: response
                };
            },
            cache: false,
            templateResult: app.templateOpenedUsersResults,
            templateSelection: app.templateOpenedUsersSelection,
        },
        onChanged: function(sel_id, data){
            //console.log('sel item:', sel_id, data);
            filterGrid();
        },
        onClearing: function(){
            filterGrid();
        },
    });




    //
    function filterGrid(){
        //
        var filter_pos_id = default_pos_id || $("#filter_pos_id").val();
        let opened_user_id = $("#opened_user_id").val();
        //
        var start_date = $('#filter_date').datetimepicker('date');
        var end_date = $('#filter_date_end').datetimepicker('date');

        //
        var qs = "?a=1";
        //
        if (filter_pos_id){
            qs += "&pid=" + filter_pos_id;   
        }
        if (opened_user_id){
            qs += "&uid=" + opened_user_id;   
        }

        //
        if (start_date && end_date){
            //
            qs += "&sd=" + start_date.format('YYYY-MM-DD');   
            qs += "&ed=" + end_date.format('YYYY-MM-DD');   
            //
            $("#grid_section").DataTable().ajax.url(app.admin_url + "/pos/registers" + qs);
            $("#grid_section").DataTable().ajax.reload();

        } else {

               $("#grid_section tbody").empty();
               
        }
        
    }



    



    //
    var savedDateEnd = localStorage.getItem("sales_sel_date_end");
    var defaultDateEnd = savedDateEnd ? moment(savedDateEnd, 'YYYY-MM-DD') : moment();
    //
    $('#filter_date_end').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es-mx',
        date: defaultDateEnd,
        buttons: {
            showToday: false,
            showClear: false,
            showClose: false
        },
        icons: app.tdBs5Icons,
        viewMode: 'days',
        pickTime: false
    });

    // 
    $("#filter_date_end").on("change.datetimepicker", function(e) {
        if (e.date) {
            //
            var selDate = e.date.format('YYYY-MM-DD');
            //console.log(selDate);
            localStorage.setItem("sales_sel_date_end", selDate);
            //
            filterGrid();
        }
    });
    


    // debug no local storage date
    //localStorage.removeItem("sales_sel_date"); return;
    var savedDate = localStorage.getItem("sales_sel_date");
    var defaultDate = savedDate ? moment(savedDate, 'YYYY-MM-DD') : moment();

    //
    $('#filter_date').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es-mx',
        date: defaultDate,
        buttons: {
            showToday: false,
            showClear: false,
            showClose: false
        },
        icons: app.tdBs5Icons,
        viewMode: 'days',
        pickTime: false
    });

    //
    $("#filter_date").on("change.datetimepicker", function(e) {
        if (e.date) {
            //
            var selDate = e.date.format('YYYY-MM-DD');
            //console.log(selDate);
            localStorage.setItem("sales_sel_date", selDate);
            //
            $('#filter_date_end').datetimepicker('minDate', e.date);
            $('#filter_date_end').datetimepicker('date', e.date);
        }
    });







    



    

    //
    if ( !$.isNumeric(default_pos_id) ){
        $("#select_pdv_container").show();
        $(".view-title").html("Cajas");
    }
    
    //
    selectLoad2({
        id: "#filter_pos_id",
        url: app.admin_url + "/pos/list-avail",
        parseFields: function(item){
            return item.location_name + " - " + item.name;
        },
        prependEmptyOption: true,
        emptyOptionText: "--todos",
        saveValue: true,
        enable: true,
        default_value: default_pos_id,
        onItemIteration: function(item, $option, index, $select) {
            //console.log(`Procesando item ${index}:`, item);
            //
            if (item.id == default_pos_id) {
                defaultPosItem = item;
                //console.log('Item por defecto encontrado:', defaultPosItem);
            }
        },
        onChange: function(){     
            //
            filterGrid();
        },
        onReady: function(){
            setTimeout(function(){
                if ( default_pos_id && defaultPosItem ) {
                    console.log("***: ", defaultPosItem);
                    $(".view-title").html("Cajas - " + defaultPosItem.name);
                }
            }, 500);
            //
            filterGrid();
        }
    });



    
    //
    function downloadReportXls(){
        //
        var filter_pos_id = default_pos_id || $("#filter_pos_id").val();
        let opened_user_id = $("#opened_user_id").val();
        //
        var start_date = $('#filter_date').datetimepicker('date');
        var end_date = $('#filter_date_end').datetimepicker('date');

        //
        var qs = "?a=1";
        //
        if (filter_pos_id){
            qs += "&pid=" + filter_pos_id;   
        }
        if (opened_user_id){
            qs += "&uid=" + opened_user_id;   
        }

        //
        if (start_date && end_date){
            //
            qs += "&sd=" + start_date.format('YYYY-MM-DD');   
            qs += "&ed=" + end_date.format('YYYY-MM-DD');   
            //
            window.open(app.admin_url + "/pos/registers/report-xls" + qs);        
        }        
    }






    // 
    $(".btnDownloadExcel").click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        if (confirm("Solo se mostraran cajas cerradas, Descargar Reporte?")){
            downloadReportXls();
        }
    });



    //
    $(".btnReloadGrid").click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        filterGrid();
    });

    

})(jQuery);