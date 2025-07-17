define(function(){
    function moduleReady(modal, page_type){
        console.log("------show sale: ", page_type);


        

        


        //
        $("#btnContinue").click(function(e) {
            e.preventDefault();

            //
            var cant_expired_items = vent.getElemsExpired();
            //
            if (cant_expired_items){

                //
                app.Toast.fire({icon: 'warning', title: "Tienes " + cant_expired_items + " elementos expirados, actualizalos para continuar"});

            }  else {
                
                 //
                 loadModalV2({
                    id: "modal-checkout",
                    modal_size: "lg",
                    data: null,
                    html_tmpl_url: "/app/sales/modals/checkout.html?v=" + dynurl(),
                    js_handler_url: "/app/sales/modals/checkout.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){

                        //
                        enable_btns();

                        //
                        $('#modal-title-2').html("Checkout");
                        $('.btnAdd2').html("<i class='fa fa-send'></i> Enviar");

                    }
                });

            }

        });




        //
        vent.actualizarOcupacion(app.temp_sale_id);


    }
    return {init: moduleReady}
});