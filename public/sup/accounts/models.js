(function ($) {
    'use strict';




    /*
    *
    * ACCOUNT MENUS
    *
    * */
    app.createSection({
        section_title: "Accounts",
        section_title_singular: "Account",
        scripts_path: "/sup/accounts",
        endpoint_url: app.supadmin_url + "/accounts",
        record_id: record_id,
        onEditReady: function(section_data, opts){

            //
            $('.section-title').text(section_data.company_name);



        },
        reloadDataOnSave: true,
        loadModules: function(section_data, opts){


            //
            section_data.endpoint_url = app.supadmin_url + "/accounts/" + section_data.id + "/models";


            //
            loadModule({
                data: section_data,
                onBeforeLoad: function(){
                    disable_btns();
                },
                js_url: "/sup/accounts/modules/models.js",
                onInit: function(){
                    enable_btns();
                }
            });

        }
    });




})(jQuery);