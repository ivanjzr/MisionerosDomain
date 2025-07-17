(function ($) {
    'use strict';


    //
    app.createSection({
        section_title: "Features",
        section_title_singular: "Feature",
        scripts_path: "/app/buses/cat_features",
        endpoint_url: app.admin_url + "/buses/features",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "nombre", "data" : "nombre"},
                {"data" : function(obj){ return safeNulValue(obj.color); }},
                {"data" : function(obj){ return safeNulValue(obj.description); }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 4, 5],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            hdrBtnsSearch: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){

            //
            $('#active').attr("checked", true);

        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){

            //
            $('#nombre').val(section_data.nombre);
            $('#color').val(section_data.color);
            $('#description').val(section_data.description);

            //
            if (section_data.active){
                $('#active').attr("checked", true);
            } else {
                $('#active').attr("checked", false);
            }

        },
        
        onAddEditReady: function(section_data){
    // def focus
    $("#nombre").focus();
    
    // Preview functionality con jQuery
    $('#color').on('change', function() {
        var color = $(this).val();
        var $previewCard = $('#color-preview-card');
        var $tagPreview = $('#tag-preview');
        
        if (color) {
            $previewCard.show();
            
            // Quitar todas las clases de color
            $tagPreview.removeClass('bg-primary bg-secondary bg-success bg-danger bg-warning bg-info bg-light bg-dark text-dark text-white');
            
            // Mapear colores reales con CSS inline
            var colorStyles = {
                'red': { background: '#dc3545', color: 'white' },      // Rojo
                'green': { background: '#28a745', color: 'white' },    // Verde  
                'yellow': { background: '#ffc107', color: 'black' },   // Amarillo
                'blue': { background: '#007bff', color: 'white' },     // Azul
                'purple': { background: '#6f42c1', color: 'white' },   // Morado
                'orange': { background: '#fd7e14', color: 'white' },   // Naranja
                'pink': { background: '#e83e8c', color: 'white' },     // Rosa
                'gray': { background: '#6c757d', color: 'white' }      // Gris
            };
            
            // Aplicar clases base
            $tagPreview.addClass('badge fs-6 px-3 py-2');
            
            // Aplicar colores inline
            if (colorStyles[color]) {
                $tagPreview.css({
                    'background-color': colorStyles[color].background,
                    'color': colorStyles[color].color
                });
            }
            
        } else {
            $previewCard.hide();
        }
    });

    $('#nombre').on('input', function() {
        var text = $(this).val() || 'Ejemplo de Tag';
        $('#preview-text').text(text);
    });
    
    // Trigger initial preview if editing
    if (section_data && section_data.color) {
        $('#color').trigger('change');
    }
    if (section_data && section_data.nombre) {
        $('#preview-text').text(section_data.nombre);
    }
},
        onGridReady: function(opts){


        }
    });




})(jQuery);