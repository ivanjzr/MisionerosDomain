define(function(){

    function moduleReady(modal, salida_ocupacion){
    //function moduleReady(salida_ocupacion){
        console.log("------salida_ocupacion: ", salida_ocupacion);









        var view_detailed_info = false;


        function generarColorPastel() {
            // Generar componentes RGB aleatorios con valores entre 150 y 255
            const r = Math.floor(Math.random() * 56) + 200;
            const g = Math.floor(Math.random() * 56) + 200;
            const b = Math.floor(Math.random() * 56) + 200;

            // Devolver el color en formato RGB
            return `rgb(${r},${g},${b})`;
        }


        function generarColorBorde(color) {
            // Obtener los componentes RGB del color generado para el fondo
            const valores = color.match(/\d+/g).map(Number);

            // Reducir ligeramente los valores RGB para obtener un color más oscuro
            const r = Math.max(valores[0] - 20, 0);
            const g = Math.max(valores[1] - 20, 0);
            const b = Math.max(valores[2] - 20, 0);

            // Devolver el color para el borde en formato RGB
            return `rgb(${r},${g},${b})`;
        }



        //
        app.onDestinosReady = function(arr_destinos, arr_seats, arr_seats_2do_piso){



            //
            if ( arr_destinos && arr_destinos.length && arr_seats && arr_seats.length ){

                //
                var str_tbl = "<table class='tablev2 table-bordered'>";


                /*
                * Pintamos el Header
                * */
                //
                str_tbl += "<tr>";
                // width:25px;
                str_tbl += "<th style=''>#</th>";
                //
                $.each(arr_destinos, function(idx, item_dest){
                    //console.log(idx, item);
                    // width:100px;
                    str_tbl += "<th style=''>" + item_dest.ciudad_nombre + " (" + item_dest.orden_num + ")</th>";
                });
                str_tbl += "</tr>";




                function retSeats(arr_seats, is_segundo_piso){


                    //
                    var str_seats = "";

                    //
                    if (is_segundo_piso){
                        //
                        var cant_destinos = ( arr_destinos.length + 1 );
                        str_seats += "<tr>";
                        //
                        str_seats += "<th colspan='" + cant_destinos + "' style='padding-left:40px;font-weight: normal;font-size: 24px;'> 2do Piso </th>";
                        //
                        str_seats += "</tr>";
                    }


                    //
                    $.each(arr_seats, function(idx, item){
                        //console.log(idx, item);


                        //
                        var add_row = true;


                        //
                        const colorFondo = generarColorPastel();
                        const colorBorde = generarColorBorde(colorFondo);
                        //console.log(colorFondo, colorBorde);


                        var qty_seat_ocup = item.seat_ocupacion.length;


                        //
                        $.each(item.seat_ocupacion, function(idx3, seat_ocup){
                            //console.log(item.seat_number, item_dest.orden_num, item_dest.ciudad_nombre, seat_ocup);


                            //
                            var str_first_last_border = "";


                            //
                            str_seats += "<tr>";

                            if (idx3 === 0 ){
                                //
                                str_first_last_border = "border-top:2px solid "+colorBorde+";";
                                str_seats += "<td rowspan='"+qty_seat_ocup+"' style='text-align: center;vertical-align: middle;font-weight: bolder;'>" + item.seat_number + "</td>";
                            }
                            //
                            else if ( (idx3+1) === qty_seat_ocup ){
                                //
                                str_first_last_border = "border-bottom:2px solid "+colorBorde+";";
                            }



                            //
                            add_row = false;
                            //view_detailed_info = true;


                            //
                            $.each(arr_destinos, function(idx2, item_dest){
                                //console.log(idx, item);

                                //
                                if ( (item_dest.orden_num >= seat_ocup.origen_orden_num) && (item_dest.orden_num <= seat_ocup.destino_orden_num) ){

                                    //
                                    if (view_detailed_info){
                                        //
                                        if (seat_ocup.origen_ciudad_id === item_dest.ciudad_id){
                                            str_seats += "<td style='background-color:"+colorFondo+";'>" + seat_ocup.id + " - " + seat_ocup.origen_info + " (" + seat_ocup.origen_orden_num + ")</td>";
                                        }
                                        else if (seat_ocup.destino_ciudad_id === item_dest.ciudad_id){
                                            str_seats += "<td style='background-color:"+colorFondo+";'>" + seat_ocup.destino_info + " (" + seat_ocup.destino_orden_num + ")</td>";
                                        } else {
                                            str_seats += "<td style='background-color:"+colorFondo+";'> (" + item_dest.orden_num + ")</td>";
                                        }
                                    } else {
                                        //
                                        var colspan = (seat_ocup.destino_orden_num - seat_ocup.origen_orden_num) + 1;
                                        //
                                        if (item_dest.orden_num === seat_ocup.origen_orden_num){

                                            //
                                            var div_content = "<div style='background-color: "+colorFondo+";font-size: 12px;font-weight: bold;padding:2px;'>" + seat_ocup.passanger_name + " (" + seat_ocup.origen_info + " - " + seat_ocup.destino_info + ")</div>";
                                            str_seats += "<td colspan='"+colspan+"' style='"+str_first_last_border+"'>" + div_content + "</td>";

                                        }
                                    }


                                } else {

                                    //
                                    if (view_detailed_info){
                                        str_seats += "<td style='"+str_first_last_border+"'>" + item_dest.ciudad_nombre + " (" + item_dest.orden_num + ")</td>";
                                    } else {
                                        str_seats += "<td style='"+str_first_last_border+"'>&nbsp;</td>";
                                    }

                                }

                            });


                            //
                            str_seats += "</tr>";

                        });



                        //
                        if (add_row){
                            //
                            str_seats += "<tr>";
                            str_seats += "<td style='text-align: center;vertical-align: middle;'>" + item.seat_number + "</td>";
                            //
                            $.each(arr_destinos, function(idx2, item_dest){
                                str_seats += "<td></td>";
                            });
                            str_seats += "</tr>";
                        }


                    });

                    //
                    return str_seats;
                }







                //
                str_tbl += retSeats(arr_seats, false);


                //
                if (arr_seats_2do_piso && arr_seats_2do_piso.length){
                    str_tbl += retSeats(arr_seats_2do_piso, true);
                }



                //
                str_tbl += "</table>";
                //
                $("#grid_destinos").html(str_tbl);

            }
        }






        //
        app.loadData = function(){


            //
            $("#grid_destinos").html("");


            //
            var the_url = app.admin_url + "/salidas/" + salida_ocupacion.salida_id + "/ocupacion";
            //
            $.ajax({
                type:'GET',
                url: the_url,
                beforeSend: function (xhr) {
                    disable_btns();
                },
                success:function(data){

                    enable_btns();

                    //
                    if (data && data.arr_destinos && data.arr_seats){
                        app.onDestinosReady(data.arr_destinos, data.arr_seats, data.arr_seats_2do_piso);
                    }

                    //
                    if (data.error) {
                        app.Toast.fire({ icon: 'error', title: data.error});
                    }
                },
                error: function(){
                    //
                    enable_btns();
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });

        }

        //
        app.loadData();



        //
        $('#modal-title').text("Salida - " + momentFormat(salida_ocupacion.fecha_hora_salida.date, "MMM DD YYYY h:mm A") + " - " + momentFormat(salida_ocupacion.fecha_hora_llegada.date, "MMM DD YYYY h:mm A"));

    }
    return {init: moduleReady}
});