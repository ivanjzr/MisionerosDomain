define(function(){
    function moduleReady(modal, section_data){
        console.log("------add pasajero modal: ", section_data);






        

        function calcEdad(){

            //
            $("#passanger_age").val("");
            $("#clasificacion_info").val("");




            // 
            $.ajax({
                type:'POST',
                url: app.public_url + "/utils/calc-dob",
                data: $.param({
                    ruta_id: section_data.ruta_id,
                    salida_id: section_data.id,
                    origen_ciudad_id: section_data.origen_ciudad_id,
                    destino_ciudad_id: section_data.destino_ciudad_id,
                    dob: vent.getSelDob().format("YYYY-MM-DD")
                }),
                beforeSend: function (xhr) {
                    //
                },
                success:function(response){
                    //console.log(response.data);

                    //
                    if ( $.isNumeric(response.passanger_age) ){
                        $("#passanger_age").val(response.passanger_age);
                    }

                    if ( response.id  ){
                        var str_clasificacion_info = response.clave + " - " + response.descripcion;
                        $("#clasificacion_info").val(str_clasificacion_info);
                    }

                    //
                    $("#precio_base").val(response.precio_salida);
                    $("#precio_total").val(response.new_precio);
                    $(".calc_info")
                        .html(response.calc_info)
                        .val(response.calc_info);

                    //
                    if (response.error){
                        //
                        app.Toast.fire({ icon: 'error', title: response.error});
                    }
                },
                error: function(){
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });
        }


        function initDob(opts){

            //
            var mmt = moment();
            var end_val = parseInt(mmt.format("YYYY"));
            //
            var start_val = (end_val - 90);
            //
            var arr_years = getArrRange(start_val, end_val);
            //
            $.each(arr_years, function(idx, itm_yr){
                //console.log(idx, itm_yr);
                //
                $("<option />")
                    .val(itm_yr)
                    .text(itm_yr)
                    .appendTo("#dob_anio");
            });
            //
            $("#dob_anio").unbind("change").bind("change", function(){
                //
                opts.onChange();
            });

            //
            $.each(vent.arr_months, function(idx, itm_mth){
                //console.log(idx, itm_mth);
                //
                $("<option />")
                    .val(itm_mth.val)
                    .text(itm_mth.text)
                    .appendTo("#dob_mes");
            });
            //
            $("#dob_mes").unbind("change").bind("change", function(){
                //
                opts.onChange();
            });

            //
            opts.onReady();
        }



        //
        function getDobDaysRange(onReady){
            //
            $("#dob_dia").html("");

            //
            var dob_anio = $("#dob_anio").val();
            var dob_mes = $("#dob_mes").val();

            //
            const primerDia = moment(`${dob_anio}-${dob_mes}-01`, 'YYYY-MM-DD');
            const ultimoDia = primerDia.clone().endOf('month');

            //
            var start_val = 1;
            const end_val = ultimoDia.date();
            //console.log(primerDia, ultimoDia, end_val);

            //
            var arr_vals = getArrRange(start_val, end_val, true);
            //
            $.each(arr_vals, function(idx, itm_val){
                //console.log(idx, itm_yr);
                //
                $("<option />")
                    .val(itm_val)
                    .text(itm_val)
                    .appendTo("#dob_dia");
            });
            //
            $("#dob_dia").unbind("change").bind("change", function(){
                //
                calcEdad();
            });
            //
            calcEdad();
        }



        //
        initDob({
            onReady: function(){
                //
                getDobDaysRange();
            },
            onChange: function(){
                //
                getDobDaysRange();
            }
        });






        //
        $('#form_add_passanger').validate();
        //
        $('#form_add_passanger').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_add_passanger').valid() ) {

                //
                var post_data = {
                    visitor_id: app.visitor_id,
                    temp_sale_id: app.temp_sale_id,                    
                    ruta_id: section_data.ruta_id,
                    salida_id: section_data.id,
                    lugar_id: section_data.lugar_id,
                    origen_ciudad_id: section_data.origen_ciudad_id,
                    destino_ciudad_id: section_data.destino_ciudad_id,
                    passanger_name: $("#passanger_name").val(),
                    passanger_dob: vent.getSelDob().format("YYYY-MM-DD"),
                }
                //console.log("---post_data: ", post_data); return;

                //
                $.ajax({
                    type:'POST',
                    url: app.public_url + "/utils/apartar",
                    dataType: "json",
                    data: JSON.stringify(post_data),
                    beforeSend: function( xhr ) {
                        //xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
                        disable_btns();
                        preload(true);
                    },
                    contentType: "application/json",
                    success:function(response){
                        //console.log(response); return;
                        //
                        enable_btns();

                        //
                        if (response && response.id ){
                                                    
                            //
                            var arr_sale_items = getLSItem("arr_sale_items");
                            arr_sale_items = (arr_sale_items && arr_sale_items.length) ? arr_sale_items : [];
                            //console.log("---arr_sale_items: ", arr_sale_items);
                            arr_sale_items.push(response);
                            //
                            localStorage.setItem("arr_sale_items", JSON.stringify(arr_sale_items));



                            //
                            app.Toast.fire({icon: 'success', title: "Pasajero Agregado correctamente"});
                            $("#modal-add-pasajero").find('.modal').modal("hide");
                            $("#modal-display-bus").find('.modal').modal("hide");
                            //
                            vent.clearCounters();
                            vent.clearCounters2();
                            //
                            vent.updateShowBtn();
                            vent.showSaleModal();
                            
                            //$(document).trigger("loadBusSeats", []);


                        }
                        //
                        else if (response.error){
                            app.Toast.fire({ icon: 'error', title: response.error});
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }

                    },
                    error: function(){
                        enable_btns();
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });

                    }
                });






            }
        });





    }
    return {init: moduleReady}
});