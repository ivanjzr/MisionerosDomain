define(function(){
    function moduleReady(modal, customer_data){
        console.log("------customer data: ", customer_data);
        
        //
        app.selectedService = null;
        $("#serv_info_title").text("Solo se podran cobrar citas con estatus de confirmadas ")
        $("#select-service-title").text("Buscar Citas de " + customer_data.name);


        
        //
        $.S2Ext({
            S2ContainerId: "customer_service_id",
            placeholder: "...buscar servicio",
            language: {
                noResults: function(){ return ""; },
                searching: function(){ return ""; }
            },
            dropdownParent: $('#modal-select-service .modal'),
            allowClear: true,
            minimumInputLength: 0,
            minimumResultsForSearch: 0,
            remote: {
                qs: function(){
                    return {};
                },
                url: app.admin_url + "/customers/" + customer_data.id + "/services/search",
                dataType: 'json',
                delay: 250,
                processResults: function (response, page) {
                    return {
                        results: response
                    };
                },
                cache: false,
                templateResult: function(item){
                    if (item.loading) {
                        return item.text;
                    }
                    return item.id + " - " + item.nombre + " - " + item.location_name;
                },
                templateSelection: function(item){
                    if (item.id){
                        return item.id + " - " + item.nombre + " - " + item.location_name;
                    }
                    return item.text;
                },
            },
            onChanged: function(sel_id, data){ 
                //console.log('Cliente seleccionado:', sel_id, data);
                //
                if (app.sel_suc_id !== parseInt(data.sucursal_id)){
                    app.Toast.fire({ icon: 'info', title:"No se pueden pagar citas de otra sucursal (" + data.location_name + ") "});
                    s2ResetValue("#customer_service_id");
                    return;
                }
                //
                app.selectedService = data;
                //
                $(document).trigger('serviceSelected', [app.selectedService]);                
                $("#modal-select-service").find('.modal').modal("hide");
            },
            onClose: function(){
                //
                app.selectedService = null;
            }
        });



        //
        $('#form_select_service').validate();
        //
        $('#form_select_service').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();            
            //
            if ( $('#form_select_service').valid() ) {
                //
                $(document).trigger('serviceSelected', [app.selectedService]);
                $("#modal-select-service").find('.modal').modal("hide");
            }
        });






    }
    return {init: moduleReady}
});