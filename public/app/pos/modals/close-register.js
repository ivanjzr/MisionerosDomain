define(function(){
    function moduleReady(modal, s_data){
        console.log(s_data);

        // Variables globales para los cálculos
        var expectedMxn = 0;
        var expectedUsd = 0;

        // Función para formatear moneda
        function formatMoney(amount) {
            return '$' + parseFloat(amount || 0).toFixed(2);
        }

        // Función para calcular diferencias en tiempo real
        function calculateDifferences() {
            var countedMxn = parseFloat($('#closed_balance').val() || 0);
            var countedUsd = parseFloat($('#closed_balance_usd').val() || 0);
            
            var diffMxn = countedMxn - expectedMxn;
            var diffUsd = countedUsd - expectedUsd;
            
            // Mostrar diferencias con colores
            $('#difference_mxn').text(formatMoney(diffMxn))
                .removeClass('text-success text-danger text-info')
                .addClass(diffMxn === 0 ? 'text-success' : (diffMxn > 0 ? 'text-info' : 'text-danger'));
                
            $('#difference_usd').text(formatMoney(diffUsd) + ' USD')
                .removeClass('text-success text-danger text-info')
                .addClass(diffUsd === 0 ? 'text-success' : (diffUsd > 0 ? 'text-info' : 'text-danger'));
            
            // Mostrar sección de diferencias
            $('#differences_section').show();
            
            // Mostrar/ocultar alerta de diferencias
            if (Math.abs(diffMxn) > 0.01 || Math.abs(diffUsd) > 0.01) {
                $('#difference_alert').show();
                var message = "Diferencias encontradas: ";
                if (Math.abs(diffMxn) > 0.01) {
                    message += formatMoney(diffMxn) + " MXN ";
                }
                if (Math.abs(diffUsd) > 0.01) {
                    message += formatMoney(diffUsd) + " USD";
                }
                $('#difference_message').text(message);
            } else {
                $('#difference_alert').hide();
            }
        }

        // Función para llenar datos cuando llegue la respuesta del AJAX
        app.onPosRegisterDataReady = function(register_data){
            
            // Título dinámico
            $("#close-register-title").text("Cierre: " + register_data.pos_name + " - Folio #" + register_data.id);

            // Llenar resumen del día
            $('#summary_ventas').text(formatMoney(register_data.ventas_total));
            $('#summary_efectivo').text(formatMoney(register_data.efectivo_neto_mxn));
            $('#summary_dolares').text(formatMoney(register_data.dolares_vendidos_usd) + ' USD');
            
            // Guardar valores esperados para cálculos
            expectedMxn = parseFloat(register_data.efectivo_final_esperado_mxn || 0);
            expectedUsd = parseFloat(register_data.efectivo_final_esperado_usd || 0);
            
            // Mostrar balance esperado
            $('#expected_balance_mxn').text(formatMoney(expectedMxn));
            $('#expected_balance_usd').text(formatMoney(expectedUsd) + ' USD');
            
            // Pre-llenar campos con valores esperados (opcional)
            $('#closed_balance').val(expectedMxn.toFixed(2));
            $('#closed_balance_usd').val(expectedUsd.toFixed(2));
            
            // Calcular diferencias iniciales
            setTimeout(function() {
                calculateDifferences();
            }, 100);
        }

        // Cargar datos del registro
        app.loadRegisterData = function(){
            $.ajax({
                type: "GET",
                url: app.admin_url + "/pos/registers/" + s_data.pos_register_id,
                success: function(data) {
                    if (data && data.id) {
                        app.onPosRegisterDataReady(data);
                    } else {
                        const err = (data.error) ? data.error : "error al gestionar el registro";
                        alert(err);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading events:", error);
                    enable_btns();
                }
            });
        }
        
        // Cargar datos al inicio
        app.loadRegisterData();

        // Validación del formulario
        $('#form_close_register').validate({
            rules: {
                closed_balance: {
                    required: true,
                    min: 0
                },
                closed_balance_usd: {
                    min: 0
                }
            },
            messages: {
                closed_balance: {
                    required: "Ingrese el balance contado en MXN",
                    min: "El balance no puede ser negativo"
                },
                closed_balance_usd: {
                    min: "El balance no puede ser negativo"
                }
            }
        });

        // Submit del formulario
        $('#form_close_register').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if ($('#form_close_register').valid()) {
                $('#form_close_register').ajaxSubmit({
                    url: app.admin_url + "/pos/main/close-register",
                    beforeSubmit: function(arr){
                        disable_btns();
                        
                        // Agregar parámetros necesarios
                        arr.push({
                            name: "pos_user_id",
                            value: s_data.pos_user_id
                        });
                        arr.push({
                            name: "pos_register_id",
                            value: s_data.pos_register_id
                        });
                        
                        // Asegurar que USD tenga valor (0 si está vacío)
                        arr.forEach(function(item) {
                            if (item.name === 'closed_balance_usd' && !item.value) {
                                item.value = '0';
                            }
                        });
                    },
                    success: function(response){
                        enable_btns();
                        
                        if (response && response.affected_rows > 0){
                            app.Toast.fire({ 
                                icon: 'success', 
                                title: 'Caja cerrada exitosamente' 
                            });
                            
                            //
                            $("#modal-close-register").find('.modal').modal("hide");
                            location.reload();

                        } else {
                            let err = (response.error) ? response.error : "The operation could not be completed. Check your connection or contact the administrator."
                            app.Toast.fire({ icon: 'error', title: err });
                            
                            $("#closed_balance").focus().select();
                        }
                    },
                    error: function(response){
                        enable_btns();
                        app.Toast.fire({ 
                            icon: 'error', 
                            title: "The operation could not be completed. Check your connection or contact the administrator." 
                        });
                    }
                });
            }
        });

        // Focus inicial
        $("#closed_balance").focus();

        // Eventos para calcular diferencias en tiempo real
        $("#closed_balance, #closed_balance_usd").on("input blur", function(){
            if (expectedMxn > 0 || expectedUsd > 0) { // Solo si ya cargaron los datos
                calculateDifferences();
            }
        });

        // Seleccionar todo al hacer focus
        $("#closed_balance, #closed_balance_usd").on("focus", function(){
            $(this).select();
        });

    }
    return {init: moduleReady}
});