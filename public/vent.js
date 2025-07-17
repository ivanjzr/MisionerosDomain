// ventas helper
var vent = {};


//
vent.mins_limite = 5;


//
vent.updateCounter = function(item_insert_id, item_is_expired, horaInicial) {

    //
    var update_elem_id = "item-"+item_insert_id;
    var btn_cls_refresh = "btn-cls-refresh-"+item_insert_id;
    var btn_cls_del = "btn-cls-del-"+item_insert_id;
    var btn_cls_edit = "btn-cls-edit-"+item_insert_id;

    //
    var horaActual = moment();
    var diferenciaMilisegundos = horaActual.diff(horaInicial);

    //
    var minutosTranscurridos = Math.floor(diferenciaMilisegundos / (60 * 1000));
    var segundosTranscurridos = Math.floor((diferenciaMilisegundos % (60 * 1000)) / 1000);


    //
    var field_color = "green";
    var str_input_expired = "";
    var str_is_expired = (item_is_expired) ? " Expiro " : "";
    //
    if ( minutosTranscurridos >= vent.mins_limite ){
        
        //
        field_color = "orangered";
        // 
        str_input_expired = "<input type='hidden' class='is_expired' value='1' />";
        $("." + btn_cls_refresh).show();

    } else {
        //
        $("." + btn_cls_refresh).hide();
    }

    //
    $("." + btn_cls_del).show();
    $("." + btn_cls_edit).show();

    
    //
    var field_res = "<small style='color:"+field_color+"'>(" + str_is_expired + " Hace " + minutosTranscurridos + " minutos " + segundosTranscurridos + " segundos)</small>" + str_input_expired;
    
    //console.log(moment().format("DD MMM YY h:mm A"), field_res);
    $("#" + update_elem_id).html(field_res);


    //
    var cant_elems_expired = vent.getElemsExpired();
    var str_elems_expired = "";
    if (cant_elems_expired){
        str_elems_expired = cant_elems_expired + " elements expired ";     
    }
    $("#elems_expired").html(str_elems_expired);
}



vent.getElemsExpired = function(){
    return $(".is_expired").length;
}




vent.tipoAsiento = function(tipo){
    return (tipo==="v") ? "Ventanilla" : "Pasillo";
}

vent.counters = {};
vent.counters2 = {};

vent.clearCounters = function() {
    for (let counter_item_id in vent.counters) {
        if (vent.counters.hasOwnProperty(counter_item_id)) {
            clearInterval(vent.counters[counter_item_id]);
        }
    }
    vent.counters = {};
}

vent.clearCounters2 = function() {
    for (let counter_item_id in vent.counters2) {
        if (vent.counters2.hasOwnProperty(counter_item_id)) {
            clearInterval(vent.counters2[counter_item_id]);
        }
    }
    vent.counters2 = {};
}


vent.addLugarTempItem = function(item){
    //console.log("---addLugarTempItem: ", item);

    // 
    var item_insert_id = item.id;

    // 
    var sel_item = JSON.stringify({
        id: item_insert_id,
        salida_id: item.salida_id,
        ruta_id: item.ruta_id,
        origen_ciudad_id: item.origen_ciudad_id,
        destino_ciudad_id: item.destino_ciudad_id,
        passanger_name: item.passanger_name,
        passanger_dob: item.passanger_dob,
        passanger_age: item.passanger_age
    });

    //
    var str_item = "<tr>";


    // 
    //var tipo_asiento_info = " <small style='font-size:16px;'>(" + vent.tipoAsiento(seat.tipo_ventanilla_pasillo) + " - " + seat.espacio_tipo + " )</small>";
    var str_seat_info = "<div style='font-size:24px;line-height:18px;'> #" + item.num_asiento + " - " + item.passanger_name + "</div>";
    // 
    str_item += "<td><input type='hidden' class='item-id' value='" + item_insert_id + "' />" + str_seat_info + " <br /><small style='color:gray;'>" + momentFormat(item.passanger_dob, "DD MMM YYYY")  + " / " + item.passanger_age + " años / " + item.tipo_precio_descripcion + "</small></td>";


    //
    str_item += "<td>" + item.id + " / " + item.salida_id + " / " + item.ruta_id + " / " + item.autobus_clave + "</td>";


    //
    var str_ext_destino = (item.ext_destino_ciudad_id) ? "<br /><small>extension a " + item.extension_destino_info + "</small> " : "";
    str_item += "<td>" + item.origen_info + " <br /> " + momentFormat(item.fecha_hora_salida, "DD MMM YY h:mm A") + str_ext_destino + " </td>";
    //
    var str_ext_origen = (item.ext_origen_ciudad_id) ? "<br /><small>extension de " + item.extension_origen_info + "</small> " : "";
    str_item += "<td>" + item.destino_info + " <br /> " + momentFormat(item.fecha_hora_llegada, "DD MMM YY h:mm A") + str_ext_origen + "</td>";
    
    //
    str_item += "<td>" + item.total + "<br />" + item.calc_info + "</td>";



    //
    var counter_item_id = "item-counter-"+item_insert_id;
    var update_elem_id = "item-"+item_insert_id;
    var btn_cls_refresh = "btn-cls-refresh-"+item_insert_id;
    var btn_cls_del = "btn-cls-del-"+item_insert_id;
    var btn_cls_edit = "btn-cls-edit-"+item_insert_id;
    
    //
    var item_datetime_created = moment(item.datetime_created, "YYYY-MM-DD HH:mm");
    var item_is_expired = (item.is_expired) ? 1 : 0;


    // Detener cualquier contador existente antes de iniciar uno nuevo
    if (vent.counters[counter_item_id]){
        clearInterval(vent.counters[counter_item_id]);
    }
    //
    vent.counters[counter_item_id] = setInterval(function() {
        vent.updateCounter(item_insert_id, item_is_expired, item_datetime_created);
    }, 1000);

    //console.log(vent.counters);


    //
    str_item += "<td>" + item_datetime_created.format("DD MMM YY h:mm A") + " <span id='"+update_elem_id+"'></span></td>";

    //
    var str_refresh_btn = "<button type='button' class='btn btn-xs btn-default btn-refresh-item btn-block "+btn_cls_refresh+"' style='display:none;' data-item='" + sel_item + "'><span class='fas fa-sync'></span> </button>";
    var str_edit_btn = "<button type='button' class='btn btn-xs btn-info btn-edit-item btn-block "+btn_cls_edit+"' style='display:none;' data-item='" + sel_item + "'><span class='fas fa-edit'></span> </button>";
    var str_del_btn = "<button type='button' class='btn btn-xs btn-primary btn-del-item btn-block "+btn_cls_del+"' style='display:none;' data-item='" + sel_item + "'><span class='fas fa-trash'></span> </button>";
    //
    str_item += "<td>" + str_refresh_btn + str_edit_btn + str_del_btn + "</td>";




    //
    str_item += "</tr>";
    //
    return str_item
}







//
vent.delItem = function(ls_arr_name, item_id){
    //
    var the_arr = getLSItem(ls_arr_name);
    //
    $.each(the_arr, function(idx, item){
        //
        if (parseInt(item.id) === parseInt(item_id)){
            the_arr.splice(idx, 1);
            return false;
        }
    });
    localStorage.setItem(ls_arr_name, JSON.stringify(the_arr));
}


//
vent.updateItem = function(ls_arr_name, item_id, new_item){
    //
    var the_arr = getLSItem(ls_arr_name);
    var item_updated = false;
    //
    $.each(the_arr, function(idx, item){
        //
        if (parseInt(item.id) === parseInt(item_id)){
            the_arr[idx]= new_item;
            item_updated = true;
        }
    });
    //
    localStorage.setItem(ls_arr_name, JSON.stringify(the_arr));
    return item_updated;
}


//
vent.findItem = function(ls_arr_name, item_id){
    //
    var the_arr = getLSItem(ls_arr_name);
    var item_found = null;
    //
    $.each(the_arr, function(idx, item){
        //
        if (parseInt(item.id) === parseInt(item_id)){
            item_found = item;            
            return false;
        }
    });
    return item_found;
}




// 
vent.actualizarOcupacion = function(temp_sale_id){
   // 
   $.ajax({
    type: 'GET',
    url: app.public_url + "/utils/temp-ocupacion/" + temp_sale_id,
    dataType: "json",
    data: null,
    beforeSend: function (xhr) {
        //xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
        disable_btns();
        preload(true);
    },
    contentType: "application/json",
    success: function (response) {
        //console.log(response); return;
        enable_btns();
        preload(false);
        //
        vent.renderItems(response, "#tbl_body_results");
        vent.onItemsReady();
    },
    error: function () {
        enable_btns();
        preload(false);
        //
        app.Toast.fire({icon: 'error', title: "Error en el servidor o internet inactivo"});

    }
});
}



vent.getServerItem = function(arr_server_items, item_id){
    //
    var item_found = null;
    //
    $.each(arr_server_items, function(idx, item){
        if (parseInt(item.id) === parseInt(item_id)){
            //console.log("item found: ", item);
            item_found = item;
        }
    });
    return item_found;
}


//
vent.renderItems = function(arr_server_items, append_to_elem){
  
    //
    var arr_local_items = getLSItem("arr_sale_items");
    console.log(arr_local_items, arr_server_items);

    // 
    vent.counters = {};
    $(append_to_elem).html("");
    //
    var total_amount = 0;

    //arr_server_items

    //
    if (arr_local_items && arr_local_items.length){
        //
        $.each(arr_local_items, function(idx, item){
            //console.log("local item: ", idx, item);
            
            // Si existe el item se actualiza con la hora del server
            var item_found = vent.getServerItem(arr_server_items, item.id)
            if (item_found && item_found.id){
                item.datetime_created = item_found.datetime_created;
            } 
            // caso contrario se deja la hora del item local y solo se agrega la variable is_expired
            else {
                item.is_expired = true;
            }

            //
            var item_el = vent.addLugarTempItem(item);
            $(append_to_elem).append(item_el);
            //
            total_amount += parseFloat(item.total);
        });
    } else {

        //app.Toast.fire({icon: 'success', title: "Cart vacio"});
        vent.updateShowBtn();
        $("#modal-show-sale").find('.modal').modal("hide");
    }
  //
  $("#total_amount").val(total_amount);
  $("#totals").html("Total: $"+total_amount);    
}


//
vent.updateShowBtn = function(){
    //
    $(".btnShowSale").hide();
    $(".btnRemoveSale").hide();
    //
    var arr_sale_items = getLSItem("arr_sale_items");
    if (arr_sale_items && arr_sale_items.length){
        //
        $(".btnShowSale").show();
        $(".btnRemoveSale").show();
    }
}

vent.showSaleModal = function(type){
    //
    loadModalV2({
        id: "modal-show-sale",
        modal_size: "lg",
        data: type,
        html_tmpl_url: "/app/sales/modals/show-sale.html?v="+dynurl(),
        js_handler_url: "/app/sales/modals/show-sale.js?v="+dynurl(),
        onBeforeLoad: function(){
            disable_btns();
        },
        onInit: function(){
            enable_btns();
        }
    });
}


vent.getSelDob = function(){
    //
    var dob_anio = $("#dob_anio").val();
    var dob_mes = $("#dob_mes").val();
    var dob_dia = $("#dob_dia").val();
    //
    //console.log(dob_anio, dob_mes, dob_dia, moment(`${dob_anio}-${dob_mes}-${dob_dia}`, 'YYYY-MM-DD'));
    return moment(`${dob_anio}-${dob_mes}-${dob_dia}`, 'YYYY-MM-DD');
}



 //
 vent.arr_months = [
    {val:1, text: "Ene"},
    {val:2, text: "Feb"},
    {val:3, text: "Mar"},
    {val:4, text: "Abr"},
    {val:5, text: "May"},
    {val:6, text: "Jun"},
    {val:7, text: "Jul"},
    {val:8, text: "Ago"},
    {val:9, text: "Sep"},
    {val:10, text: "Oct"},
    {val:11, text: "Nov"},
    {val:12, text: "Dec"}
]

vent.generateTempSaleId = function(){
    var temp_sale_id = generateRandom4DigitNumber();
    localStorage.setItem("temp_sale_id", app.temp_sale_id);
    return temp_sale_id;
}


//
vent.onItemsReady = function(){

    //
    $(".btn-edit-item").click(function(e) {
       e.preventDefault();

       //
       var item_data = $(this).data("item");
       //console.log(item_data);

       //
       loadModalV2({
           id: "modal-add-pasajero",
           modal_size: "lg",
           data: item_data,
           html_tmpl_url: "/app/sales/modals/add-edit-pasajero.html?v=" + dynurl(),
           js_handler_url: "/app/sales/modals/edit-pasajero.js?v=" + dynurl(),
           onBeforeLoad: function(){
               disable_btns();
           },
           onInit: function(){

               //
               enable_btns();

               //
               $('#modal-title-2').html("Editar Pasajero " + item_data.passanger_name + " - " + item_data.passanger_age);
               $('.btnAddEditPasajero').html("<i class='fa fa-edit'></i> Editar");

           }
       });


    });

   //
   $(".btn-del-item").click(function(e) {
       e.preventDefault();

       //
       var item_data = $(this).data("item");

       //
       if (confirm("Eliminar item " + item_data.id)) {

           //
           var del_id = parseInt(item_data.id);

           //
           $.ajax({
               type: 'POST',
               url: app.public_url + "/utils/del-item",
               dataType: "json",
               data: JSON.stringify({
                   id: del_id,
                   visitor_id: app.visitor_id
               }),
               beforeSend: function (xhr) {
                   //xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
                   disable_btns();
                   preload(true);
               },
               contentType: "application/json",
               success: function (response) {
                   //console.log(response); return;
                   //
                   enable_btns();

                   //
                   if (response && response.id) {

                       //
                       vent.delItem("arr_sale_items", del_id);
                       //
                       app.Toast.fire({icon: 'success', title: "Item eliminado correctamente"});
                       //
                       vent.actualizarOcupacion(app.temp_sale_id);

                   }
                   //
                   else if (response.error) {
                       app.Toast.fire({icon: 'error', title: response.error});
                   }
                   //
                   else {
                       app.Toast.fire({icon: 'error', title: "Error en el servidor o internet inactivo"});
                   }

               },
               error: function () {
                   enable_btns();
                   //
                   app.Toast.fire({icon: 'error', title: "Error en el servidor o internet inactivo"});

               }
           });


       }

   });



   //
   $(".btn-refresh-item").click(function(e) {
       e.preventDefault();

       //
       var item_data = $(this).data("item");
       var prev_id = item_data.id;
       var the_item = vent.findItem("arr_sale_items", prev_id);
       //console.log(item_data, the_item); return;
       
       //
       var post_data = {
           visitor_id: app.visitor_id,
           temp_sale_id: app.temp_sale_id,
           ruta_id: the_item.ruta_id,
           salida_id: the_item.salida_id,                    
           lugar_id: the_item.lugar_id,
           origen_ciudad_id: the_item.origen_ciudad_id,
           destino_ciudad_id: the_item.destino_ciudad_id,
           passanger_name: the_item.passanger_name,
           passanger_dob: the_item.passanger_dob,
       }
       //console.log("---new_item: ", new_item); return;

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
               if (response && response.id){

                   
                   //
                   var item_updated = vent.updateItem("arr_sale_items", prev_id, response);
                   //
                   if (item_updated){
                       app.Toast.fire({icon: 'success', title: "Lugar Actualizado correctamente"});
                   } else {
                       app.Toast.fire({icon: 'info', title: "No se actualizo el lugar"});   
                   }


                    //
                    vent.clearCounters();                             
                   //
                   vent.actualizarOcupacion(app.temp_sale_id);

               }
               //
               else if (response.error){
                   app.Toast.fire({ icon: 'error', title: response.error});
               }
               //
               else {
                   app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
               }

           },
           error: function(){
               enable_btns();
               //
               app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });

           }
       });



   });

}
