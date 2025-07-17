$(document).ready(function(){



   

    app.createSection({
        section_title: "Ventas",
        section_title_singular: "Venta",
        scripts_path: "/app/sales",
        endpoint_url: app.public_url + "/sales/" + sale_code + "/paginate",
        gridOptions:{
            btnExpandir: true,
            btnColapsar: true,
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {visible: false, "name" : "id", "data" : "id"},
                {"name" : "passanger_name", "data" : "passanger_name"},
                {"data" : function(obj){ 
                    return obj.passanger_age + " / " + obj.tipo_precio_descripcion;
                }},
                {"data" : function(obj){ 
                    return obj.origen_info + " - " + momentFormat(obj.fecha_hora_salida.date, "DD MMM YY h:mm A");
                }},
                {"data" : function(obj){ 
                    return obj.destino_info + " - " + momentFormat(obj.fecha_hora_llegada.date, "DD MMM YY h:mm A");
                }},
                {"data" : function(obj){ 
                    return obj.autobus_clave + " / #" + obj.num_asiento;
                }},
                {"name" : "total", "data" : "total"},
            ],
            columnDefs: [
                {
                    "targets": [0, 1, 2, 3],
                    "orderable": false
                },
                {
                    "targets": "_all",
                    "searchable": false
                }
            ],
            hdrBtnsSearch: false,
            deferLoading: false,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){

        },

    });




    var form_name = '#form_confirm';


    //
    $(form_name).validate();
    
    $(form_name).submit(function(e){
        e.preventDefault();



        



        //
        if ($(form_name).valid()){

            //
            disable_btns();

            /*
             * Auth user
             * */
            $(form_name).ajaxSubmit({
                url: app.public_url + "/sales/" + sale_code + "/confirm",
                beforeSubmit: function(arr){
                    //
                    disable_btns();
                },
                success: function(response){

                    //
                    enable_btns();

                    //
                    if ( response && response.id ){
                        //
                        app.Toast.fire({ icon: 'success', title: "Venta Aceptada correctamente" });
                        location.href = "/";
                    }
                    //
                    else if (response.error){                        
                        //
                        app.Toast.fire({ icon: 'error', title: response.error });                        
                    }
                    else { 
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." }); 
                    }
                },
                error: function(response){
                    enable_btns();
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });


        }

    });






})