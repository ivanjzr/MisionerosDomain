(function ($) {
    'use strict';



    //
    function onEditReady(section_data, opts){

        //
        $('#name').val(section_data.name);
        $('#description').val(section_data.description);
        $('#location_name').val(section_data.location_name);
        //
        $('.pdv-title').text(section_data.name);
        
        //
        if (section_data.active){
            $('#active').attr("checked", true);
        } else {
            $('#active').attr("checked", false);
        }
        
        // def focus
        $("#nombre").focus();

    }







    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;


        /*
        //
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/resources/modules/services.js",
            onInit: function(){
                enable_btns();
            }
        });
        */


        

    }




    //
    app.createSection({
        section_title: "Puntos de venta",
        section_title_singular: "Punto de venta",
        scripts_path: "/app/pos/pos_list",
        endpoint_url: app.admin_url + "/pos/list",
        record_id: record_id,
        onEditReady: onEditReady,
        loadModules: loadModules,
        onSectionReady: function(opts){

            //
            $('#btnReloadDetails').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                opts.loadData();
            });

        }
    });




})(jQuery);