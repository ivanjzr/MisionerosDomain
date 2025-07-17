define(function(){
    function moduleReady(modal, section_data){
        console.log("------edit comisiones: ", section_data);




        //
        $('#modal-title').html("Cambiar Status Invoice #" + section_data.id);
        $('.btnAdd2').html("<i class='fa fa-save'></i> Actualizar");



        function subitUpdateStatus(){

            //
            var send_email = $("#send_email").is(":checked");            

            //
            $('#form_section').ajaxSubmit({
                url: app.admin_url + "/invoices/" + section_data.id + "/update-status",
                beforeSubmit: function(arr){
                    disable_btns();
                    preload(".section-preloader, .overlay", true);
                },
                success: function(response){
                    //
                    if (response && response.id){
                        

                        if (send_email){

                            //
                            app.sendInvoice(section_data.id, function(){
                                //
                                enable_btns();
                                preload(".section-preloader, .overlay");
                                //
                                app.Toast.fire({ icon: 'success', title: "Estado Actualizado y Mensaje enviado correctamente" });
                                $("#modal-cambiar-status").find('.modal').modal("hide");
                                $("#grid_section").DataTable().ajax.reload();
                            });


                        } else {
                            
                            //
                            enable_btns();
                            preload(".section-preloader, .overlay");
                            //
                            app.Toast.fire({ icon: 'success', title: "Estado Actualizado Correctamente" });
                            $("#modal-cambiar-status").find('.modal').modal("hide");
                            $("#grid_section").DataTable().ajax.reload();
                        }

                        

                         

                    }
                    //
                    else if (response.error){
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        app.Toast.fire({ icon: 'error', title: response.error});
                    }
                    //
                    else {
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                },
                error: function(response){
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });
        }




       //
       $('#form_section').validate();
       //
       $('#form_section').submit(function(e) {
           e.preventDefault();
           e.stopImmediatePropagation();

           //
           if ( $('#form_section').valid() ) {

                //
                var status_id = $("#status_id").val();
                if (status_id && parseInt(status_id) === app.inv_status_id_paid){
                    if (confirm("Cuando marcas un invoice como Pagado ya no se podra cambiar el estatus por que las ventas se marcaran como cobradas, continuar?")){
                        subitUpdateStatus();
                    }
                } else {
                    subitUpdateStatus();
                }

           }
       });






    }
    return {init: moduleReady}
});