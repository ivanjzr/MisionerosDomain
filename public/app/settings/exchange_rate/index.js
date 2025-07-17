(function ($) {
    'use strict';


    function parseMonedaInfo(item){
        return item.nombre + " - " + item.moneda + " (" + item.moneda_abrev + ")"
    }


    //
    app.createSection({
        section_title: "Tipos de cambio",
        section_title_singular: "tipo de cambio",
        scripts_path: "/app/settings/exchange_rate",
        endpoint_url: app.admin_url + "/exchange-rate",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){ return parseMonedaInfo(obj);}},
                {"name" : "tipo_cambio", "data" : "tipo_cambio"},
                {"name": "datetime_created", "data" : function(obj){ 
                    return moment(obj.datetime_created.date).format('DD/MM/YY hh:mm A'); 
                }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-view' data-info='"+data_info+"'><i class='fas fa-folder'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0,1,2,3,4,5],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]]
        },
        onAddReady: function(data){
            
            //
            loadSelectAjax({
                id: "#sys_pais_id",
                url: app.public_url + "/paises/list?id=" + app.ID_PAIS_EU,
                parseFields: function(item){
                    return parseMonedaInfo(item);
                },
                saveValue: true,
                default_value: app.ID_PAIS_EU,
                enable: true
            });

            //            
            $('#sys_pais_id').show();           

        },
        onEditReady: function(section_data){
            
            
            $('#moneda_info')
                .text(parseMonedaInfo(section_data))
                .show();


            //
            $("#tipo_cambio").val(section_data.tipo_cambio);
        },
        onAddEditReady: function(section_data){
                        
            //
            $('#tipo_cambio').focus();

        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);