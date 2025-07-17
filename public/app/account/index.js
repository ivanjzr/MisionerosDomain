(function ($) {
    'use strict';

    //
    app.onAccountDataReady = function(data){

        // Update page title
        $(".account-title").html(data.company_name);
        
        // Basic Information
        $('#company_name_display').text(data.company_name || '-');
        $('#contact_name_display').text(data.contact_name || '-');
        $('#account_id_display').text(data.account_id || '-');
        
        // Format and display creation date
        if (data.datetime_created && data.datetime_created.date) {
            const createdDate = moment(data.datetime_created.date);
            $('#datetime_created_display').text(createdDate.format('DD/MM/YYYY HH:mm'));
        } else {
            $('#datetime_created_display').text('-');
        }

        // Application Details
        $('#app_name_display').text(data.app_name || '-');
        $('#app_folder_name_display').text(data.app_folder_name || '-');
        $('#domain_controller_display').text(data.domain_controller || '-');
        $('#views_name_display').text(data.views_name || '-');

        // Domain Information
        $('#domain_prod_display').text(data.domain_prod || '-');
        $('#domain_prod2_display').text(data.domain_prod2 || '-');
        $('#domain_dev_display').text(data.domain_dev || '-');
        $('#domain_dev2_display').text(data.domain_dev2 || '-');

        // Logo Information
        $('#logo_path_display').text(data.logo_path || '-');
        $('#login_logo_path_display').text(data.login_logo_path || '-');

        // Handle logo images
        if (data.logo_path && data.logo_path !== '') {
            $('#logo_img').attr('src', "/logos" + data.logo_path).show();
            $('#logo_placeholder').hide();
        } else {
            $('#logo_img').hide();
            $('#logo_placeholder').show();
        }

        if (data.login_logo_path && data.login_logo_path !== '') {
            $('#login_logo_img').attr('src', "/logos" + data.login_logo_path).show();
            $('#login_logo_placeholder').hide();
        } else {
            $('#login_logo_img').hide();
            $('#login_logo_placeholder').show();
        }

        // Load account apps module
        loadModule({
            data: data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/account/acct_apps.js",
            onInit: function(){
                enable_btns();
            }
        });

        // Hide loading overlay
        preload(".section-preloader, .overlay");
    }

    //
    app.loadAccountData = function(){
        //
        $(".reset-item").html("");
        $('#logo_img').hide();
        //
        $.ajax({
            type: 'GET',
            url: app.admin_url + "/account",
            success: function(data){
                if (data && data.id){
                    //
                    app.onAccountDataReady(data);
                } else {
                    app.Toast.fire({ 
                        icon: 'warning', 
                        title: "No se encontraron datos de la cuenta." 
                    });
                    preload(".section-preloader, .overlay");
                }
            },
            error: function(){
                app.Toast.fire({ 
                    icon: 'error', 
                    title: "Error de conexión. Verifica tu conexión e intenta nuevamente." 
                });
                preload(".section-preloader, .overlay");
            }
        });
    }

    //
    $('#btnReloadDetails').click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        //
        // Show loading overlay
        $(".section-preloader").show();
        //
        app.loadAccountData();
    });

    // Initialize
    app.loadAccountData();

})(jQuery);