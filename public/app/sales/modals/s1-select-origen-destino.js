define(function(){
    function moduleReady(modal, section_data){
        console.log("------buscar: ", section_data);






        function onSelectedOrigen(origen_ciudad_id){
            //
            loadSelectAjax({
                id: "#destino_ciudad_id",
                url: app.public_url + "/utils/" + origen_ciudad_id + "/destinos",
                parseFields: function(item){
                    var str_ext = (item.ext_origen_ciudad_id) ? " (Extension de " + item.ext_origen_ciudad_nombre + ") " : "";
                    return item.ciudad_nombre + str_ext;
                },
                onChange: function(){
                    //
                },
                saveValue: true,
                prependEmptyOption: true,
                emptyOptionText: "--seleccionar",
                enable: true
            });
        }


        //
        function onSelectOrigen(){
            //
            var origen_ciudad_data = $("#origen_ciudad_id").find(":selected").data("info");
            //
            if (origen_ciudad_data && origen_ciudad_data.id){
                onSelectedOrigen(origen_ciudad_data.id);
            } else {
                $("#destino_ciudad_id").html("");
            }
        }

        //
        loadSelectAjax({
            id: "#origen_ciudad_id",
            url: app.public_url + "/utils/origenes",
            parseFields: function(item){
                var str_ext = (item.ext_destino_ciudad_id) ? " (Extension a " + item.ext_destino_ciudad_nombre + ") " : "";
                return item.ciudad_nombre + str_ext;
            },
            onChange: function(){
                onSelectOrigen();
            },
            onReady: function(){
                onSelectOrigen();
            },
            saveValue: true,
            prependEmptyOption: true,
            emptyOptionText: "--seleccionar",
            enable: true
        });




        //
        $("#fecha_hora_busqueda").datePickerTask({
            storeId: "fecha_hora_busqueda",
            saveValue: false,
            opts:{
                autoclose: true,
                defaultDate: moment(),
                ignoreReadonly: false,
                format:'YYYY-MM-DD',
            },
            onChange: function(date){
                /**/
            }
        });





        //
        $('#form_section2').validate();
        //
        $('#form_section2').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_section2').valid() ) {


                //
                $('#form_section2').ajaxSubmit({
                    url: app.public_url + "/utils/buscar",
                    beforeSubmit: function(arr){
                        disable_btns();
                        //
                        var this_date = $('#fecha_hora_busqueda').datetimepicker('date');
                        //
                        arr.push({
                            name: "fecha_hora_busqueda",
                            value: moment(this_date).format("YYYY-MM-DD")
                        });
                    },
                    success: function(response){
                        //
                        enable_btns();

                        //
                        if (response.error){
                            app.Toast.fire({ icon: 'error', title: response.error});
                        }

                        if (response && response.length){
                            $(document).trigger(section_data.bindFuncName, [response]);
                        } else {
                            app.Toast.fire({ icon: 'info', title: " No se encontraron resultados"});
                        }

                        //
                        $("#modal-search-origenes-destinos").find('.modal').modal("hide");

                    },
                    error: function(response){
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