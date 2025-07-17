(function ($) {
    'use strict';



    /*
    *
    * SECCION CONFIG
    *
    * */
    app.createSection({
        section_title: "Config",
        section_title_singular: "Config",
        scripts_path: "/app/site/config",
        endpoint_url: app.admin_url + "/site/config",
        preventPassId: true,
        loadModules: function(section_data, opts){
            //
            section_data.opts = opts;

            // MOD - LAYOUTS
            loadModule({
                data: section_data,
                onBeforeLoad: function(){
                    disable_btns();
                },
                js_url: "/app/site/config/layouts/index.js",
                onInit: function(){
                    enable_btns();
                }
            });
        },
        onEditReady: function(section_data, opts){
            //
            $("#site_title").val(section_data.site_title);
            $("#email").val(section_data.email);
            $("#address").val(section_data.address);
            $("#phone_number").val(section_data.phone_number);
            $("#phone_number_2").val(section_data.phone_number_2);
            $("#facebook_url").val(section_data.facebook_url);
            $("#twitter_url").val(section_data.twitter_url);
            $("#youtube_url").val(section_data.youtube_url);
            $("#linkedin_url").val(section_data.linkedin_url);
            $("#instagram_url").val(section_data.instagram_url);
        },
        onSectionReady: function(opts){

            //
            $("#btnReloadConfig").click(function(){
                //
                opts.loadData();
            });
            //
            opts.loadData();

            //
            $("#site_title").focus();
        }
    });




})(jQuery);