(function ($) {
    'use strict';




    //
    function onEditReady(section_data, opts){

        //
        $('#nombre').val(section_data.nombre);
        $('.blog-title').text(section_data.nombre);
        $('#url').val(section_data.url);
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
        $('#contenido').summernote({
            placeholder: '',
            height: 500,
            callbacks: {
                onInit: function() {
                    //$('#contenido').summernote('codeview.activate');
                    $("#contenido").summernote("code", ((section_data && section_data.contenido) ? section_data.contenido : ""));
                }
            }
        });



        //
        loadSelectAjax({
            id: "#category_id",
            url: app.admin_url + "/cat/blog-categories/list",
            parseFields: function(item){
                return item.category;
            },
            default_value: section_data.category_id,
            prependEmptyOption: true,
            emptyOptionText: "--select",
            enable: true
        });


        //
        $("#nombre").keyup(function(e) {
            //
            var nombre = $(this).val();
            $("#url").val(convertToUrl(nombre));
        });
        // def focus
        $("#nombre").focus();





        //
        $('#btnAddImage').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //
            disable_btns();
            //
            loadModalV2({
                id: "select-image",
                modal_size: "xl",
                data: section_data,
                html_tmpl_url: "/app/blog/modals/select-image.html?v=" + dynurl(),
                js_handler_url: "/app/blog/modals/select-image.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){
                    //
                    enable_btns();

                }
            });
        });

    }




    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;


        // MOD - PRECIOS
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/blog/modules/blog-visits.js",
            onInit: function(){
                enable_btns();
            }
        });

    }




    /*
    *
    * SECCION POSTS
    *
    * */
    app.createSection({
        section_title: "Posts",
        section_title_singular: "Post",
        scripts_path: "/app/blog",
        endpoint_url: app.admin_url + "/blog/posts",
        record_id: record_id,
        onEditReady: onEditReady,
        beforeSubmit: function(arr){
            // //
            // arr.push({
            //     name: "contenido",
            //     value: $("#contenido").summernote('code')
            // });
        },
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