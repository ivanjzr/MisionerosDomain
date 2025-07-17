(function ($) {
    'use strict';



    /*
    *
    * SECCION PROMOCIONES
    *
    * */
    app.createSection({
        section_title: "Promos",
        section_title_singular: "Promo",
        modalAddHtmlName: "add-record.html",
        modalEditHtmlName: "edit-record.html",
        modalAddId: "edit-record",
        scripts_path: "/app/promos",
        endpoint_url: app.admin_url + "/promos",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){ return safeNulValue(obj.clave) + " - " + safeNulValue(obj.descripcion); }},
                {"data" : function(obj){
                        if (obj.es_porcentaje){
                            return "Porcentaje: %" + obj.valor;
                        }
                        else {
                            return "Monto: $" + obj.valor;
                        }
                    }},
                {"data" : function(obj){
                        return "Del <strong>" + fmtDateSpanish(obj.fecha_hora_inicio.date, true) + "</strong> al <strong>" + fmtDateSpanish(obj.fecha_hora_fin.date, true) + "</strong>";
                    }},
                {"name": "enabled_pdv", "data" : function(obj){ return fmtActiveV2(obj.enabled_pdv); }},
                {"name": "enabled_apps", "data" : function(obj){ return fmtActiveV2(obj.enabled_apps); }},
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
                { "targets": [0, 3, 5],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){


            //
            $('#es_porcentaje_porc').attr("checked", true);
            $('#enabled_pdv').attr("checked", true);

        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){

            //
            $('#descripcion').val(section_data.descripcion);
            $('#clave').val(section_data.clave);
            $('#valor').val(section_data.valor);


            //
            var str_tipo_info = "";
            if (section_data.es_porcentaje){
                str_tipo_info = "Descuento Por Porcentaje: %" + section_data.valor;
            }
            else {
                str_tipo_info = "Descuento Por Monto: $" + section_data.valor;
            }
            $("#tipo_info").html(str_tipo_info);


            



            //
            if (section_data.enabled_pdv){
                $('#enabled_pdv').attr("checked", true);
            } else {
                $('#enabled_pdv').attr("checked", false);
            }
            if (section_data.enabled_apps){
                $('#enabled_apps').attr("checked", true);
            } else {
                $('#enabled_apps').attr("checked", false);
            }

            


        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){



            
            //
            $('#fecha_hora_inicio').datetimepicker({
                autoclose: true,
                locale: 'es-mx',
                format: 'DD/MM/YYYY, hh:mm A',
                stepping: 15,
                icons: { time: 'far fa-clock' }
            });
            //
            $('#fecha_hora_fin').datetimepicker({
                autoclose: true,
                locale: 'es-mx',
                format: 'DD/MM/YYYY, hh:mm A',
                stepping: 15,
                icons: { time: 'far fa-clock' }
            });


            //
            if ( section_data && section_data.fecha_hora_inicio ){
                //
                var fecha_hora_inicio = moment(section_data.fecha_hora_inicio.date).format("DD/MM/YYYY hh:mm");
                console.log(fecha_hora_inicio);
                $('#fecha_hora_inicio').datetimepicker('date', fecha_hora_inicio);
            }

            //
            if ( section_data && section_data.fecha_hora_fin ){
                //
                var fecha_hora_fin = moment(section_data.fecha_hora_fin.date).format("DD/MM/YYYY hh:mm");
                console.log(fecha_hora_fin);
                $('#fecha_hora_fin').datetimepicker('date', fecha_hora_fin);
            }



            

            //
            $('#descripcion').focus();

            if (section_data && section_data.id){
                $("#modal-title").text("Editar Promo - " + section_data.clave);
            }
        },
        beforeSubmit: function(arr){

            //
            var fecha_hora_inicio = $('#fecha_hora_inicio').datetimepicker('viewDate');
            //
            arr.push({
                name: "fecha_hora_inicio",
                value: fecha_hora_inicio.format("YYYY-MM-DD HH:mm")
            });


            //
            var fecha_hora_fin = $('#fecha_hora_fin').datetimepicker('viewDate');
            //
            arr.push({
                name: "fecha_hora_fin",
                value: fecha_hora_fin.format("YYYY-MM-DD HH:mm")
            });
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);