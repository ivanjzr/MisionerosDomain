(function ($) {
    'use strict';

    //
    function onViewReady(section_data, opts){

        // Información General
        $('#register_id').text(section_data.id);
        $('#location_name').text(section_data.location_name || '--');
        $('#pos_name').text(section_data.pos_name || '--');
        $('.register-title').text(section_data.pos_name || 'Caja #' + section_data.id);

        // Estado de la caja
        if (section_data.closed_datetime) {
            $('#status_badge').html('<span class="badge bg-danger">Cerrada</span>');
        } else {
            $('#status_badge').html('<span class="badge bg-success">Abierta</span>');
        }

        // Usuarios
        $('#opened_user').text(section_data.opened_user_name || '--');
        if (section_data.opened_user_email) {
            $('#opened_user').append('<br><small class="text-muted">' + section_data.opened_user_email + '</small>');
        }
        
        if (section_data.opened_datetime && section_data.opened_datetime.date) {
            $('#opened_datetime').text(moment(section_data.opened_datetime.date).format('DD/MM/YYYY HH:mm'));
        }

        // Usuario que cerró (solo si está cerrada)
        if (section_data.closed_datetime) {
            $('#closed_user_section').show();
            $('#closed_user').text(section_data.closed_user_name || '--');
            if (section_data.closed_user_email) {
                $('#closed_user').append('<br><small class="text-muted">' + section_data.closed_user_email + '</small>');
            }
            if (section_data.closed_datetime && section_data.closed_datetime.date) {
                $('#closed_datetime').text(moment(section_data.closed_datetime.date).format('DD/MM/YYYY HH:mm'));
            }
            $('#current_user_section').hide();
        } else {
            // Usuario actual (solo si está abierta)
            $('#current_user').text(section_data.current_user_name || '--');
        }

        // Balance Inicial
        $('#balance_inicial_mxn').text('$' + parseFloat(section_data.balance_inicial_mxn || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}));
        $('#balance_inicial_usd').text('$' + parseFloat(section_data.balance_inicial_usd || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' USD');

        // Totales de Ventas
        $('#ventas_total').text('$' + parseFloat(section_data.ventas_total || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}));
        $('#efectivo_cobrado_mxn').text('$' + parseFloat(section_data.efectivo_cobrado_mxn || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}));
        $('#cambio_dado').text('$' + parseFloat(section_data.cambio_dado || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}));
        $('#tarjetas_total').text('$' + parseFloat(section_data.tarjetas_total || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}));
        $('#dolares_vendidos_usd').text('$' + parseFloat(section_data.dolares_vendidos_usd || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' USD');

        // Balance Final Esperado
        $('#efectivo_final_esperado_mxn').text('$' + parseFloat(section_data.efectivo_final_esperado_mxn || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}));
        if (section_data.efectivo_final_esperado_usd) {
            $('#efectivo_final_esperado_usd').text('$' + parseFloat(section_data.efectivo_final_esperado_usd || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' USD');
        } else {
            $('#efectivo_final_esperado_usd').text('$0.00 USD');
        }

        // Balance Final Real y Diferencias (solo si está cerrada)
        if (section_data.closed_datetime) {
            $('#real_balance_section').show();
            $('#real_balance_usd_section').show();
            $('#difference_section').show();
            $('#difference_usd_section').show();

            $('#efectivo_final_real_mxn').text('$' + parseFloat(section_data.efectivo_final_real_mxn || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}));
            $('#efectivo_final_real_usd').text('$' + parseFloat(section_data.efectivo_final_real_usd || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' USD');

            // Diferencias con colores
            var diff_mxn = parseFloat(section_data.diferencia_mxn || 0);
            var diff_usd = parseFloat(section_data.diferencia_usd || 0);
            
            var diff_mxn_class = diff_mxn === 0 ? 'text-success' : (diff_mxn > 0 ? 'text-primary' : 'text-danger');
            var diff_usd_class = diff_usd === 0 ? 'text-success' : (diff_usd > 0 ? 'text-primary' : 'text-danger');
            
            $('#diferencia_mxn').removeClass('text-success text-danger text-primary').addClass(diff_mxn_class);
            $('#diferencia_usd').removeClass('text-success text-danger text-primary').addClass(diff_usd_class);
            
            $('#diferencia_mxn').text('$' + diff_mxn.toLocaleString('es-MX', {minimumFractionDigits: 2}));
            $('#diferencia_usd').text('$' + diff_usd.toLocaleString('en-US', {minimumFractionDigits: 2}) + ' USD');
        }

        // Footer info
        $('#registro_folio').text(section_data.id);
        if (section_data.opened_datetime && section_data.opened_datetime.date) {
            $('#registro_fecha_creacion').text(moment(section_data.opened_datetime.date).format('DD/MM/YYYY HH:mm'));
        }
    }

    //
    function loadModules(section_data, opts){
        //
        section_data.opts = opts;

        // MOD - SALES
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/pos_registers/modules/sales.js?v=1.0",
            onInit: function(){
                enable_btns();
            }
        });
    }

    //
    app.createSection({
        section_title: "Caja Ventas",
        section_title_singular: "Caja Venta",
        scripts_path: "/app/pos/registers",
        endpoint_url: app.admin_url + "/pos/registers",
        record_id: record_id,
        onEditReady: onViewReady, // Cambié el nombre pero mantengo la funcionalidad
        loadModules: loadModules,
        onSectionReady: function(opts){

            //
            $('#btnReloadDetails').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                opts.loadData();
            });

            //
            $(".sucursal_name").text($("#sucursal_name").val());
        }
    });

    
    //
    const redirUrl = (URI().search(true).retTo === "sale") ? "/admin/pos/main" : "/admin/pos/registers/index";
    //
    $(".goBackLink").attr("href", redirUrl);




})(jQuery);