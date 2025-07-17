define(function(){
    function moduleReady(modal, sucursal_data){
        console.log(sucursal_data);
    
        
        //
        $("#modal-title").html("Agregar Sellos para " + sucursal_data.name);
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Crear");

        
        

        //
        $('#form_sellos').validate();
        //
        $('#form_sellos').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_sellos').valid() ) {
                //
                $('#form_sellos').ajaxSubmit({
                    url: app.admin_url + "/sucursales/" + sucursal_data.id + "/sellos",
                    beforeSubmit: function(arr){
                        disable_btns();
                    },
                    success: function(response){
                        //
                        enable_btns();
                        //
                        if (response && response.id){
                            //
                            app.Toast.fire({ icon: 'success', title: "Record added succesfully" });
                            //
                            $("#grid_sucursal_sellos").DataTable().ajax.reload();
                            //
                            $("#modal-add-sellos").find('.modal').modal("hide");
                        }
                        //
                        else if (response.error){
                            app.Toast.fire({ icon: 'error', title: response.error});
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }
                    },
                    error: function(response){
                        enable_btns();
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });

            }
        });




        // def focus
        //$("#sello").focus();




    }
    return {init: moduleReady}
});