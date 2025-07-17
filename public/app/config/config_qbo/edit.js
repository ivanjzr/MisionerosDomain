(function ($) {
    'use strict';



    function onDataReady(data){

        //
        $("#prod_client_id").val(data.prod_client_id);
        $("#prod_client_secret").val(data.prod_client_secret);
        $("#prod_redirect_url").val(data.prod_redirect_url);
        //
        $("#dev_client_id").val(data.dev_client_id);
        $("#dev_client_secret").val(data.dev_client_secret);
        $("#dev_redirect_url").val(data.dev_redirect_url);
        //
        if (data.is_prod){
            $("#is_prod_prod").attr("checked", "checked");
        } else {
            $("#is_prod_dev").attr("checked", "checked");
        }
        //
        if (data.active){
            $("#active").attr("checked", "checked");
        } else {
            $("#active").removeAttr("checked", "checked");
        }
    }


    //
    disable_btns();
    preload(".section-preloader, .overlay", true);
    //
    $.ajax({
        type:'GET',
        url: app.admin_url + "/config/quickbooks",
        success:function(response){
            //
            enable_btns();
            preload(".section-preloader, .overlay");
            //
            if ( response && response.id ){
                onDataReady(response);
            }
            //
            else if (response.error){
                app.Toast.fire({ icon: 'error', title: response.error});
            }
        },
        error: function(){
            //
            enable_btns();
            preload(".section-preloader, .overlay");
            //
            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
        }
    });






    //
    $("#form_section").validate();
    //
    $("#form_section").submit(function(e) {
        e.preventDefault();
        //
        if ( $("#form_section").valid() ) {

            //
            $("#form_section").ajaxSubmit({
                url: app.admin_url + "/config/quickbooks",
                beforeSubmit: function(arr){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay", true);
                },
                success: function(send_response){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    if (send_response && send_response.id){

                        //
                        app.Toast.fire({ icon: 'success', title: "Registro Editado Exitosamente" });
                    }
                    //
                    else if (send_response.error){
                        app.Toast.fire({ icon: 'error', title: send_response.error });
                    }
                    //
                    else {
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                },
                error: function(response_error){
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });

        }
    });





    
    

    



    //
    $("#btnClearQuickbooksCredentials").click(function(){
        //
        localStorage.removeItem("token_id"); 
        //
        $("#token_id").html("");
        //
        $(this).hide();
        $("#btnGenerateAuthUrlAndLogin").show();
        $("#btnSyncCustomers").hide();
        $("#btnSyncSales").hide();
        $("#btnRevokeToken").hide();
    });

    



    //
    app.token_id = localStorage.getItem("token_id");
    console.log("***token_id: ", app.token_id);
    //
    if ( app.token_id ){
        $("#token_id").html( app.token_id );
        //        
        $("#btnGenerateAuthUrlAndLogin").hide();
        $("#btnClearQuickbooksCredentials").show();        
    } else {
        $("#token_id").html("");
        $("#btnGenerateAuthUrlAndLogin").show();
    }



    //
    app.token_id = localStorage.getItem("token_id");
    //
    if ( app.token_id ){
        $("#btnSyncCustomers").show();
        $("#btnSyncSales").show();
        $("#btnRevokeToken").show();
        $("#token_id").html( app.token_id );
        $("#btnClearQuickbooksCredentials").show();        
    } else {
        $("#btnSyncCustomers").hide();        
        $("#btnSyncSales").hide();        
        $("#btnRevokeToken").hide();
        $("#token_id").html("");
    }

    
    

    
        
        
    



    //
    $("#btnSyncCustomers").click(function(){

        //
        disable_btns();
        preload(".section-preloader, .overlay", true);

        $.ajax({
            type: "POST",
            url: app.admin_url + "/config/quickbooks/sync-customers?token_id=" + app.token_id,
            success: function(response) {
                console.log(response);
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                if (response && response.data) {

                }            
            },
            error: function(xhr, status, error) {
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                console.error("Error: " + error);
            }
        });

        
    });


    //
    $("#btnSyncSales").click(function(){

        //
        disable_btns();
        preload(".section-preloader, .overlay", true);

        $.ajax({
            type: "POST",
            url: app.admin_url + "/config/quickbooks/sync-sales?token_id=" + app.token_id,
            success: function(response) {
                console.log(response);
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                if (response && response.data) {

                }            
            },
            error: function(xhr, status, error) {
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                console.error("Error: " + error);
            }
        });

        
    });





    //
    $("#btnRevokeToken").click(function(){
        //
        if (confirm("Revoke Token?")){

            //
            disable_btns();
            preload(".section-preloader, .overlay", true);

            $.ajax({
                type: "POST",
                url: app.admin_url + "/config/quickbooks/revoke?token_id=" + app.token_id,
                success: function(response) {
                    //console.log(response);
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    if (response && response.success) {
                        alert("Token revoked");
                        location.reload();
                    }            
                },
                error: function(xhr, status, error) {
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    console.error("Error: " + error);
                }
            });

        }
    });








    app.loginPopupUri = function(auth_url) {
        
        // Launch Popup
        var parameters = "location=1,width=800,height=650";
        parameters += ",left=" + (screen.width - 800) / 2 + ",top=" + (screen.height - 650) / 2;

        var win = window.open(auth_url, 'connectPopup', parameters);
        var pollOAuth = window.setInterval(function () {
            try {

                if (win.document.URL.indexOf("code") != -1) {
                    window.clearInterval(pollOAuth);
                    win.close();
                    location.reload();
                }
            } catch (e) {
                console.log(e)
            }
        }, 100);
    }


    //
    $("#btnGenerateAuthUrlAndLogin").click(function(){
        //
        $.ajax({
            type: "GET",
            url: app.admin_url + "/config/quickbooks/auth-url",
            success: function (response) {
                //
                enable_btns();
                preload(".section-preloader, .overlay");
                //
                if (response && response.auth_url){                    
                    //
                    app.loginPopupUri(response.auth_url);
                }            
            },
            error: function () {
                //
                enable_btns();
                preload(".section-preloader, .overlay");
            }
        });
    });

    





})(jQuery);