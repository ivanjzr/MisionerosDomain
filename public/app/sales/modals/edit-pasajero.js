define(function(){
    function moduleReady(modal, edit_data){
        console.log("------edit pasajero modal: ", edit_data);





        
        var dob = moment(edit_data.passanger_dob, "YYYY-MM-DD HH:mm");
        //
        var psngr_anio = dob.year();
        var psngr_mes = dob.month() + 1;
        var psngr_dia = dob.date();
        //console.log(dob, psngr_anio, psngr_mes, psngr_dia);


        //
        $("#passanger_name").val(edit_data.passanger_name);
        


        //
        function calcEdad(){

            //
            $("#passanger_age").val("");
            $("#clasificacion_info").val("");


            // 
            $.ajax({
                type:'POST',
                url: app.public_url + "/utils/calc-dob",
                data: $.param({
                    ruta_id: edit_data.ruta_id,
                    salida_id: edit_data.salida_id,
                    origen_ciudad_id: edit_data.origen_ciudad_id,
                    destino_ciudad_id: edit_data.destino_ciudad_id,
                    dob: vent.getSelDob().format("YYYY-MM-DD")
                }),
                beforeSend: function (xhr) {
                    //
                },
                success:function(response){
                    //console.log(response.data);

                    //
                    if ($.isNumeric(response.passanger_age) && (parseInt(response.passanger_age) === 0 || response.passanger_age) ){
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
                var str_selected_year = (parseInt(psngr_anio) === parseInt(itm_yr)) ? "selected" : "";
                //
                $("<option "+str_selected_year+" />")
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
                var str_selected_month = (parseInt(psngr_mes) === parseInt(itm_mth.val)) ? "selected" : "";

                //
                $("<option "+str_selected_month+" />")
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
                var str_selected_dia = (parseInt(psngr_dia) === parseInt(itm_val)) ? "selected" : "";
                //
                $("<option "+str_selected_dia+" />")
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
                    passanger_name: $("#passanger_name").val(),
                    passanger_dob: vent.getSelDob().format("YYYY-MM-DD"),
                    passanger_age: $("#passanger_age").val(),
                    clasificacion_info: $("#clasificacion_info").val(),
                    precio_base: $("#precio_base").val(),
                    precio_total: $("#precio_total").val(),
                    calc_info: $(".calc_info").val(),
                }
                //console.log("---post_data: ", post_data); return;                
                
                //
                var arr_sale_items = getLSItem("arr_sale_items");
                console.log("---arr_sale_items: ", arr_sale_items);
                //
                $.each(arr_sale_items, function(idx, item){
                    //
                    if (parseInt(item.id) === parseInt(edit_data.id)){
                        arr_sale_items[idx].passanger_name = post_data.passanger_name;
                        arr_sale_items[idx].passanger_dob = post_data.passanger_dob;
                        arr_sale_items[idx].passanger_age = post_data.passanger_age;
                        arr_sale_items[idx].tipo_precio_descripcion = post_data.clasificacion_info;                        
                        arr_sale_items[idx].sub_total = post_data.precio_base;
                        arr_sale_items[idx].total = post_data.precio_total;
                        arr_sale_items[idx].calc_info = post_data.calc_info;
                    }
                });
                //
                localStorage.setItem("arr_sale_items", JSON.stringify(arr_sale_items));

                //
                app.Toast.fire({icon: 'success', title: "Paajero Editado correctamente"});
                //
                $("#modal-add-pasajero").find('.modal').modal("hide");

                //
                vent.actualizarOcupacion(app.temp_sale_id);

            }
        });





    }
    return {init: moduleReady}
});