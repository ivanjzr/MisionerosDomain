(function ($) {
    'use strict';






    //
    function onEditReady(section_data, opts){

        //
        $('#nombre').val(section_data.nombre);

        //
        let str_tipo = ""
        if (section_data.bus_type==="p"){
            str_tipo = "Regular";
        } else if (section_data.bus_type==="s"){
            str_tipo = "Double Decker";
        }
        //
        $('.bus-type').text(str_tipo + " - ");        
        $('.bus-title').text(section_data.nombre);
        $('.bus-title2').html(section_data.nombre + " <small style='font-size:12px;'>( " + section_data.description + " )</small>");

        $('#url').val(section_data.url);
        $('#description').val(section_data.description);
        $('#bus_code').val(section_data.bus_code);
        $('#year').val(section_data.year);


        $('#make_and_model_info').text(section_data.make + " / " + section_data.model);


        /*
        $('#description').summernote({
            placeholder: '',
            height: 200,
            callbacks: {
                onInit: function() {
                    //$('#contenido').summernote('codeview.activate');
                    $("#description").summernote("code", section_data.description);
                }
            }
        });
        */

        //
        $('#active').attr('checked', (section_data.active==1) ? true : false);
        $("#precio").val(section_data.precio);


        //
        if ( section_data.thumb_img_url ){
            //
            $('#img_section_url').attr("src", section_data.thumb_img_url + dynurl());
            $('#img_section_url').attr("data-id", section_data.id);
            $('#img_section_url').css({
                "width":196
            });
            $('#img_section_container').show();
        } else {
            $('#img_section_url').attr("src", null);
            $('#img_section_container').hide();
        }




        //
        $("#nombre").keyup(function(e) {
            //
            var nombre = $(this).val();
            $("#url").val(convertToUrl(nombre));
        });
        // def focus
        $("#nombre").focus();

    }



    





    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;

        // MOD - PRICES
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/buses/modules/prices.js?v=1.0",
            onInit: function(){
                enable_btns();
            }
        });

        

        // MOD - Features
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/buses/modules/features.js",
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
            js_url: "/app/buses/modules/gallery.js",
            onInit: function(moduleReady){
                enable_btns();
            }
        });


    }




    //
    app.createSection({
        section_title: "Buses",
        section_title_singular: "Bus",
        scripts_path: "/app/buses",
        endpoint_url: app.admin_url + "/buses",
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




            //
            $(".sucursal_name").text($("#sucursal_name").val());
        }
    });




})(jQuery);