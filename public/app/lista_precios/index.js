(function ($) {
    'use strict';



    //
    app.createSection({
        section_title: "Tipos de Precio",
        section_title_singular: "Tipo de Precio",
        modalAddHtmlName: "add-record.html",
        modalEditHtmlName: "edit-record.html",
        modalAddId: "edit-record",
        scripts_path: "/app/tipos_precios",
        endpoint_url: app.admin_url + "/tipos-precios",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){ return safeNulValue(obj.clave) + " - " + safeNulValue(obj.descripcion); }},
                {"data" : function(obj){
                        //
                        var tipo_precio_id = parseInt(obj.tipo_precio_id);
                        //
                        if ( tipo_precio_id === 1 ){
                            //
                            var str_tipo_sum_rest = (obj.es_porcentaje === "s") ? "Mas" : "Menos";
                            if (obj.es_porcentaje){
                                return str_tipo_sum_rest + " " + parseInt(obj.valor) + "% (porciento) al precio base";
                            }
                            else {
                                return str_tipo_sum_rest + " $" + parseFloat(obj.valor) + " al precio base";
                            }
                        }
                        //
                        else if ( tipo_precio_id === 3 ){
                            return "Sin Costo";
                        }
                        //
                        return "Mantiene Precio Base";
                    }},
                {"data" : function(obj){
                        return "Desde <strong>" + obj.edad_minima + "</strong> hasta <strong>" + obj.edad_maxima + " años</strong>";
                    }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active); }},
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
            $('#active').attr("checked", true);

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
                str_tipo_info = "Porcentaje: %" + section_data.valor;
            }
            else {
                str_tipo_info = "Monto: $" + section_data.valor;
            }
            $("#tipo_info").html(str_tipo_info);


            //
            var str_edades_info = "Edad desde: " + section_data.edad_minima + " hasta " + section_data.edad_maxima + " años";
            $("#edades_info").html(str_edades_info);



            //
            if (section_data.active){
                $('#active').attr("checked", true);
            } else {
                $('#active').attr("checked", false);
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
            $("input[name=tipo_precio_id]").click(function(){

                //
                var tipo_precio_id_val = $("input[name=tipo_precio_id]:checked").val();
                $("#tipo_precio_container").hide();

                //
                if ( parseInt(tipo_precio_id_val) === 1 ){
                    $("#tipo_precio_container").show();
                }

            });



            //
            $('#descripcion').focus();
        },
        beforeSubmit: function(arr){
            //
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);