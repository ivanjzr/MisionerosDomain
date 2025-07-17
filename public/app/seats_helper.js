

function toFloat(itm){
    if ($.isNumeric(itm)){
        return parseFloat(itm);
    }
    return 0;
}




//
$.loadSeats = function(opts){
    //
    return {
        btn_select_name: "btn-select-space",
        bindEvts: function(){


            //
            let self = this;

            //
            $("."+this.btn_select_name).click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                if (opts.onSelectSpace && $.isFunction(opts.onSelectSpace)){
                    opts.onSelectSpace(opts, self, $(this).data("info"));
                }
            });


            //
            if ( opts.btnReloadId && $(opts.btnReloadId).length ){
                //
                $(opts.btnReloadId).click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    self.init();

                });
            }

        },
        //
        renderBusFloor: function(arr_rows, display_1st_floor_info, display_2nd_floor_info){

            //
            var self = this;

            //
            var str_tbl = "";

            //
            str_tbl = "";
            str_tbl += "<table class='table table-sm' style='border: 1px solid #75d8ff'>";

            //
            if ( arr_rows && arr_rows.length ){


                //
                if ( display_1st_floor_info || display_2nd_floor_info ){
                    //
                    var str_piso = "";
                    if (display_1st_floor_info){
                        str_piso = "1er Piso";
                    } else if (display_2nd_floor_info){
                        str_piso = "2do Piso";
                    }
                    //
                    str_tbl += "<tr>";
                    str_tbl += "<th colspan='6' style='border: 1px solid #75d8ff' class='text-center'>" + str_piso + "</th>";
                    str_tbl += "</tr>";
                }


                //
                str_tbl += "<tr>";
                var front_bus_img = "<img src='/app/bus-front.png' style='width:100%;height:50px;text-align: center;' />";
                str_tbl += "<th colspan='6' style='border:1px solid #fff'> " + front_bus_img + " </th>";
                str_tbl += "</tr>";




                //
                if (!opts.hideColsAndRows){
                    //
                    str_tbl += "<tr>";
                    str_tbl += "<th style='width:20px;'>&nbsp;</th>";
                    //
                    str_tbl += "<th class='text-center'> A </th>";
                    str_tbl += "<th class='text-center'> B </th>";
                    str_tbl += "<th style='width:15px;'></th>";
                    str_tbl += "<th class='text-center'> C </th>";
                    str_tbl += "<th class='text-center'> D </th>";
                    //
                    str_tbl += "</tr>";
                }





                //
                $.each(arr_rows, function(idx, item_row){
                    //console.log(idx, item);
                    //
                    str_tbl += "<tr>";


                    //
                    if (!opts.hideColsAndRows){
                        //
                        str_tbl += "<td><small>" + item_row.row + "</small></td>";
                    }


                    //
                    $.each(item_row.cols, function(idx2, item_col){
                        //console.log("item_col: ", item_col);
                        //
                        var elem_info = JSON.stringify(item_col);


                        if (idx2===2){
                            str_tbl += "<td style='width:15px;height:40px;background-color: #fff; border-top:0; border-bottom:0; border-left: 1px solid #75d8ff;border-right: 1px solid #75d8ff; padding:0;'></td>";
                        }


                        //
                        var space_hex_color = "#fff";
                        var space_opacity = "1";
                        var space_border = "none";

                        //
                        if ( item_col.seat_number ){

                            //
                            space_hex_color = item_col.seat_hex_color;

                            //
                            if ( item_col.ocupacion_id ){
                                space_hex_color = "#ddd";
                                space_opacity = "0.5";
                                space_border = "1px solid #bdddff";
                            }
                            //
                            else if ( item_col.seat_active ){
                                space_border = "1px solid #75d8ff";
                            }
                            //
                            else {
                                space_border = "1px solid #bdddff";
                                space_opacity = "0.3";
                            }

                        } else {
                            //
                            if ( item_col.seat_hex_color ){
                                space_hex_color = item_col.seat_hex_color;
                            }
                        }






                        //
                        str_tbl += "<td style='height: 40px;background-color: " + space_hex_color + "; opacity: " + space_opacity + "; border: " + space_border + "; padding:0;'>";


                        //
                        var btn_link = "<a href='#' class='"+self.btn_select_name+"' data-info='"+elem_info+"' style='display:block;width:100%;height:100%;text-align: center;vertical-align: middle;'>";
                        //
                        if ( opts.bindOnActiveSeats){

                            //
                            if (item_col.seat_number && item_col.seat_active && !item_col.ocupacion_id){
                                str_tbl += btn_link;
                            }

                        } else {
                            str_tbl += btn_link;
                        }

                        //
                        if (item_col.seat_number){

                            var ocupacion_id = (item_col.ocupacion_id) ? " (" + item_col.ocupacion_id  + ") ": "";

                            str_tbl += item_col.seat_number + ocupacion_id;
                        }

                        //
                        if ( opts.bindOnActiveSeats ){
                            //
                            if (item_col.seat_number && item_col.seat_active && !item_col.ocupacion_id){
                                str_tbl += "</a>";
                            }
                        } else {
                            str_tbl += "</a>";
                        }



                        str_tbl += "</td>";

                    });


                    //
                    str_tbl += "</tr>";
                });

                //
            }

            //
            str_tbl += "</table>";
            //
            return str_tbl;
        },
        onDataReady: function(data){
            //console.log("***seats onDataReady: ", data);

            //
            var display_1st_floor_info = false;
            //
            if (data && data.rows_piso_2){
                //
                display_1st_floor_info = true;
                var str_tbl2 = this.renderBusFloor(data.rows_piso_2, false, true);
                $(opts.busFirstFloorId).html(str_tbl);
                $(opts.bus2ndFloorId).html(str_tbl2);
            }

            //
            if (data && data.rows_piso_1){
                //
                var str_tbl = this.renderBusFloor(data.rows_piso_1, display_1st_floor_info, false);
                $(opts.bus1stFloorId).html(str_tbl);
            }



            //
            if (data && data.error) {
                app.Toast.fire({ icon: 'error', title: data.error});
            }

            //
            if (opts.onReady && $.isFunction(opts.onReady)){
                opts.onReady(opts, this, data);
            }

            //
            this.bindEvts(data);

        },
        setData: function(new_data){
            opts.data = new_data;
        },
        reload: function(){
          this.init();
        },
        init: function(){
            //
            var self = this;


            //
            if (opts.onBeforeLoad && $.isFunction(opts.onBeforeLoad)){
                opts.onBeforeLoad(opts, this);
            }

            //
            disable_btns();
            $(opts.bus1stFloorId).html("");
            $(opts.bus2ndFloorId).html("");


            if (opts.data){

                //
                self.onDataReady(opts.data);
                enable_btns();

            } else {

                //
                $.ajax({
                    type:'GET',
                    url: opts.url,
                    beforeSend: function (xhr) {
                        /**/
                    },
                    success:function(data){
                        //console.log("-------data loaded: ", data);
                        //
                        self.onDataReady(data);
                        enable_btns();

                    },
                    error: function(){
                        //
                        enable_btns();
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });

            }



        }
    }
}
