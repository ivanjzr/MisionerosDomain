(function ($) {
    'use strict';





    //
    function onEditReady(section_data, opts){

        //
        $('#title').val(section_data.title);
        $('.product-title').text(section_data.title);
        $('#url').val(section_data.url);
        $('#sub_title').val(section_data.sub_title);
        $('#description').val(section_data.description);
        $('#full_description').val(section_data.full_description);

        //
        if (section_data.active){
            $('#active').attr("checked", true);
        } else {
            $('#active').attr("checked", false);
        }




        //
        if ( section_data.thumb_img_url ){
            //
            $('#img_section_url').attr("src", section_data.thumb_img_url + dynurl());
            $('#img_section_url').attr("data-id", section_data.id);
            $('#img_section_url').css({
                "width":200
            });
            $('#img_section_container').show();
        } else {
            $('#img_section_url').attr("src", null);
            $('#img_section_container').hide();
        }




        //
        $("#title").keyup(function(e) {
            //
            var title = $(this).val();
            $("#url").val(convertToUrl(title));
        });
        // def focus
        $("#title").focus();

    }




    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;



        // MOD - FECHAS
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/events/modules/fechas.js",
            onInit: function(){
                enable_btns();
            }
        });


        // MOD - GALLERY
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/events/modules/gallery.js",
            onInit: function(moduleReady){
                enable_btns();
            }
        });



    }




    /*
    *
    * SECCION EVENTOS
    *
    * */
    app.createSection({
        section_title: "Eventos",
        section_title_singular: "Evento",
        scripts_path: "/app/events",
        endpoint_url: app.admin_url + "/events",
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


            //
            $('#btnOpenPrices').click(function(e) {
                e.preventDefault();
                //
                var tab_name = "precios";
                //
                $('.nav-tabs a[href="#' + tab_name + '"]').tab('show');
            });

        }
    });




})(jQuery);