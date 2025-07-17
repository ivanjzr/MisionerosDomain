(function ($) {
    'use strict';

    // Estado global para tracking
    app.posData = [];
    app.activePosId = null;

    app.onPosListReady = function(data){
        //console.log("POS Data received:", data);
        //
        app.posData = data;
        app.renderPosList(data);
    };

    app.renderPosList = function(posData) {
        //
        const container = $('#pos_list');
        container.empty();

        if (!posData || posData.length === 0) {
            container.html(`
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h5>No hay puntos de venta disponibles</h5>
                        <p>Contacta al administrador para configurar los puntos de venta.</p>
                    </div>
                </div>
            `);
            return;
        }

        posData.forEach(pos => {
            const card = app.createPosCard(pos);
            container.append(card);
        });

        // Inicializar tooltips de Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

    };



    //
    app.getDDMNenu = function(pos) {
        //
        return `
            <!-- Dropdown Menu -->
            <div class="dropdown">
                <button class="btn btn-link btn-sm text-muted p-0" type="button" 
                        data-bs-toggle="dropdown" aria-expanded="false"
                        data-bs-toggle="tooltip" title="Opciones">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="/admin/pos/registers/index?pid=${pos.id}">
                            <i class="fas fa-cash-register me-2 text-primary"></i>
                            Ver Cajas
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="/admin/pos/sales/index?pid=${pos.id}">
                            <i class="fas fa-chart-line me-2 text-success"></i>
                            Ver Ventas
                        </a>
                    </li>                    
                    <!--
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-muted" href="/admin/pos/${pos.id}/config">
                            <i class="fas fa-cog me-2"></i>
                            Configuración
                        </a>
                    </li>
                    -->
                </ul>
            </div>
        `
    }


    //
    app.getActionButton = function(pos) {
        // Si el POS está inactivo, no mostrar botón
        if (pos.pos_status === 'inactive') {
            return '';
        }
        
        let btn = "";
        
        switch (pos.pos_status) {
            case 'available_initial':
                // Primera vez - nunca ha tenido caja
                btn = `
                <div class="d-grid">
                    <button class="btn btn-primary btn-md" onclick="app.startPOS(${pos.id}, '${pos.name}')">
                        <i class="fas fa-cash-register me-2"></i>
                        Iniciar Caja
                    </button>
                </div>                
                `;
                break;
                
            case 'available':
                // Caja anterior cerrada - puede iniciar nueva
                btn = `
                    <div class="d-grid">
                        <button class="btn btn-primary btn-md" onclick="app.startPOS(${pos.id}, '${pos.name}')">
                            <i class="fas fa-cash-register me-2"></i>
                            Iniciar Caja
                        </button>
                    </div>
                `;
                break;
                
            case 'open':
                // Caja abierta - puede continuar vendiendo
                btn = `
                    <div class="d-grid">
                        <button class="btn btn-success btn-md" onclick="app.continuePOS(${pos.id}, '${pos.name}')">
                            <i class="fas fa-play me-2"></i>
                            Continuar Vendiendo
                        </button>
                    </div>
                    <div class="mt-2 d-grid">
                        <button class="btn btn-outline-danger btn-md" onclick="app.btnCloseRegister(${pos.last_register_id}, ${pos.last_current_user_id})">
                            <i class="fas fa-times me-2"></i>
                            Cerrar Caja
                        </button>
                    </div>
                `;
                break;
                
            default:
                // Estado desconocido o cualquier otro - sin botón
                btn = '';
        }
        
        return btn;
    };


    //  
    app.createPosCard = function(pos) {
        let statusInfo = app.getPosStatusInfo(pos);
        const dropdown_menu = app.getDDMNenu(pos);
        const actionBtn = app.getActionButton(pos);

        //
        const getBorderStyle = (status) => {
            const styles = {
                'open': 'border-success border-3',
                'available': 'border-primary border-2',
                'available_initial': 'border-info border-2',
                'inactive': 'border-secondary'
            };
            return styles[status] || 'border-light border-2';
        };

        const getCardAnimation = (status) => {
            // Solo las cajas abiertas tienen animación sutil
            return status === 'open' ? 'shadow-lg' : 'shadow-sm';
        };

        const getCardHover = () => {
            // Efecto hover universal con Bootstrap 
            return 'pos-card-hover';
        };

        return `
            <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-4">
                <div class="card h-100 ${getCardAnimation(pos.pos_status)} ${getBorderStyle(pos.pos_status)} ${getCardHover()} position-relative overflow-hidden" 
                    data-pos-id="${pos.id}"
                    style="transition: all 0.3s ease;">
                    
                    <!-- Indicador de estado (barra lateral) -->
                    <div class="position-absolute top-0 start-0 h-100 bg-${statusInfo.colorClass}" 
                        style="width: 4px; z-index: 1;"></div>
                    
                    <!-- Card Header -->
                    <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="bg-${statusInfo.colorClass} rounded-circle d-flex align-items-center justify-content-center" 
                                    style="width: 40px; height: 40px;">
                                    <i class="fas ${statusInfo.icon} text-white"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">${pos.name}</h6>
                                <small class="text-muted">${pos.location_name}</small>
                            </div>
                        </div>
                        ${dropdown_menu}
                    </div>

                    <!-- Card Body -->
                    <div class="card-body">
                        <!-- Estado del POS -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <small class="text-muted fw-medium">Estado:</small>
                                <span class="badge bg-${statusInfo.badgeClass} fs-6 px-3 py-2 rounded-pill">
                                    <i class="fas ${statusInfo.icon} me-1"></i>
                                    ${statusInfo.text}
                                </span>
                            </div>
                            
                            ${pos.description ? `
                                <div class="mb-2">
                                    <small class="text-muted fw-medium">Descripción:</small>
                                    <p class="mb-0 small text-dark">${pos.description}</p>
                                </div>
                            ` : ''}

                            ${(pos.pos_status == "available" || pos.pos_status == "open") ? `
                                <div class="mb-2">
                                    <small class="text-muted fw-medium">Abierta por:</small>
                                    <p class="mb-0 small text-dark">${pos.last_opened_user_name}</p>
                                    <small class="text-muted">${moment(pos.last_opened_datetime.date).format('DD MMM YYYY h:mm A')}</small>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted fw-medium">Usuario actual:</small>
                                    <p class="mb-0 small text-dark">${pos.last_current_user_name}</p>
                                    <small class="text-muted">${moment(pos.last_login_datetime.date).format('DD MMM YYYY h:mm A')}</small>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="card-footer bg-light border-top-0">
                        ${actionBtn}                        
                    </div>
                </div>
            </div>
            
            <style>
            /* Estilos CSS mínimos para el hover (solo si no tienes un archivo CSS) */
            .pos-card-hover:hover {
                transform: translateY(-2px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            }
            </style>
        `;
    };

    
    //
    app.getPosStatusInfo = function(pos) {
        const statusMap = {
            'inactive': {
                text: 'Inactivo',
                icon: 'fa-times-circle',
                colorClass: 'secondary',
                badgeClass: 'secondary'
            },
            'available_initial': {
                text: 'Disponible (inicial)',
                icon: 'fa-check-circle',
                colorClass: 'primary',
                badgeClass: 'primary'
            },
            'available': {
                text: 'Disponible',
                icon: 'fa-check-circle',
                colorClass: 'primary',
                badgeClass: 'primary'
            },
            'open': {
                text: 'Abierta',
                icon: 'fa-unlock',
                colorClass: 'success',
                badgeClass: 'success'
            },
            'unknown': {
                text: 'Desconocido',
                icon: 'fa-question-circle',
                colorClass: 'warning',
                badgeClass: 'warning'
            }
        };
        
        // Retorna el mapeo basado en pos_status, o el default para unknown
        return statusMap[pos.pos_status] || statusMap['unknown'];
    };


    // Funciones de acciones del POS
    app.startPOS = function(pos_id, pos_name) {
        //console.log(pos_id)

        //
        loadModalV2({
            id: "modal-select-user",
            modal_size: "sm",
            data: {
                pos_id,
                pos_register_title: pos_name
            },
            /*onHide: function(){},*/
            html_tmpl_url: "/app/pos/modals/select-user.html?v=" + dynurl(),
            js_handler_url: "/app/pos/modals/select-user.js?v=" + dynurl(),
            onBeforeLoad: function(){
                //disable_btns();
            },
            onInit: function(){
                //
                enable_btns();
            }
        });

    };

    //
    app.continuePOS = function(posId, pos_name) {
        
        if (confirm("continuar vendiendo para punto de venta " + pos_name + "?")){
            //
            $.ajax({
                type: "POST",
                url: app.admin_url + "/pos/main/continue",
                dataType: "json",
                data: JSON.stringify({
                    pos_id: posId
                }),
                contentType: "application/json",
                timeout: 10000,
                success: function(data) {
                    if (data.id) {
                        window.location.href = `/admin/pos/main`;
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
        
    };

    //
    app.btnCloseRegister = function(pos_register_id, pos_user_id) {
        //
        loadModalV2({
            id: "modal-close-register",
            modal_size: "md",
            data: {
                pos_register_id,
                pos_user_id
            },
            /*onHide: function(){},*/
            html_tmpl_url: "/app/pos/modals/close-register.html?v=" + dynurl(),
            js_handler_url: "/app/pos/modals/close-register.js?v=" + dynurl(),
            onBeforeLoad: function(){
                //disable_btns();
            },
            onInit: function(){
                //
                enable_btns();
            }
        });

    };





    // Funciones existentes
    app.init = function(){
        
        // Mostrar preloader
        preload(".section-preloader, .overlay", true);
        $("#pos_list").html("");
        
        $.ajax({
            type: "GET",
            url: app.admin_url + "/pos/items",
            success: function(data) {
                enable_btns();
                preload(".section-preloader, .overlay");
                
                if (data && data.length){
                    app.onPosListReady(data);
                } else {
                    app.onPosListReady([]);
                }
            },
            error: function(xhr, status, error) {
                enable_btns();
                preload(".section-preloader, .overlay");
                console.error("Error: " + error);
                
                // Mostrar error
                $('#pos_list').html(`
                    <div class="col-12">
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h5>Error al cargar las cajas registradoras</h5>
                            <p>No se pudieron cargar las cajas registradoras. Por favor, intente nuevamente.</p>
                            <button class="btn btn-outline-danger" onclick="app.init()">
                                <i class="fas fa-retry me-2"></i>
                                Reintentar
                            </button>
                        </div>
                    </div>
                `);
            }
        });
    };


    //
    $(document).on("reload", function(){
        app.init();
    });

    // Botón de reload
    $('.btnReload').click(function(e) {
        e.preventDefault();
        //
        $(document).trigger("reload");
    });

    // Inicializar al cargar la página
    app.init();



    //
    app.openRegister = function(s_data){
        //
        if ( !(s_data && s_data.pos_id && s_data.user_id) ){
            app.Toast.fire({ icon: 'error', title: "Se requiere el punto de venta y el usuario para abrir caja" });
            return;
        }
        //
        loadModalV2({
            id: "modal-open-register",
            modal_size: "md",
            data: s_data,
            /*onHide: function(){},*/
            html_tmpl_url: "/app/pos/modals/open-register.html?v=" + dynurl(),
            js_handler_url: "/app/pos/modals/open-register.js?v=" + dynurl(),
            onBeforeLoad: function(){
                //disable_btns();
            },
            onInit: function(){
                //
                enable_btns();
            }
        });
    }




})(jQuery);