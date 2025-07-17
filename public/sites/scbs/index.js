$(document).ready(function() {
    // Variables globales
    let allBuses = [];
    let allMakes = [];
    let allModels = [];
    let allYears = [];

    // Función para obtener las marcas
    function getMakes(){
        $.ajax({
            type: 'GET',
            url: "/public/makes/list",
            success: function(data){
                console.log('Makes loaded:', data);
                allMakes = data;
                populateMakesFilter();
            },
            error: function(xhr, status, error){
                console.error('Error loading makes:', error);
            }
        });
    }
    
    // Función para obtener los modelos
    function getModels(make_id){
        if (make_id){
            $.ajax({
                type: 'GET',
                url: "/public/models/list?mkid=" + make_id,
                success: function(data){
                    console.log('Models loaded:', data);
                    allModels = data;
                    populateModelsFilter();
                },
                error: function(xhr, status, error){
                    console.error('Error loading models:', error);
                }
            });
        } else {
            // Si no hay make_id, limpiar los modelos
            allModels = [];
            populateModelsFilter();
        }        
    }

    // Función para obtener los años
    function getYears(){
        $.ajax({
            type: 'GET',
            url: "/public/avail-years",
            success: function(data){
                console.log('Years loaded:', data);
                // Extraer años únicos y ordenarlos
                allYears = data;
                populateYearsFilter();
            },
            error: function(xhr, status, error){
                console.error('Error loading years:', error);
            }
        });
    }

    // Función para obtener los buses
    function getBuses(make_id, model_id, year){
        let qs = "?1=1"; // Query string base
        
        if (make_id) qs += "&mkid=" + make_id;
        if (model_id) qs += "&moid=" + model_id;
        if (year) qs += "&yr=" + year;

        $.ajax({
            type: 'GET',
            url: "/public/buses" + qs,
            success: function(data){
                console.log('Buses loaded:', data);
                allBuses = data;
                displayBuses(data);
            },
            error: function(xhr, status, error){
                console.error('Error loading buses:', error);
                displayBuses([]); // Mostrar mensaje de error
            }
        });
    }

    // Poblar el filtro de marcas
    function populateMakesFilter() {
        const $brandFilter = $('#brandFilter');
        $brandFilter.find('option:not(:first)').remove(); // Mantener "All Brands"
        
        allMakes.forEach(make => {
            $brandFilter.append(`<option value="${make.id}">${make.nombre}</option>`);
        });
    }

    // Poblar el filtro de modelos
    function populateModelsFilter() {
        const $modelFilter = $('#modelFilter');
        $modelFilter.find('option:not(:first)').remove(); // Mantener "All Models"
        
        allModels.forEach(model => {
            $modelFilter.append(`<option value="${model.id}">${model.nombre}</option>`);
        });
    }

    // Poblar el filtro de años
    function populateYearsFilter() {
        const $yearFilter = $('#yearFilter');
        $yearFilter.find('option:not(:first)').remove(); // Mantener "All Years"
        
        allYears.forEach(item => {
            $yearFilter.append(`<option value="${item.year}">${item.year}</option>`);
        });
    }

    // Mostrar los buses
    function displayBuses(buses) {
        const $grid = $('#busesGrid');
        $grid.empty();

        if (buses.length === 0) {
            $grid.html(`
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fs-1 mb-3" style="color: var(--medium-gray);"></i>
                    <h3 style="color: var(--medium-gray);">No buses found</h3>
                    <p style="color: var(--medium-gray);">Try adjusting your filters to see more results.</p>
                </div>
            `);
            return;
        }

        buses.forEach(bus => {
            // Preparar features
            const features = bus.features ? bus.features.slice(0, 3).map(feature => 
                `<span class="badge feature-badge me-1 mb-1">${feature.feature_name}</span>`
            ).join('') : '';

            // Preparar imágenes
            const mainImage = bus.orig_img_url || '/images/bus-placeholder.jpg';
            let thumbnails = '';
            
            if (bus.images && bus.images.length > 0) {
                thumbnails = bus.images.slice(0, 5).map((img, idx) => 
                    `<img src="${img.thumb_img_url}" class="thumbnail-img" style="width: 80px; height: 55px; object-fit: cover;" data-img="${img.orig_img_url}" alt="Thumbnail ${idx + 1}">`
                ).join('');
            }

            // Formatear precio
            const formattedPrice = parseFloat(bus.precio).toLocaleString('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });

            const busCard = `
                <div class="col-lg-6 col-md-6">
                    <div class="card bus-card h-100 shadow-sm">
                        <div class="position-relative">
                            <img src="${mainImage}" class="card-img-top bus-main-image" style="height: 400px; object-fit: cover;" alt="${bus.make} ${bus.model}">
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-success fs-6 fw-normal px-3 py-2" style="border-radius: 20px;">${bus.year}</span>
                            </div>
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary fs-6 fw-normal px-3 py-2" style="border-radius: 20px;">${bus.make}</span>
                            </div>
                        </div>
                        
                        ${thumbnails ? `
                        <div class="thumbnail-container p-2">
                            <div class="d-flex gap-2 justify-content-center">
                                ${thumbnails}
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0 fw-bold" style="color: var(--dark-gray);">${bus.make} ${bus.model}</h5>
                                <span class="fs-5 fw-bold" style="color: var(--accent-orange);">${formattedPrice}</span>
                            </div>
                            
                            <p class="card-text text-muted mb-3" style="font-size: 0.9rem; line-height: 1.5;">
                                ${bus.description || `${bus.year} ${bus.make} ${bus.model} in excellent condition. Perfect for your transportation needs.`}
                            </p>
                            
                            ${features ? `
                            <div class="mb-3">
                                ${features}
                                ${bus.features && bus.features.length > 3 ? `<span class="text-muted">+${bus.features.length - 3} more</span>` : ''}
                            </div>
                            ` : ''}
                            
                            <div class="mt-auto">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button class="btn btn-contact text-white w-100" onclick="contactAboutBus(${bus.id})">
                                            <i class="fas fa-phone me-1"></i>Contact
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button class="btn btn-details w-100" onclick="viewDetails(${bus.id})">
                                            <i class="fas fa-info-circle me-1"></i>Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $grid.append(busCard);
        });

        // Setup thumbnail click events
        $('.thumbnail-img').on('click', function() {
            const newSrc = $(this).data('img');
            const $mainImg = $(this).closest('.card').find('.bus-main-image');
            $mainImg.attr('src', newSrc);
            
            $(this).siblings().removeClass('border-primary');
            $(this).addClass('border-primary');
        });
    }

    // Configurar event listeners
    function setupEventListeners() {
        // Cuando cambie la marca, cargar modelos
        $('#brandFilter').on('change', function() {
            const makeId = $(this).val();
            $('#modelFilter').val(''); // Reset model filter
            
            if (makeId) {
                getModels(makeId);
            } else {
                allModels = [];
                populateModelsFilter();
            }
            
            filterBuses();
        });
        
        // Cuando cambien modelo o año
        $('#modelFilter, #yearFilter').on('change', filterBuses);
        
        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#brandFilter, #modelFilter, #yearFilter').val('');
            allModels = [];
            populateModelsFilter();
            getBuses(null, null, null); // Cargar todos los buses
        });
    }

    // Filtrar buses
    function filterBuses() {
        const brand = $('#brandFilter').val();
        const model = $('#modelFilter').val();
        const year = $('#yearFilter').val();
        
        getBuses(brand || null, model || null, year || null);
    }

    // Funciones globales para los botones
    window.contactAboutBus = function(busId) {
        const bus = allBuses.find(b => b.id == busId);
        if (bus) {
            const message = `Hi! I'm interested in the ${bus.year} ${bus.make} ${bus.model}. Could you provide more information?`;
            // Puedes cambiar esto por WhatsApp o email si prefieres
            window.location.href = `tel:915-613-9899`;
        }
    };

    window.viewDetails = function(busId) {
        const bus = allBuses.find(b => b.id == busId);
        if (bus) {
            let details = `Details for ${bus.make} ${bus.model}:\n\n`;
            details += `Year: ${bus.year}\n`;
            details += `Price: ${parseFloat(bus.precio).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}\n`;
            details += `SKU: ${bus.sku || 'N/A'}\n`;
            details += `Bus Code: ${bus.bus_code || 'N/A'}\n\n`;
            
            if (bus.features && bus.features.length > 0) {
                details += `Features:\n${bus.features.map(f => '• ' + f.feature_name).join('\n')}\n\n`;
            }
            
            if (bus.description) {
                details += `Description: ${bus.description}`;
            }
            
            alert(details);
        }
    };

    // Inicializar la página
    function init() {
        // Cargar imagen hero
        $('#heroImage').attr('src', '/images/img-bus-1.png');
        
        // Cargar datos iniciales
        getMakes();
        getYears();
        getBuses(null, null, null); // Cargar todos los buses
        
        // Configurar event listeners
        setupEventListeners();
    }

    // Inicializar todo
    init();
});