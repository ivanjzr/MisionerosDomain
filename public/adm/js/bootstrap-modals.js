// vars if we use generics
var generic_modal_url   = "/assets/generics/modal.html",
    form_auth_url       = "/assets/auth/form-valida-usuario.html",
    form_yes_no_url     = "/assets/auth/form-yes-no.html";



function loadModalV2(options){

    /*
     var opts = {
     id: "myid",
     title: "my modal title",
     data: {
     id_caja: 1234
     },
     modal_size: "lg",
     hide_header: true,
     hide_close_btn: true,
     hide_footer: true,
     hide_title: true,
     onHide: function(){
     alert("on hide");
     }
     }
     */

    //
    var modal_url = "/app/common/modal.html";
    //
    if (options.modal_url){
        modal_url = options.modal_url;
    }


    //
    var modal_container = $("<div />");
    modal_container.attr("id", options.id);


    //
    if ( options.onBeforeLoad && $.isFunction(options.onBeforeLoad)){
        options.onBeforeLoad();
    }



    //
    $.get(modal_url, function(html_modal){


        //
        modal_container.append(html_modal);


        //
        if (options.modal_size){
            modal_container.find('.modal-dialog').addClass("modal-"+options.modal_size);
        } else {
            modal_container.find('.modal-dialog').addClass("modal-md");
        }

        //
        modal_container.find('.modal-title').text( ((options.title) ? options.title : "") );

        //
        if (options.show_header || options.show_title){
            modal_container.find('.modal-header').show();
        } else {
            modal_container.find('.modal-header').hide();
        }
        //
        if (options.show_close_btn){
            modal_container.find('.modal-btn-close').show();
        } else {
            modal_container.find('.modal-btn-close').hide();
        }
        //
        if (options.show_footer){
            modal_container.find('.modal-footer').show();
        } else {
            modal_container.find('.modal-footer').hide();
        }
        //
        if (options.show_title){
            modal_container.find('.modal-title').show();
        } else {
            modal_container.find('.modal-title').hide();
        }


        //
        var modal = modal_container.find('.modal');


        //
        modal.on('hidden.bs.modal', function (e) {

            // fix: remove class modal-open only if a modal is not open
            setTimeout(function(){
                //
                if ( $(".modal").length ) {
                    $("body").addClass("modal-open");
                } else {
                    $("body").removeClass("modal-open");
                }
            }, 500);

            //
            modal_container.remove();
            //
            if ( options.onHide && $.isFunction(options.onHide)){
                options.onHide();
            }
        });


        //
        if (options.appendTo){
            $(options.appendTo).append(modal_container);
        } else {
            $("body").append(modal_container);
        }


        //
        if ( options.onBeforeLoadHTML && $.isFunction(options.onBeforeLoadHTML)){
            options.onBeforeLoadHTML();
        }



        //
        modal.on('shown.bs.modal', function (e) {

            // Modal Overlay Fix
            var zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);
            setTimeout(function() {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);


            //
            if ( options.onShown && $.isFunction(options.onShown)){
                options.onShown();
            }


            // load html template
            if (options.html_tmpl_url){
                $.get(options.html_tmpl_url, function (html_contents) {

                    //
                    modal.find('.modal-body').html(html_contents);

                    //
                    if ( options.onBeforeLoadJS && $.isFunction(options.onBeforeLoadJS)){
                        options.onBeforeLoadJS();
                    }


                    //
                    if (options.js_handler_url){
                        //
                        requirejs([options.js_handler_url], function (module) {

                            //
                            module.init(modal, (options.data) ? options.data : null, options);

                            //
                            if ( options.onInit && $.isFunction(options.onInit)){
                                options.onInit();
                            }

                        });
                    } else {
                        //
                        if ( options.onInit && $.isFunction(options.onInit)){
                            options.onInit();
                        }
                    }



                });
            }
            //
            else {
                options.onInit();
            }

        });




        // call show modal
        modal.modal('show');

    });
}
