(function ($) {
    'use strict';




    //
    function loadCiudades(estado_id, ciudad_id){
        //
        $("#ciudad_id")
            .html("")
            .attr("disabled", true)
            .append("<option>--</option>");
        //
        if (estado_id){
            //
            loadSelectAjax({
                id: "#ciudad_id",
                url: app.admin_url + "/sys/states/" + estado_id + "/cities",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                default_value: ciudad_id,
                emptyOptionText: "--select",
                enable: true
            });
        }
    }



    //
    function onEditReady(section_data, opts){

        //
        $('#nombre').val(section_data.name);
        $('#email').val(section_data.email);
        $('#phone_cc').val(section_data.phone_cc);
        $('#phone_number').val(section_data.phone_number);
        $('#address').val(section_data.address);
        $('.sucursal-nombre').text(section_data.name);
        //
        $('#lat').val(section_data.lat);
        $('#lng').val(section_data.lng);

        //
        $('#nombre_razon_social').val(section_data.nombre_razon_social);
        $('#rfc').val(section_data.rfc);

        

        //
        if (section_data.active){
            $('#active').attr("checked", true);
        } else {
            $('#active').attr("checked", false);
        }
        //
        $('.section-title').text(section_data.name);




        //
        loadSelectAjax({
            id: "#estado_id",
            url: app.admin_url + "/sys/states",
            parseFields: function(item){
                return item.nombre;
            },
            prependEmptyOption: true,
            emptyOptionText: "--select",
            default_value: (section_data.estado_id) ? section_data.estado_id: null,
            enable: true,
            onChange: function(){
                //
                var estado_id = $("#estado_id").val();
                loadCiudades(estado_id);
            }
        });
        //
        loadCiudades(section_data.estado_id, section_data.city_id);



        // def focus
        $("#nombre").focus();
    }




    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;


        //
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/sucursales/modules/cfdi.js",
            onInit: function(){
                enable_btns();
            }
        });


        
    }





    //
    app.createSection({
        section_title: "Stores",
        section_title_singular: "Store",
        scripts_path: "/app/sucursales",
        endpoint_url: app.admin_url + "/sucursales",
        record_id: record_id,
        onEditReady: onEditReady,
        reloadDataOnSave: true,
        loadModules: loadModules,
        onSectionReady: function(opts){



            //
            $('.btnReloadDetails').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                opts.loadData();
            });


        }
    });




})(jQuery);