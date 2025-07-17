define(function(){
    function moduleReady(modal, selected_seat){
        console.log("------selected_seat: ", selected_seat);



        //
        app.loadBusSeats = function(results){





            // 
            var item_el = app.addItem(results, false);
            $("#tbl_body_results2").append(item_el);



            //
            var bus_results = results.bus_results;
            console.log("***bus_results: ", bus_results);


            //
            app.seats = $.loadSeats({
                data: bus_results,
                bus1stFloorId: "#bus_1st_floor",
                bus2ndFloorId: "#bus_2nd_floor",
                btnReloadId: "#btnReloadSeats",
                hideColsAndRows: true,
                bindOnActiveSeats: true,
                onSelectSpace: function(options, obj, data){
                    //console.log(options, obj, data);

                    results.seats = app.seats;
                    results.lugar_id = data.id;

                    //
                    loadModalV2({
                        id: "modal-add-pasajero",
                        modal_size: "lg",
                        data: results,
                        html_tmpl_url: "/app/sales/modals/add-edit-pasajero.html?v=" + dynurl(),
                        js_handler_url: "/app/sales/modals/add-pasajero.js?v=" + dynurl(),
                        onBeforeLoad: function(){
                            disable_btns();
                        },
                        onInit: function(){

                            //
                            enable_btns();

                            // 
                            $('#modal-title-2').html("Asignar Asiento #" + data.seat_number);
                            $('.btnAddEditPasajero').html("<i class='fas fa-plus'></i> Agregar");

                        }
                    });


                },
                onBeforeLoad: function(options, obj){
                    //
                    $("#autobus_info").html("");
                    $("#spacios_info").html("");
                },
                onReady: function(options, obj, data){

                    //
                    if (data && data.spacios_info){
                        //
                        var str_spacios_info = "";
                        //
                        $.each(data.spacios_info, function(idx, item_spacio){
                            str_spacios_info += "" + item_spacio.espacio_tipo + "&nbsp;<span style='display:inline-block;width:15px;height:15px;background-color:" + item_spacio.seat_hex_color + "'></span><br />";
                        });
                        //
                        $("#spacios_info").html(str_spacios_info);
                    }
                    //
                    if (data && data.bus_info){

                        //
                        var str_info3 = "Autobus: " + data.bus_info.clave + " (" + data.bus_info.description + ")";
                        //
                        $("#autobus_info").html(str_info3);
                    }



                }
            })


            //
            app.seats.init();

        }






        //
        $(document).off("loadBusSeats").on("loadBusSeats", function(evt) {

            //
            $("#tbl_body_results2").html("");
            $("#bus_1st_floor").html("");
            $("#bus_2nd_floor").html("");
            disable_btns();



            //
            $.ajax({
                type: 'GET',
                url: app.public_url + "/utils/results/" + selected_seat.ruta_id + "/" +  selected_seat.id + "/" + selected_seat.origen_ciudad_id + "/" + selected_seat.destino_ciudad_id + "/?bi=1",
                success: function(response){
                    //
                    enable_btns();
                    //
                    app.loadBusSeats(response);
                },
                error: function(){
                    /**/
                }
            });

        });





        $(document).trigger("loadBusSeats", []);





    }
    return {init: moduleReady}
});